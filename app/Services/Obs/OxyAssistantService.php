<?php

namespace App\Services\Obs;

use App\Models\ObsMacro;
use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OxyAssistantService
{
    public function __construct(
        private readonly ScreenCollectionService $collectionService,
        private readonly ObsCommandService $commandService,
    ) {}

    /**
     * Handle a natural-language question, casual chat, copy request, or live Studio/OBS action for Oxy.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{
     *     reply: string,
     *     reaction: string,
     *     executed: bool,
     *     action: string|null,
     *     suggestions: array<int, string>,
     *     links: array<int, array{label: string, url: string}>
     * }
     */
    public function reply(string $message, ?User $user = null, ?string $studioSlug = null, array $history = []): array
    {
        $clean = trim($message);
        $lower = mb_strtolower($clean);

        $studio = $this->resolveStudio($user, $studioSlug);
        $collection = $this->resolveCollection($user, $studio);

        if ($clean === '') {
            return $this->makeResponse(
                reply: $this->greetingReply($user, $studio),
                reaction: 'wave',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        // 1. Live Studio / OBS Actions (authenticated users only — deterministic, 0 tokens)
        if ($user instanceof User) {
            $actionResult = $this->tryExecuteLiveAction($clean, $lower, $user, $studio, $collection);
            if ($actionResult !== null) {
                return $actionResult;
            }

            // 2. Signed-in Studio Operator Gemini AI (when GEMINI_API_KEY is configured, with cache + local fallback)
            $geminiReply = $this->tryAskGemini($clean, $user, $studio, $collection, $history);
            if ($geminiReply !== null) {
                return $geminiReply;
            }
        } elseif ($this->looksLikeLiveCommand($lower)) {
            return $this->makeResponse(
                reply: 'I\'d love to run that for you, but you\'ll need to sign in first so I know which studio is yours. Once you\'re in, just ask again!',
                reaction: 'peek',
                links: [['label' => 'Sign in to Studio', 'url' => '/admin/login']],
                suggestions: ['How do I pair OBS?', 'Suggest lower-third copy', 'What is Unified Stage?'],
            );
        }

        // 3. Built-in conversational small talk, identity, gratitude & vibe checks (Guests & fallback)
        $chatResponse = $this->trySmallTalk($lower, $user, $studio, $collection);
        if ($chatResponse !== null) {
            return $chatResponse;
        }

        // 4. Follow-up requests ("tell me more", "what else?", "and then?") using recent history
        if ($this->isFollowUp($lower) && $history !== []) {
            $followUp = $this->answerFollowUp($history, $user, $studio, $collection);
            if ($followUp !== null) {
                return $followUp;
            }
        }

        // 5. Copy / Overlay Text Suggestions (Guests & fallback)
        if ($this->matchesAny($lower, ['suggest', 'idea', 'copy', 'headline', 'ticker text', 'lower third text', 'write me', 'brainstorm', 'examples for'])) {
            return $this->buildCopySuggestions($lower, $collection);
        }

        // 6. Deep Knowledge Base & OBS WebSocket v5 Q&A
        return $this->answerFromKnowledgeBase($lower, $user, $studio, $collection);
    }

    /**
     * Call Google Gemini Interactions API for signed-in operators when GEMINI_API_KEY is set.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array<string, mixed>|null
     */
    private function tryAskGemini(
        string $message,
        User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
        array $history,
    ): ?array {
        $apiKey = trim((string) config('services.gemini.key', ''));
        if ($apiKey === '') {
            return null;
        }

        $model = trim((string) config('services.gemini.model', 'gemini-3.5-flash-lite')) ?: 'gemini-3.5-flash-lite';
        $endpoint = trim((string) config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1/interactions'));

        $studioKey = $studio?->id ?? 0;
        $historyFingerprint = md5((string) json_encode(array_slice($history, -2)));
        $cacheKey = 'oxy_gemini_v1_'.md5($model.'|'.$studioKey.'|'.mb_strtolower($message).'|'.$historyFingerprint);

        $cachedText = Cache::get($cacheKey);
        if (is_string($cachedText) && $cachedText !== '') {
            return $this->makeResponse(
                reply: $cachedText,
                reaction: 'cheer',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        $systemInstruction = $this->buildGeminiSystemInstruction($user, $studio, $collection);
        $transcriptLines = [];
        foreach (array_slice($history, -6) as $turn) {
            $roleLabel = ($turn['role'] ?? '') === 'assistant' ? 'Oxy' : 'User';
            $turnText = trim((string) ($turn['text'] ?? ''));
            if ($turnText !== '') {
                $transcriptLines[] = "{$roleLabel}: {$turnText}";
            }
        }
        $transcriptLines[] = "User: {$message}";
        $inputPrompt = implode("\n", $transcriptLines);

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'model' => $model,
                    'system_instruction' => $systemInstruction,
                    'input' => $inputPrompt,
                    'store' => false,
                    'generation_config' => [
                        'max_output_tokens' => 240,
                        'temperature' => 0.65,
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $text = $this->extractGeminiOutputText($response->json());
            if ($text === '') {
                return null;
            }

            Cache::put($cacheKey, $text, now()->addHours(12));

            return $this->makeResponse(
                reply: $text,
                reaction: 'cheer',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildGeminiSystemInstruction(
        User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
    ): string {
        $firstName = trim(explode(' ', $user->name)[0] ?? 'friend');
        $studioSummary = $studio instanceof ObsStudio
            ? "Active Studio: \"{$studio->name}\" (".($studio->isOnline() ? 'ONLINE' : 'OFFLINE').', OBS Scene: '.($studio->getCurrentProgramScene() ?: 'none').').'
            : 'No active studio created yet.';
        $collectionSummary = $collection instanceof ScreenCollection
            ? "Active Screen Collection: \"{$collection->name}\" (Active Stage View: \"".($collection->activeView?->name ?? 'none').'").'
            : 'No active Screen Collection.';

        $kbSummaries = [];
        foreach (KnowledgeBaseService::getArticles() as $article) {
            $kbSummaries[] = "- {$article['title']}: {$article['summary']}";
        }
        $kbContext = implode("\n", $kbSummaries);

        return <<<PROMPT
You are Oxy, the friendly, cute 3D black-and-gold studio sidekick inside Obsisstant (a web control center, mobile deck, and single-URL browser-source overlay engine for OBS Studio v28+ using OBS WebSocket v5 and Laravel Reverb).
Speak like a warm, helpful human broadcast crewmate talking to {$firstName} — casual, clear, encouraging, and concise (2 to 4 short sentences max, or up to 3 clean bullet points when giving overlay copy). Never sound robotic or stiff.
Live Context:
{$studioSummary}
{$collectionSummary}
Knowledge Base Reference:
{$kbContext}
PROMPT;
    }

    private function extractGeminiOutputText(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        if (is_string($payload['output_text'] ?? null) && trim($payload['output_text']) !== '') {
            return trim($payload['output_text']);
        }

        $chunks = [];
        foreach (($payload['steps'] ?? []) as $step) {
            if (! is_array($step) || ($step['type'] ?? '') !== 'model_output') {
                continue;
            }
            foreach (($step['content'] ?? []) as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'text' && is_string($part['text'] ?? null)) {
                    $chunks[] = trim($part['text']);
                }
            }
        }

        if ($chunks !== []) {
            return trim(implode("\n", array_filter($chunks)));
        }

        foreach (($payload['outputs'] ?? []) as $out) {
            if (is_array($out) && is_string($out['text'] ?? null)) {
                $chunks[] = trim($out['text']);
            }
        }

        return trim(implode("\n", array_filter($chunks)));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function trySmallTalk(
        string $lower,
        ?User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
    ): ?array {
        $stripped = trim((string) preg_replace('/[^\p{L}\p{N}\s\']/u', '', $lower));

        if (in_array($stripped, ['hi', 'hey', 'hello', 'yo', 'hiya', 'sup', 'hey oxy', 'hi oxy', 'hello oxy', 'good morning', 'good afternoon', 'good evening'], true)) {
            return $this->makeResponse(
                reply: $this->greetingReply($user, $studio),
                reaction: 'wave',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        if ($this->matchesAny($lower, ['how are you', 'how is it going', 'hows it going', 'you good', 'how you doing', 'whats up', "what's up"])) {
            $studioNote = $studio instanceof ObsStudio && $studio->isOnline()
                ? " Your \"{$studio->name}\" rig is online and looking steady."
                : '';

            return $this->makeResponse(
                reply: "Doing great — just keeping an eye on things backstage!{$studioNote} What are we working on today?",
                reaction: 'cheer',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        if ($this->matchesAny($lower, ['who are you', 'what are you', 'your name', 'are you a bot', 'are you ai', 'tell me about yourself'])) {
            return $this->makeResponse(
                reply: "I'm Oxy, your backstage buddy here in Obsisstant! I can walk you through pairing OBS, brainstorm clean lower-third or ticker lines, or jump in to run timers, overlays, and scenes while you're live.",
                reaction: 'spin',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        if ($this->matchesAny($lower, ['what can you do', 'help me', 'capabilities', 'how do you work']) || $stripped === 'help') {
            $livePart = $user instanceof User
                ? "Since you're signed in, you can also tell me things like \"start a 5 minute timer\", \"put Sarah - Host on the lower third\", \"what scenes do I have?\", or \"check studio status\"."
                : 'If you sign in to your studio, I can also run your timers, lower thirds, scoreboards, and OBS scenes directly from this chat.';

            return $this->makeResponse(
                reply: "Pretty much anything you'd ask a studio tech! I can help you set up OBS pairing, explain overlays and 00:00 timer triggers, or write ticker and lower-third copy. {$livePart}",
                reaction: 'dance',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        if (in_array($stripped, ['thanks', 'thank you', 'ty', 'thx', 'cheers', 'awesome', 'nice', 'cool', 'perfect', 'great', 'got it', 'makes sense', 'sweet'], true)) {
            $replies = [
                'Anytime! Give me a shout if you need anything else before you go live.',
                'You got it — happy to help! Have a smooth broadcast.',
                'No problem at all. I\'m right here whenever you need a hand.',
            ];

            return $this->makeResponse(
                reply: $replies[strlen($stripped) % count($replies)],
                reaction: 'cheer',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        if (in_array($stripped, ['bye', 'goodbye', 'see ya', 'cya', 'later'], true)) {
            return $this->makeResponse(
                reply: 'Catch you later! Good luck with the stream — just tap me if you need me.',
                reaction: 'wave',
            );
        }

        return null;
    }

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array<string, mixed>|null
     */
    private function answerFollowUp(
        array $history,
        ?User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
    ): ?array {
        $lastTopicText = '';
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $item = $history[$i];
            if (($item['role'] ?? '') === 'assistant' && ! empty($item['text'])) {
                $lastTopicText = (string) $item['text'];
                break;
            }
        }

        if ($lastTopicText === '') {
            return null;
        }

        $lowerTopic = mb_strtolower($lastTopicText);
        foreach (KnowledgeBaseService::getArticles() as $article) {
            if (str_contains($lowerTopic, mb_strtolower($article['title']))) {
                $details = $this->extractReadableExcerpt($article['markdown'], 2);

                return $this->makeResponse(
                    reply: "Sure, here's a bit more on {$article['title']}:\n\n{$details}",
                    reaction: 'wave',
                    links: [['label' => 'Open full guide', 'url' => route('knowledge-base', ['topic' => $article['slug']], false)]],
                    suggestions: $this->defaultSuggestions($user, $studio, $collection),
                );
            }
        }

        return $this->makeResponse(
            reply: 'If you want, we can dive into pairing OBS, setting up Unified Stage overlays, or testing a live timer. Which one sounds most helpful right now?',
            reaction: 'peek',
            suggestions: $this->defaultSuggestions($user, $studio, $collection),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryExecuteLiveAction(
        string $raw,
        string $lower,
        User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
    ): ?array {
        // List views or OBS scenes
        if ($this->matchesAny($lower, ['what scenes', 'list scenes', 'my scenes', 'what views', 'list views', 'my views', 'available views'])) {
            $scenes = [];
            if ($studio instanceof ObsStudio) {
                foreach ($studio->getScenes() as $sceneItem) {
                    $name = is_array($sceneItem) ? (string) ($sceneItem['sceneName'] ?? '') : (string) $sceneItem;
                    if ($name !== '') {
                        $scenes[] = $name;
                    }
                }
            }

            $views = [];
            if ($collection instanceof ScreenCollection) {
                $collection->loadMissing('views');
                foreach ($collection->views as $view) {
                    if ($view instanceof ScreenView) {
                        $views[] = $view->name;
                    }
                }
            }

            $parts = [];
            if ($views !== []) {
                $parts[] = 'Stage views: '.implode(', ', $views).'.';
            }
            if ($scenes !== []) {
                $parts[] = 'OBS scenes: '.implode(', ', $scenes).'.';
            }

            return $this->makeResponse(
                reply: $parts !== []
                    ? "Here's what I see right now:\n".implode("\n", $parts)."\nJust tell me which one to switch to!"
                    : "I don't see any views or OBS scenes synced yet — connect your studio agent or add views in Canvas Builder.",
                reaction: 'peek',
                suggestions: $views !== [] ? ["Switch to {$views[0]}"] : ['Check studio status'],
            );
        }

        // Status summary
        if ($this->matchesAny($lower, ['status', 'online', 'connected', 'what is live', 'whats live', "what's live", 'studio state', 'how much time'])) {
            if (! $studio instanceof ObsStudio) {
                return $this->makeResponse(
                    reply: "Looks like you don't have an active Studio set up yet. Head to Admin Panel → OBS Studios to create one, and I'll hook right into it.",
                    reaction: 'peek',
                    links: [['label' => 'Open Studios', 'url' => '/admin/obs-studios']],
                );
            }

            $onlineText = $studio->isOnline() ? 'online and ready' : 'currently offline';
            $scene = $studio->getCurrentProgramScene() ?: 'none';
            $viewName = $collection?->activeView?->name ?? 'none';

            $timerNote = '';
            if ($collection instanceof ScreenCollection) {
                $timer = $collection->runtime_state['timer'] ?? [];
                $duration = (int) ($timer['duration_seconds'] ?? 0);
                $status = (string) ($timer['status'] ?? 'stopped');
                if ($duration > 0) {
                    $formatted = gmdate($duration >= 3600 ? 'H:i:s' : 'i:s', $duration);
                    $timerNote = " Timer is {$status} ({$formatted}).";
                }
            }

            return $this->makeResponse(
                reply: "\"{$studio->name}\" is {$onlineText} — OBS is on \"{$scene}\" and your active Stage view is \"{$viewName}\".{$timerNote}",
                reaction: $studio->isOnline() ? 'cheer' : 'wave',
                links: [['label' => 'Open Studio', 'url' => route('studio.show', ['studio' => $studio->slug], false)]],
                suggestions: ['Start 5m timer', 'Show lower third', 'Suggest ticker headlines'],
            );
        }

        // Timer actions on active ScreenCollection
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['timer', 'countdown', 'clock', 'minute', 'min ', 'mins', 'second', 'sec '])) {
            if (preg_match('/(?:set|start|give me|make it|run)\s+(?:a\s+)?(?:timer|countdown)?\s*(?:for|to)?\s*(\d+)\s*(m|min|mins|minute|minutes|s|sec|secs|second|seconds)\b/i', $raw, $m)) {
                $amount = max(1, (int) $m[1]);
                $unit = strtolower($m[2]);
                $seconds = str_starts_with($unit, 'm') ? $amount * 60 : $amount;
                $autoStart = ! $this->matchesAny($lower, ['set only', 'without starting', 'paused']) && $this->matchesAny($lower, ['start', 'give me', 'run', 'go']);
                $this->collectionService->updateRuntime($collection, [
                    'timer' => [
                        'action' => 'set',
                        'duration_seconds' => $seconds,
                        'auto_start' => $autoStart,
                    ],
                ]);
                $formatted = gmdate($seconds >= 3600 ? 'H:i:s' : 'i:s', $seconds);

                return $this->makeResponse(
                    reply: $autoStart
                        ? "Got it! Set the countdown to {$formatted} and started it for you."
                        : "All set — your countdown timer is queued at {$formatted}. Say \"start timer\" whenever you're ready.",
                    reaction: 'cheer',
                    executed: true,
                    action: 'timer.set',
                    suggestions: $autoStart ? ['Pause timer', 'Reset timer'] : ['Start timer'],
                );
            }

            if ($this->matchesAny($lower, ['start', 'resume', 'go', 'unpause'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'start']]);

                return $this->makeResponse("Timer's rolling!", 'cheer', true, 'timer.start', ['Pause timer', 'Reset timer']);
            }

            if ($this->matchesAny($lower, ['pause', 'stop', 'halt', 'freeze'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'pause']]);

                return $this->makeResponse('Paused the timer right where it is.', 'wave', true, 'timer.pause', ['Resume timer', 'Reset timer']);
            }

            if ($this->matchesAny($lower, ['reset', 'restart', 'clear'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'reset']]);

                return $this->makeResponse('Timer is reset back to its full duration.', 'spin', true, 'timer.reset', ['Start timer']);
            }
        }

        // Lower Third actions
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['lower third', 'lower-third', 'banner', 'name tag'])) {
            if ($this->matchesAny($lower, ['hide', 'off', 'clear', 'remove', 'take down', 'tuck'])) {
                $this->collectionService->updateRuntime($collection, ['lower_third' => ['visible' => false]]);

                return $this->makeResponse('All clear — I tucked the lower third away.', 'wave', true, 'lower_third.hide', ['Show lower third']);
            }

            if ($this->matchesAny($lower, ['show', 'display', 'on', 'put ', 'bring up'])) {
                $patch = ['visible' => true];
                if (preg_match('/(?:show|display|put)\s+(?:lower[\s-]?third|banner)?\s*(?:to|as|for)?\s*(.+)/i', $raw, $m)) {
                    $text = trim((string) preg_replace('/\b(?:on the lower third|on lower third|lower third|banner|now|please)\b/i', '', $m[1]));
                    $text = trim($text, " \t\n\r\0\x0B-\"'");
                    if ($text !== '' && ! in_array(mb_strtolower($text), ['on', 'now', 'up'], true)) {
                        $parts = array_map('trim', explode('-', $text, 2));
                        $patch['title'] = $parts[0];
                        if (! empty($parts[1])) {
                            $patch['subtitle'] = $parts[1];
                        }
                    }
                }
                $this->collectionService->updateRuntime($collection, ['lower_third' => $patch]);

                $labelNote = isset($patch['title']) ? " with \"{$patch['title']}\"" : '';

                return $this->makeResponse(
                    reply: "Lower third is live on air{$labelNote}!",
                    reaction: 'cheer',
                    executed: true,
                    action: 'lower_third.show',
                    suggestions: ['Hide lower third'],
                );
            }
        }

        // Scoreboard actions
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['score', 'team a', 'team b', 'swap team', 'point'])) {
            if ($this->matchesAny($lower, ['swap'])) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['swap' => true]]);

                return $this->makeResponse('Swapped Team A and Team B sides!', 'spin', true, 'scoreboard.swap');
            }
            if ($this->matchesAny($lower, ['reset'])) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['reset_scores' => true]]);

                return $this->makeResponse('Scoreboard is back to 0 - 0.', 'wave', true, 'scoreboard.reset');
            }

            $delta = 1;
            if (preg_match('/\b(\d+)\s*(?:pt|pts|point|points)?\b/i', $raw, $dm)) {
                $delta = max(1, min(20, (int) $dm[1]));
            }

            if (str_contains($lower, 'team a')) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['delta_a' => $delta]]);

                return $this->makeResponse("Nice! Added +{$delta} to Team A.", 'cheer', true, 'scoreboard.delta_a');
            }
            if (str_contains($lower, 'team b')) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['delta_b' => $delta]]);

                return $this->makeResponse("Added +{$delta} to Team B!", 'cheer', true, 'scoreboard.delta_b');
            }
        }

        // Switch Screen View by name
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['switch view', 'switch to', 'go to', 'show view', 'change view', 'pull up'])) {
            $collection->loadMissing('views');
            foreach ($collection->views as $view) {
                if ($view instanceof ScreenView && str_contains($lower, mb_strtolower($view->name))) {
                    $this->collectionService->switchView($collection, $view);

                    return $this->makeResponse(
                        reply: "Switched your live stage over to \"{$view->name}\"!",
                        reaction: 'spin',
                        executed: true,
                        action: 'collection.switch_view',
                    );
                }
            }
        }

        // Run Macro by name
        if ($studio instanceof ObsStudio && $this->matchesAny($lower, ['macro', 'run '])) {
            $macros = ObsMacro::query()->where('obs_studio_id', $studio->id)->get();
            foreach ($macros as $macro) {
                if ($macro->name !== '' && str_contains($lower, mb_strtolower($macro->name))) {
                    $this->commandService->executeMacro($studio, $macro, $user);

                    return $this->makeResponse(
                        reply: "Fired off your \"{$macro->name}\" macro!",
                        reaction: 'cheer',
                        executed: true,
                        action: 'macro.run',
                    );
                }
            }
        }

        // OBS Studio Stream / Record / Scene commands
        if ($studio instanceof ObsStudio) {
            if ($this->matchesAny($lower, ['start stream', 'go live'])) {
                $this->commandService->dispatch($studio, 'StartStream', [], $user);

                return $this->makeResponse("Sent StartStream to \"{$studio->name}\" — you're going live!", 'cheer', true, 'obs.StartStream');
            }
            if ($this->matchesAny($lower, ['stop stream', 'end stream'])) {
                $this->commandService->dispatch($studio, 'StopStream', [], $user);

                return $this->makeResponse("Sent StopStream to \"{$studio->name}\". Great show!", 'wave', true, 'obs.StopStream');
            }
            if ($this->matchesAny($lower, ['start record', 'start recording'])) {
                $this->commandService->dispatch($studio, 'StartRecord', [], $user);

                return $this->makeResponse("Recording started on \"{$studio->name}\".", 'cheer', true, 'obs.StartRecord');
            }
            if ($this->matchesAny($lower, ['stop record', 'stop recording'])) {
                $this->commandService->dispatch($studio, 'StopRecord', [], $user);

                return $this->makeResponse("Stopped recording on \"{$studio->name}\".", 'wave', true, 'obs.StopRecord');
            }

            if ($this->matchesAny($lower, ['scene', 'cut to', 'switch obs'])) {
                foreach ($studio->getScenes() as $sceneItem) {
                    $sceneName = is_array($sceneItem) ? (string) ($sceneItem['sceneName'] ?? '') : (string) $sceneItem;
                    if ($sceneName !== '' && str_contains($lower, mb_strtolower($sceneName))) {
                        $this->commandService->dispatch($studio, 'SetCurrentProgramScene', ['sceneName' => $sceneName], $user);

                        return $this->makeResponse("Cut OBS over to \"{$sceneName}\"!", 'spin', true, 'obs.SetCurrentProgramScene');
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCopySuggestions(string $lower, ?ScreenCollection $collection): array
    {
        $showName = $collection?->name ?: 'Live Show';

        if (str_contains($lower, 'ticker')) {
            return $this->makeResponse(
                reply: "Here are a few clean ticker lines you can drop straight into {$showName}:\n• Welcome to {$showName} — glad you're here!\n• Have a question? Drop it in chat for our live Q&A.\n• Coming up next: Main segment right after this short break.",
                reaction: 'dance',
                suggestions: ['Suggest lower-third copy', 'Start 5m timer'],
            );
        }

        return $this->makeResponse(
            reply: "Here are three crisp lower-third ideas for {$showName}:\n• LIVE | Alex Rivera — Host & Producer\n• GUEST | Jordan Lee — Lead Engineer\n• UP NEXT | Live Q&A — Drop questions in chat",
            reaction: 'cheer',
            suggestions: ['Suggest ticker headlines', 'Show lower third'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function answerFromKnowledgeBase(
        string $lower,
        ?User $user,
        ?ObsStudio $studio,
        ?ScreenCollection $collection,
    ): array {
        $expanded = $this->expandSynonyms($lower);
        $tokens = array_values(array_filter(
            preg_split('/[^a-z0-9]+/i', $expanded) ?: [],
            static fn (string $t): bool => mb_strlen($t) >= 3 && ! in_array($t, ['how', 'what', 'when', 'where', 'why', 'can', 'the', 'for', 'and', 'with', 'you', 'oxy', 'does', 'this', 'that', 'from', 'into'], true),
        ));

        $bestArticle = null;
        $bestScore = 0;

        foreach (KnowledgeBaseService::getArticles() as $article) {
            $titleLower = mb_strtolower($article['title']);
            $summaryLower = mb_strtolower($article['summary']);
            $bodyLower = mb_strtolower($article['markdown']);
            $score = 0;

            foreach ($tokens as $tok) {
                if (str_contains($titleLower, $tok)) {
                    $score += 4;
                } elseif (str_contains($summaryLower, $tok)) {
                    $score += 2;
                } elseif (str_contains($bodyLower, $tok)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestArticle = $article;
            }
        }

        if ($bestArticle !== null && $bestScore > 0) {
            $excerpt = $this->extractReadableExcerpt($bestArticle['markdown'], 1);
            $reply = $excerpt !== ''
                ? "{$bestArticle['title']} — {$bestArticle['summary']}\n\n{$excerpt}"
                : "{$bestArticle['title']}: {$bestArticle['summary']}";

            return $this->makeResponse(
                reply: $reply,
                reaction: 'wave',
                links: [['label' => 'Read full guide', 'url' => route('knowledge-base', ['topic' => $bestArticle['slug']], false)]],
                suggestions: array_merge(['Tell me more'], array_slice($this->defaultSuggestions($user, $studio, $collection), 0, 2)),
            );
        }

        // Check OBS Request Catalog match
        foreach (KnowledgeBaseService::getCommandReference() as $cmd) {
            $cmdHay = mb_strtolower($cmd['request_type'].' '.$cmd['label'].' '.$cmd['description']);
            foreach ($tokens as $tok) {
                if (str_contains($cmdHay, $tok)) {
                    return $this->makeResponse(
                        reply: "For OBS WebSocket v5, you'll want `{$cmd['request_type']}` ({$cmd['category']}) — {$cmd['description']}",
                        reaction: 'cheer',
                        links: [['label' => 'Command Reference', 'url' => route('knowledge-base', [], false)]],
                    );
                }
            }
        }

        return $this->makeResponse(
            reply: $user instanceof User
                ? "Hmm, I'm not 100% sure what you meant there! You can ask me about OBS pairing, overlays, or timers — or tell me to run something live like \"start 5m timer\" or \"check studio status\"."
                : "Hmm, I didn't quite catch that one! Try asking how to pair OBS, how Unified Stage overlays work, or ask me for lower-third and ticker copy ideas.",
            reaction: 'peek',
            links: [['label' => 'Browse Knowledge Base', 'url' => route('knowledge-base', [], false)]],
            suggestions: $this->defaultSuggestions($user, $studio, $collection),
        );
    }

    private function greetingReply(?User $user, ?ObsStudio $studio): string
    {
        if ($user instanceof User) {
            $firstName = trim(explode(' ', $user->name)[0] ?? '');
            $namePart = $firstName !== '' ? " {$firstName}" : '';
            $studioPart = $studio instanceof ObsStudio
                ? ($studio->isOnline() ? " \"{$studio->name}\" is online and ready." : " \"{$studio->name}\" is currently offline.")
                : '';

            return "Hey{$namePart}! I'm Oxy.{$studioPart} Need help with OBS, overlay copy, or want me to run a live action?";
        }

        return "Hey there! I'm Oxy. Ask me anything about connecting OBS, building stage overlays, or ask for lower-third & ticker copy!";
    }

    private function expandSynonyms(string $lower): string
    {
        $map = [
            'connect' => 'connect pair agent helper websocket',
            'pairing' => 'pairing pair token installer',
            'overlay' => 'overlay stage browser source canvas',
            'lag' => 'lag latency delay reverb',
            '00:00' => '00:00 timer countdown end action',
            'expire' => 'expire timer countdown end action',
            'shortcut' => 'shortcut macro hotkey deck',
            'mic' => 'mic audio mute volume input',
        ];

        $extra = [];
        foreach ($map as $needle => $additions) {
            if (str_contains($lower, $needle)) {
                $extra[] = $additions;
            }
        }

        return $extra === [] ? $lower : $lower.' '.implode(' ', $extra);
    }

    private function extractReadableExcerpt(string $markdown, int $sectionIndex = 1): string
    {
        $lines = preg_split('/\R/', $markdown) ?: [];
        $paragraphs = [];
        $current = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '```')) {
                if ($current !== []) {
                    $paragraphs[] = implode(' ', $current);
                    $current = [];
                }

                continue;
            }
            $cleanLine = (string) preg_replace('/[*`]/', '', $trimmed);
            $current[] = $cleanLine;
        }
        if ($current !== []) {
            $paragraphs[] = implode(' ', $current);
        }

        $pick = $paragraphs[$sectionIndex - 1] ?? $paragraphs[0] ?? '';
        if (mb_strlen($pick) > 260) {
            $pick = rtrim(mb_substr($pick, 0, 257)).'...';
        }

        return $pick;
    }

    private function isFollowUp(string $lower): bool
    {
        return $this->matchesAny($lower, ['tell me more', 'more details', 'explain more', 'what else', 'and then', 'elaborate', 'continue']);
    }

    private function resolveStudio(?User $user, ?string $studioSlug): ?ObsStudio
    {
        if (! $user instanceof User) {
            return null;
        }

        if ($studioSlug !== null && $studioSlug !== '') {
            $scoped = ObsStudio::query()->ownedBy($user)->where('slug', $studioSlug)->first();
            if ($scoped instanceof ObsStudio) {
                return $scoped;
            }
        }

        return ObsStudio::query()->ownedBy($user)->where('is_active', true)->orderBy('id')->first();
    }

    private function resolveCollection(?User $user, ?ObsStudio $studio): ?ScreenCollection
    {
        if (! $user instanceof User) {
            return null;
        }

        if ($studio instanceof ObsStudio && $studio->active_screen_collection_id) {
            $active = ScreenCollection::query()->ownedBy($user)->with('activeView')->find($studio->active_screen_collection_id);
            if ($active instanceof ScreenCollection) {
                return $active;
            }
        }

        return ScreenCollection::query()->ownedBy($user)->with('activeView')->orderBy('id')->first();
    }

    private function looksLikeLiveCommand(string $lower): bool
    {
        if (preg_match('/\b(start|pause|resume|reset|set)\b.*\btimer\b/', $lower) === 1) {
            return true;
        }

        return $this->matchesAny($lower, ['switch view', 'show lower third', 'hide lower third', 'start stream', 'stop stream', 'check studio', 'what scenes', 'list scenes']);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function defaultSuggestions(?User $user, ?ObsStudio $studio, ?ScreenCollection $collection): array
    {
        if ($user instanceof User && $collection instanceof ScreenCollection) {
            return ['Check studio status', 'Start 5m timer', 'Show lower third', 'Suggest ticker headlines'];
        }

        if ($user instanceof User && $studio instanceof ObsStudio) {
            return ['Check studio status', 'Suggest lower-third copy', 'How do I pair OBS?'];
        }

        return ['How do I pair OBS?', 'Suggest lower-third copy', 'What happens at 00:00?'];
    }

    /**
     * @param  array<int, string>  $suggestions
     * @param  array<int, array{label: string, url: string}>  $links
     * @return array{
     *     reply: string,
     *     reaction: string,
     *     executed: bool,
     *     action: string|null,
     *     suggestions: array<int, string>,
     *     links: array<int, array{label: string, url: string}>
     * }
     */
    private function makeResponse(
        string $reply,
        string $reaction = 'wave',
        bool $executed = false,
        ?string $action = null,
        array $suggestions = [],
        array $links = [],
    ): array {
        return [
            'reply' => $reply,
            'reaction' => $reaction,
            'executed' => $executed,
            'action' => $action,
            'suggestions' => $suggestions,
            'links' => $links,
        ];
    }
}
