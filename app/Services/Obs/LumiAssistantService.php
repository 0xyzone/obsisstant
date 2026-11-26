<?php

namespace App\Services\Obs;

use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use App\Services\KnowledgeBaseService;

class LumiAssistantService
{
    public function __construct(
        private readonly ScreenCollectionService $collectionService,
        private readonly ObsCommandService $commandService,
    ) {}

    /**
     * Handle a natural-language question, copy request, or live Studio/OBS action for Lumi.
     *
     * @return array{
     *     reply: string,
     *     reaction: string,
     *     executed: bool,
     *     action: string|null,
     *     suggestions: array<int, string>,
     *     links: array<int, array{label: string, url: string}>
     * }
     */
    public function reply(string $message, ?User $user = null, ?string $studioSlug = null): array
    {
        $clean = trim($message);
        $lower = mb_strtolower($clean);

        $studio = $this->resolveStudio($user, $studioSlug);
        $collection = $this->resolveCollection($user, $studio);

        if ($clean === '') {
            return $this->makeResponse(
                reply: 'Ask me how Obsisstant works, ask for overlay text ideas, or tell me to run a live Studio action!',
                reaction: 'wave',
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        // 1. Copy / Overlay Text Suggestions (works for both guests and signed-in users)
        if ($this->matchesAny($lower, ['suggest', 'idea', 'copy', 'headline', 'ticker text', 'lower third text', 'write'])) {
            return $this->buildCopySuggestions($lower, $collection);
        }

        // 2. Live Studio / OBS Actions (authenticated users only)
        if ($user instanceof User) {
            $actionResult = $this->tryExecuteLiveAction($clean, $lower, $user, $studio, $collection);
            if ($actionResult !== null) {
                return $actionResult;
            }
        } elseif ($this->looksLikeLiveCommand($lower)) {
            return $this->makeResponse(
                reply: 'Sign in first so I can safely control your Studio, timers, overlays, and OBS scenes!',
                reaction: 'peek',
                links: [['label' => 'Sign in to Studio', 'url' => '/admin/login']],
                suggestions: ['How do I pair OBS?', 'Suggest lower-third copy', 'What is Unified Stage?'],
            );
        }

        // 3. Knowledge Base & OBS WebSocket v5 Q&A
        return $this->answerFromKnowledgeBase($lower, $user, $studio, $collection);
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
        // Status summary
        if ($this->matchesAny($lower, ['status', 'online', 'connected', 'what is live', 'studio state'])) {
            if (! $studio instanceof ObsStudio) {
                return $this->makeResponse(
                    reply: 'You do not have an active Studio yet. Create one in Admin Panel -> OBS Studios to get started.',
                    reaction: 'peek',
                    links: [['label' => 'Open Studios', 'url' => '/admin/obs-studios']],
                );
            }

            $onlineText = $studio->isOnline() ? 'online and connected' : 'currently offline';
            $scene = $studio->getCurrentProgramScene() ?: 'none';
            $viewName = $collection?->activeView?->name ?? 'none';

            return $this->makeResponse(
                reply: "Studio \"{$studio->name}\" is {$onlineText} (OBS Program Scene: {$scene}). Active Stage View: {$viewName}.",
                reaction: $studio->isOnline() ? 'cheer' : 'wave',
                links: [['label' => 'Open Studio', 'url' => route('studio.show', ['studio' => $studio->slug], false)]],
                suggestions: ['Start 5m timer', 'Show lower third', 'Suggest ticker headlines'],
            );
        }

        // Timer actions on active ScreenCollection
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['timer', 'countdown', 'clock'])) {
            if (preg_match('/(?:set|start)\s+(?:a\s+)?(?:timer|countdown)?\s*(?:for|to)?\s*(\d+)\s*(m|min|mins|minute|minutes|s|sec|secs|second|seconds)\b/i', $raw, $m)) {
                $amount = max(1, (int) $m[1]);
                $unit = strtolower($m[2]);
                $seconds = str_starts_with($unit, 'm') ? $amount * 60 : $amount;
                $autoStart = str_contains($lower, 'start');
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
                        ? "Done! Set and started the countdown at {$formatted}."
                        : "Done! Set the countdown timer to {$formatted}.",
                    reaction: 'cheer',
                    executed: true,
                    action: 'timer.set',
                );
            }

            if ($this->matchesAny($lower, ['start', 'resume', 'go'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'start']]);

                return $this->makeResponse('Countdown timer started!', 'cheer', true, 'timer.start');
            }

            if ($this->matchesAny($lower, ['pause', 'stop', 'halt'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'pause']]);

                return $this->makeResponse('Timer paused.', 'wave', true, 'timer.pause');
            }

            if ($this->matchesAny($lower, ['reset', 'restart'])) {
                $this->collectionService->updateRuntime($collection, ['timer' => ['action' => 'reset']]);

                return $this->makeResponse('Timer reset to full duration.', 'spin', true, 'timer.reset');
            }
        }

        // Lower Third actions
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['lower third', 'lower-third', 'banner'])) {
            if ($this->matchesAny($lower, ['hide', 'off', 'clear', 'remove'])) {
                $this->collectionService->updateRuntime($collection, ['lower_third' => ['visible' => false]]);

                return $this->makeResponse('Lower Third banner hidden.', 'wave', true, 'lower_third.hide');
            }

            if ($this->matchesAny($lower, ['show', 'display', 'on'])) {
                $patch = ['visible' => true];
                if (preg_match('/(?:show|display)\s+(?:lower[\s-]?third|banner)\s+(.+)/i', $raw, $m)) {
                    $text = trim($m[1]);
                    if ($text !== '' && ! in_array(mb_strtolower($text), ['on', 'now'], true)) {
                        $parts = array_map('trim', explode('-', $text, 2));
                        $patch['title'] = $parts[0];
                        if (! empty($parts[1])) {
                            $patch['subtitle'] = $parts[1];
                        }
                    }
                }
                $this->collectionService->updateRuntime($collection, ['lower_third' => $patch]);

                return $this->makeResponse('Lower Third is now on air!', 'cheer', true, 'lower_third.show');
            }
        }

        // Scoreboard actions
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['score', 'team a', 'team b', 'swap team'])) {
            if ($this->matchesAny($lower, ['swap'])) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['swap' => true]]);

                return $this->makeResponse('Swapped Team A and Team B sides!', 'spin', true, 'scoreboard.swap');
            }
            if ($this->matchesAny($lower, ['reset'])) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['reset_scores' => true]]);

                return $this->makeResponse('Reset scoreboard to 0 - 0.', 'wave', true, 'scoreboard.reset');
            }
            if (str_contains($lower, 'team a')) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['delta_a' => 1]]);

                return $this->makeResponse('Added +1 point to Team A!', 'cheer', true, 'scoreboard.delta_a');
            }
            if (str_contains($lower, 'team b')) {
                $this->collectionService->updateRuntime($collection, ['scoreboard' => ['delta_b' => 1]]);

                return $this->makeResponse('Added +1 point to Team B!', 'cheer', true, 'scoreboard.delta_b');
            }
        }

        // Switch Screen View by name
        if ($collection instanceof ScreenCollection && $this->matchesAny($lower, ['switch view', 'switch to', 'go to', 'show view', 'change view'])) {
            $collection->loadMissing('views');
            foreach ($collection->views as $view) {
                if ($view instanceof ScreenView && str_contains($lower, mb_strtolower($view->name))) {
                    $this->collectionService->switchView($collection, $view);

                    return $this->makeResponse(
                        reply: "Switched live stage to \"{$view->name}\"!",
                        reaction: 'spin',
                        executed: true,
                        action: 'collection.switch_view',
                    );
                }
            }
        }

        // OBS Studio Stream / Record / Scene commands
        if ($studio instanceof ObsStudio) {
            if ($this->matchesAny($lower, ['start stream', 'go live'])) {
                $this->commandService->dispatch($studio, 'StartStream', [], $user);

                return $this->makeResponse("Sent StartStream to \"{$studio->name}\"!", 'cheer', true, 'obs.StartStream');
            }
            if ($this->matchesAny($lower, ['stop stream', 'end stream'])) {
                $this->commandService->dispatch($studio, 'StopStream', [], $user);

                return $this->makeResponse("Sent StopStream to \"{$studio->name}\".", 'wave', true, 'obs.StopStream');
            }
            if ($this->matchesAny($lower, ['start record', 'start recording'])) {
                $this->commandService->dispatch($studio, 'StartRecord', [], $user);

                return $this->makeResponse("Started recording on \"{$studio->name}\"!", 'cheer', true, 'obs.StartRecord');
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

                        return $this->makeResponse("Switched OBS Program Scene to \"{$sceneName}\"!", 'spin', true, 'obs.SetCurrentProgramScene');
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
                reply: "Here are 3 clean ticker lines for {$showName}:\n• Welcome to {$showName} — live now!\n• Drop your questions in chat for the Q&A segment.\n• Up next: Main session right after the break.",
                reaction: 'dance',
                suggestions: ['Suggest lower-third copy', 'Start 5m timer'],
            );
        }

        return $this->makeResponse(
            reply: "Try these Lower Third pairs for {$showName}:\n• Badge: LIVE | Title: Alex Rivera | Subtitle: Host & Producer\n• Badge: GUEST | Title: Jordan Lee | Subtitle: Lead Engineer\n• Badge: UP NEXT | Title: Live Q&A | Subtitle: Ask in chat",
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
        $tokens = array_values(array_filter(
            preg_split('/[^a-z0-9]+/i', $lower) ?: [],
            static fn (string $t): bool => mb_strlen($t) >= 3 && ! in_array($t, ['how', 'what', 'can', 'the', 'for', 'and', 'with', 'you', 'lumi'], true),
        ));

        $bestArticle = null;
        $bestScore = 0;

        foreach (KnowledgeBaseService::getArticles() as $article) {
            $haystack = mb_strtolower($article['title'].' '.$article['summary'].' '.$article['markdown']);
            $score = 0;
            foreach ($tokens as $tok) {
                if (str_contains($haystack, $tok)) {
                    $score += str_contains(mb_strtolower($article['title']), $tok) ? 3 : 1;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestArticle = $article;
            }
        }

        if ($bestArticle !== null && $bestScore > 0) {
            return $this->makeResponse(
                reply: "{$bestArticle['title']}: {$bestArticle['summary']}",
                reaction: 'wave',
                links: [['label' => 'Read Guide', 'url' => route('knowledge-base', ['topic' => $bestArticle['slug']], false)]],
                suggestions: $this->defaultSuggestions($user, $studio, $collection),
            );
        }

        // Check OBS Request Catalog match
        foreach (KnowledgeBaseService::getCommandReference() as $cmd) {
            $cmdHay = mb_strtolower($cmd['request_type'].' '.$cmd['label'].' '.$cmd['description']);
            foreach ($tokens as $tok) {
                if (str_contains($cmdHay, $tok)) {
                    return $this->makeResponse(
                        reply: "OBS Request `{$cmd['request_type']}` ({$cmd['category']}): {$cmd['description']}",
                        reaction: 'cheer',
                        links: [['label' => 'Command Reference', 'url' => route('knowledge-base', [], false)]],
                    );
                }
            }
        }

        return $this->makeResponse(
            reply: $user instanceof User
                ? 'I can check your studio status, switch views or OBS scenes, control timers/lower thirds, or search the Knowledge Base. What would you like to do?'
                : 'I can explain how Obsisstant connects to OBS, suggest overlay copy, or point you to the right guide!',
            reaction: 'wave',
            links: [['label' => 'Knowledge Base', 'url' => route('knowledge-base', [], false)]],
            suggestions: $this->defaultSuggestions($user, $studio, $collection),
        );
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

        return $this->matchesAny($lower, ['switch view', 'show lower third', 'hide lower third', 'start stream', 'stop stream', 'check studio']);
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
