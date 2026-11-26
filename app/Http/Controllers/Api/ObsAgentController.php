<?php

namespace App\Http\Controllers\Api;

use App\Events\ObsCommandAcknowledged;
use App\Events\ObsPreviewFrameUpdated;
use App\Events\ObsStudioStateUpdated;
use App\Http\Controllers\Controller;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Pusher\Pusher;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ObsAgentController extends Controller
{
    private const ONLINE_LEASE_TTL_SECONDS = 75;

    public function bootstrap(Request $request): JsonResponse
    {
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);
        $relayScope = (string) $request->attributes->get('obs_relay_scope', 'studio');
        $relayUser = $request->attributes->get('obs_relay_user');
        $agentInstanceId = $this->resolveAgentInstanceId($request);

        if ($isRelayMode) {
            $studios = $this->scopedRelayStudios($relayUser instanceof User ? $relayUser : null, $relayScope)
                ->map(fn (ObsStudio $studio): array => $this->formatStudioBootstrap($studio, $agentInstanceId))
                ->values();

            $relayChannel = ($relayScope === 'user' && $relayUser instanceof User)
                ? "private-obs.user.{$relayUser->id}.relay"
                : 'private-obs.relay';

            return response()->json([
                'mode' => 'relay',
                'scope' => $relayScope,
                'user_id' => $relayUser instanceof User ? $relayUser->id : null,
                'studios' => $studios,
                'reverb' => [
                    'key' => config('reverb.apps.apps.0.key'),
                    'host' => config('reverb.apps.apps.0.options.host'),
                    'port' => (int) config('reverb.apps.apps.0.options.port', 443),
                    'scheme' => config('reverb.apps.apps.0.options.scheme', 'https'),
                    'relay_channel' => $relayChannel,
                ],
            ]);
        }

        $studio = $this->resolveStudio($request);
        $formatted = $this->formatStudioBootstrap($studio, $agentInstanceId);

        return response()->json([
            'mode' => 'studio',
            'studio' => $formatted['studio'],
            'studios' => [$formatted],
            'reverb' => [
                'key' => config('reverb.apps.apps.0.key'),
                'host' => config('reverb.apps.apps.0.options.host'),
                'port' => (int) config('reverb.apps.apps.0.options.port', 443),
                'scheme' => config('reverb.apps.apps.0.options.scheme', 'https'),
                'channel' => $formatted['channel'],
                'studio_channel' => $formatted['studio_channel'],
            ],
            'pending_commands' => $formatted['pending_commands'],
        ]);
    }

    /**
     * Fallback queue fetch for OBS Agents when WebSocket delivery is interrupted or offline.
     */
    public function pendingCommands(Request $request): JsonResponse
    {
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);
        $relayScope = (string) $request->attributes->get('obs_relay_scope', 'studio');
        $relayUser = $request->attributes->get('obs_relay_user');

        if ($isRelayMode) {
            $studios = $this->scopedRelayStudios($relayUser instanceof User ? $relayUser : null, $relayScope)
                ->map(fn (ObsStudio $studio): array => [
                    'studio_id' => $studio->id,
                    'pending_commands' => $this->queryPendingCommands($studio),
                ])
                ->values();

            return response()->json(['studios' => $studios]);
        }

        $studio = $this->resolveStudio($request);
        $commands = $this->queryPendingCommands($studio);

        return response()->json([
            'studios' => [[
                'studio_id' => $studio->id,
                'pending_commands' => $commands,
            ]],
            'pending_commands' => $commands,
        ]);
    }

    public function authenticateBroadcast(Request $request): JsonResponse
    {
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);
        $relayScope = (string) $request->attributes->get('obs_relay_scope', 'studio');
        $relayUser = $request->attributes->get('obs_relay_user');

        $validated = $request->validate([
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
        ]);

        if ($isRelayMode) {
            $expectedRelayChannel = ($relayScope === 'user' && $relayUser instanceof User)
                ? "private-obs.user.{$relayUser->id}.relay"
                : 'private-obs.relay';

            $authorized = $validated['channel_name'] === $expectedRelayChannel;

            if (! $authorized && preg_match('/^private-obs\.studio\.(\d+)(?:\.commands)?$/', $validated['channel_name'], $matches)) {
                $authorized = ObsStudio::query()
                    ->where('id', (int) $matches[1])
                    ->where('is_active', true)
                    ->when($relayScope === 'user' && $relayUser instanceof User, fn ($q) => $q->ownedBy($relayUser))
                    ->exists();
            }

            if (! $authorized) {
                return response()->json(['message' => 'Unauthorized channel for Workspace Relay.'], 403);
            }
        } else {
            $studio = $this->resolveStudio($request);
            $allowedChannels = [
                "private-obs.studio.{$studio->id}.commands",
                "private-obs.studio.{$studio->id}",
            ];

            if (! in_array($validated['channel_name'], $allowedChannels, true)) {
                return response()->json(['message' => 'Unauthorized channel for this OBS Studio agent.'], 403);
            }
        }

        $pusher = new Pusher(
            auth_key: (string) config('reverb.apps.apps.0.key'),
            secret: (string) config('reverb.apps.apps.0.secret'),
            app_id: (string) config('reverb.apps.apps.0.app_id'),
        );

        $authJson = $pusher->authorizeChannel(
            channel: $validated['channel_name'],
            socket_id: $validated['socket_id'],
        );

        return response()->json(json_decode($authJson, true, 512, JSON_THROW_ON_ERROR));
    }

    public function updateState(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request);

        $validated = $request->validate([
            'studio_id' => ['nullable', 'integer'],
            'agent_instance_id' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:offline,online,streaming,recording'],
            'live_state' => ['required', 'array'],
        ]);

        $agentInstanceId = $this->resolveAgentInstanceId($request, $validated['agent_instance_id'] ?? null);
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);

        $mergedState = array_replace_recursive($studio->live_state ?? [], $validated['live_state']);

        // Replace collection arrays directly so deleted/reordered scenes, items, or inputs don't linger
        foreach (['scenes', 'sceneItems', 'sceneItemsByScene', 'audioInputs', 'allInputs', 'inputKinds', 'videoDimensions', 'sceneCollections'] as $listKey) {
            if (array_key_exists($listKey, $validated['live_state'])) {
                $mergedState[$listKey] = $validated['live_state'][$listKey];
            }
        }

        $status = $validated['status'] ?? $this->deriveStatus($mergedState);

        if ($status === 'offline') {
            if ($this->hasConflictingAgentLease($studio, $agentInstanceId, requireExplicitConflict: true)) {
                return response()->json([
                    'ok' => true,
                    'status' => $studio->status,
                    'active_on_other_agent' => true,
                    'ignored_stale_offline' => true,
                ]);
            }

            $this->clearOnlineLease($studio);
        } else {
            if ($isRelayMode && $this->isOwnedByAnotherAgent($studio, $agentInstanceId)) {
                return response()->json([
                    'ok' => true,
                    'status' => $studio->status,
                    'active_on_other_agent' => true,
                    'ignored_duplicate_relay' => true,
                ]);
            }

            $this->refreshOnlineLease($studio, $agentInstanceId);
        }

        $studio->update([
            'status' => $status,
            'last_seen_at' => now(),
            'live_state' => $mergedState,
        ]);

        $freshStudio = $studio->fresh() ?? $studio;
        $this->broadcastSafely(new ObsStudioStateUpdated($freshStudio));

        return response()->json([
            'ok' => true,
            'status' => $freshStudio->status,
        ]);
    }

    public function uploadPreviewFrame(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request);

        $validated = $request->validate([
            'studio_id' => ['nullable', 'integer'],
            'scene_name' => ['required', 'string', 'max:255'],
            'image_data' => ['required', 'string', 'max:1500000'],
        ]);

        $rawData = $validated['image_data'];
        $mime = 'image/jpeg';
        $base64 = $rawData;

        if (preg_match('/^data:(image\/(?:jpeg|jpg|png|webp));base64,(.+)$/i', $rawData, $matches)) {
            $mime = strtolower($matches[1]) === 'image/jpg' ? 'image/jpeg' : strtolower($matches[1]);
            $base64 = $matches[2];
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            return response()->json(['message' => 'Invalid preview image payload.'], 422);
        }

        $version = (string) round(microtime(true) * 1000);

        ObsCommandService::previewCacheStore()->put("obs.preview.frame.{$studio->id}", [
            'mime' => $mime,
            'base64' => $base64,
            'scene_name' => $validated['scene_name'],
            'version' => $version,
        ], now()->addSeconds(50));

        $inlineDataUri = "data:{$mime};base64,{$base64}";
        if (strlen($inlineDataUri) <= 180_000) {
            try {
                event(new ObsPreviewFrameUpdated(
                    studioId: $studio->id,
                    sceneName: $validated['scene_name'],
                    frameVersion: $version,
                    imageData: $inlineDataUri,
                ));
            } catch (Throwable) {
                $this->broadcastSafely(new ObsPreviewFrameUpdated(
                    studioId: $studio->id,
                    sceneName: $validated['scene_name'],
                    frameVersion: $version,
                ));
            }
        } else {
            $this->broadcastSafely(new ObsPreviewFrameUpdated(
                studioId: $studio->id,
                sceneName: $validated['scene_name'],
                frameVersion: $version,
            ));
        }

        return response()->json([
            'ok' => true,
            'version' => $version,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request);
        $agentInstanceId = $this->resolveAgentInstanceId($request);
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);

        $status = $request->input('status');
        if (! in_array($status, ['offline', 'online', 'streaming', 'recording'], true)) {
            $status = $studio->status === 'offline' ? 'online' : $studio->status;
        }

        if ($status === 'offline') {
            if ($this->hasConflictingAgentLease($studio, $agentInstanceId, requireExplicitConflict: false)) {
                return response()->json([
                    'ok' => true,
                    'status' => $studio->status,
                    'active_on_other_agent' => true,
                    'ignored_stale_offline' => true,
                    'server_time' => now()->toIso8601String(),
                ]);
            }

            $this->clearOnlineLease($studio);
        } else {
            if ($isRelayMode && $this->isOwnedByAnotherAgent($studio, $agentInstanceId)) {
                return response()->json([
                    'ok' => true,
                    'status' => $studio->status,
                    'active_on_other_agent' => true,
                    'ignored_duplicate_relay' => true,
                    'server_time' => now()->toIso8601String(),
                ]);
            }

            $this->refreshOnlineLease($studio, $agentInstanceId);
        }

        $studio->update([
            'status' => $status,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'status' => $status,
            'active_on_other_agent' => false,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function acknowledgeCommand(Request $request, string $commandUuid): JsonResponse
    {
        $studio = $this->resolveStudio($request);

        $commandLog = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->where('command_uuid', $commandUuid)
            ->firstOrFail();

        $validated = $request->validate([
            'studio_id' => ['nullable', 'integer'],
            'agent_instance_id' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'string', 'in:succeeded,failed'],
            'response_data' => ['nullable', 'array'],
            'error_message' => ['nullable', 'string'],
        ]);

        $agentInstanceId = $this->resolveAgentInstanceId($request, $validated['agent_instance_id'] ?? null);

        if ($commandLog->status === 'succeeded' && $validated['status'] === 'failed') {
            return response()->json([
                'ok' => true,
                'command_uuid' => $commandLog->command_uuid,
                'status' => $commandLog->status,
                'ignored_stale_failure' => true,
            ]);
        }

        $errorMessage = (string) ($validated['error_message'] ?? '');
        if (
            $validated['status'] === 'failed'
            && str_contains(strtolower($errorMessage), 'not currently connected')
            && $this->hasConflictingAgentLease($studio, $agentInstanceId, requireExplicitConflict: false)
        ) {
            return response()->json([
                'ok' => true,
                'command_uuid' => $commandLog->command_uuid,
                'status' => $commandLog->status,
                'ignored_idle_agent' => true,
            ]);
        }

        $commandLog->update([
            'status' => $validated['status'],
            'response_data' => $validated['response_data'] ?? null,
            'error_message' => $validated['error_message'] ?? null,
            'executed_at' => now(),
        ]);

        $freshLog = $commandLog->fresh() ?? $commandLog;
        $this->broadcastSafely(new ObsCommandAcknowledged($freshLog));

        return response()->json([
            'ok' => true,
            'command_uuid' => $freshLog->command_uuid,
            'status' => $freshLog->status,
        ]);
    }

    public function downloadMediaFile(Request $request, string $token): Response
    {
        $studio = $this->resolveStudio($request);
        $cacheKey = "obs.media.upload.{$token}";
        $meta = ObsCommandService::previewCacheStore()->get($cacheKey);

        if (! is_array($meta) || empty($meta['storage_path'])) {
            abort(404, 'Uploaded media token expired or not found.');
        }

        abort_if((int) ($meta['studio_id'] ?? 0) !== $studio->id, 403, 'Media upload does not belong to this studio.');

        $storagePath = (string) $meta['storage_path'];
        if (! Storage::disk('local')->exists($storagePath)) {
            abort(404, 'Staged media file missing.');
        }

        ObsCommandService::previewCacheStore()->forget($cacheKey);

        return response()
            ->download(
                Storage::disk('local')->path($storagePath),
                (string) ($meta['filename'] ?? 'image.png'),
                ['Content-Type' => (string) ($meta['mime'] ?? 'application/octet-stream')],
            )
            ->deleteFileAfterSend(true);
    }

    private function resolveStudio(Request $request): ObsStudio
    {
        $studio = $request->attributes->get('obs_studio');

        if (! $studio instanceof ObsStudio) {
            abort(422, 'Missing studio_id for multi-studio relay request.');
        }

        return $studio;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ObsStudio>
     */
    private function scopedRelayStudios(?User $relayUser, string $relayScope): \Illuminate\Database\Eloquent\Collection
    {
        return ObsStudio::query()
            ->where('is_active', true)
            ->when($relayScope === 'user' && $relayUser instanceof User, fn ($q) => $q->ownedBy($relayUser))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryPendingCommands(ObsStudio $studio): array
    {
        return ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->whereIn('status', ['queued', 'dispatched'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('id')
            ->get()
            ->map(fn (ObsCommandLog $log): array => [
                'command_uuid' => $log->command_uuid,
                'request_type' => $log->request_type,
                'request_data' => ObsCommandService::normalizeRequestDataForJson($log->request_data),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStudioBootstrap(ObsStudio $studio, ?string $agentInstanceId = null): array
    {
        return [
            'studio' => [
                'id' => $studio->id,
                'name' => $studio->name,
                'slug' => $studio->slug,
                'obs_host' => $studio->obs_host,
                'obs_port' => $studio->obs_port,
                'obs_password' => $studio->obs_password,
            ],
            'channel' => "private-obs.studio.{$studio->id}.commands",
            'studio_channel' => "private-obs.studio.{$studio->id}",
            'is_online' => $studio->isOnline(),
            'active_on_other_agent' => $this->isOwnedByAnotherAgent($studio, $agentInstanceId),
            'pending_commands' => $this->queryPendingCommands($studio),
        ];
    }

    private function broadcastSafely(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function onlineLeaseKey(ObsStudio $studio): string
    {
        return "obs.studio.{$studio->id}.online_lease";
    }

    private function resolveAgentInstanceId(Request $request, mixed $bodyValue = null): ?string
    {
        return $this->normalizeAgentInstanceId(
            $bodyValue ?? $request->input('agent_instance_id') ?? $request->header('X-Obs-Agent-Instance')
        );
    }

    private function normalizeAgentInstanceId(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? substr($trimmed, 0, 120) : null;
    }

    private function isOwnedByAnotherAgent(ObsStudio $studio, ?string $agentInstanceId): bool
    {
        if (! $studio->isOnline()) {
            return false;
        }

        $lease = Cache::get($this->onlineLeaseKey($studio));
        $leaseAgentId = is_array($lease) ? ($lease['agent_id'] ?? null) : null;

        if (! is_string($leaseAgentId) || $leaseAgentId === '') {
            return false;
        }

        if ($agentInstanceId !== null && $leaseAgentId === $agentInstanceId) {
            return false;
        }

        return true;
    }

    private function refreshOnlineLease(ObsStudio $studio, ?string $agentInstanceId): void
    {
        Cache::put(
            $this->onlineLeaseKey($studio),
            [
                'agent_id' => $agentInstanceId,
                'at' => now()->timestamp,
            ],
            now()->addSeconds(self::ONLINE_LEASE_TTL_SECONDS),
        );
    }

    private function clearOnlineLease(ObsStudio $studio): void
    {
        Cache::forget($this->onlineLeaseKey($studio));
    }

    /**
     * Protect an active online studio from being downgraded by a secondary idle PC's relay agent.
     */
    private function hasConflictingAgentLease(ObsStudio $studio, ?string $agentInstanceId, bool $requireExplicitConflict): bool
    {
        $lease = Cache::get($this->onlineLeaseKey($studio));
        $leaseAgentId = is_array($lease) ? ($lease['agent_id'] ?? null) : null;

        if ($agentInstanceId !== null && is_string($leaseAgentId) && $leaseAgentId === $agentInstanceId) {
            return false;
        }

        if (is_array($lease)) {
            if ($requireExplicitConflict) {
                return $agentInstanceId !== null && is_string($leaseAgentId) && $leaseAgentId !== $agentInstanceId;
            }

            return $studio->isOnline();
        }

        if ($requireExplicitConflict) {
            return false;
        }

        return $studio->isOnline()
            && $studio->last_seen_at !== null
            && $studio->last_seen_at->greaterThan(now()->subSeconds(self::ONLINE_LEASE_TTL_SECONDS));
    }

    /**
     * @param  array<string, mixed>  $liveState
     */
    private function deriveStatus(array $liveState): string
    {
        $outputs = $liveState['outputs'] ?? [];

        if (! empty($outputs['streaming'])) {
            return 'streaming';
        }

        if (! empty($outputs['recording'])) {
            return 'recording';
        }

        return 'online';
    }
}
