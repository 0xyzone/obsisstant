<?php

namespace App\Http\Controllers\Api;

use App\Events\ObsCommandAcknowledged;
use App\Events\ObsPreviewFrameUpdated;
use App\Events\ObsStudioStateUpdated;
use App\Http\Controllers\Controller;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Pusher\Pusher;

class ObsAgentController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);

        if ($isRelayMode) {
            $studios = ObsStudio::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(fn (ObsStudio $studio): array => $this->formatStudioBootstrap($studio))
                ->values();

            return response()->json([
                'mode' => 'relay',
                'studios' => $studios,
                'reverb' => [
                    'key' => config('reverb.apps.apps.0.key'),
                    'host' => config('reverb.apps.apps.0.options.host'),
                    'port' => (int) config('reverb.apps.apps.0.options.port', 443),
                    'scheme' => config('reverb.apps.apps.0.options.scheme', 'https'),
                    'relay_channel' => 'private-obs.relay',
                ],
            ]);
        }

        $studio = $this->resolveStudio($request);
        $formatted = $this->formatStudioBootstrap($studio);

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
            ],
            'pending_commands' => $formatted['pending_commands'],
        ]);
    }

    public function authenticateBroadcast(Request $request): JsonResponse
    {
        $isRelayMode = (bool) $request->attributes->get('obs_relay_mode', false);

        $validated = $request->validate([
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
        ]);

        if ($isRelayMode) {
            $authorized = $validated['channel_name'] === 'private-obs.relay';

            if (! $authorized && preg_match('/^private-obs\.studio\.(\d+)\.commands$/', $validated['channel_name'], $matches)) {
                $authorized = ObsStudio::query()
                    ->where('id', (int) $matches[1])
                    ->where('is_active', true)
                    ->exists();
            }

            if (! $authorized) {
                return response()->json(['message' => 'Unauthorized channel for Workspace Relay.'], 403);
            }
        } else {
            $studio = $this->resolveStudio($request);
            $expectedChannel = "private-obs.studio.{$studio->id}.commands";

            if ($validated['channel_name'] !== $expectedChannel) {
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
            'status' => ['nullable', 'string', 'in:offline,online,streaming,recording'],
            'live_state' => ['required', 'array'],
        ]);

        $mergedState = array_replace_recursive($studio->live_state ?? [], $validated['live_state']);

        // Replace collection arrays directly so deleted/reordered scenes, items, or inputs don't linger
        foreach (['scenes', 'sceneItems', 'sceneItemsByScene', 'audioInputs', 'allInputs', 'inputKinds', 'videoDimensions', 'sceneCollections'] as $listKey) {
            if (array_key_exists($listKey, $validated['live_state'])) {
                $mergedState[$listKey] = $validated['live_state'][$listKey];
            }
        }

        $status = $validated['status'] ?? $this->deriveStatus($mergedState);

        $studio->update([
            'status' => $status,
            'last_seen_at' => now(),
            'live_state' => $mergedState,
        ]);

        $freshStudio = $studio->fresh() ?? $studio;
        event(new ObsStudioStateUpdated($freshStudio));

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

        Cache::put("obs.preview.frame.{$studio->id}", [
            'mime' => $mime,
            'base64' => $base64,
            'scene_name' => $validated['scene_name'],
            'version' => $version,
        ], now()->addSeconds(45));

        event(new ObsPreviewFrameUpdated(
            studioId: $studio->id,
            sceneName: $validated['scene_name'],
            frameVersion: $version,
        ));

        return response()->json([
            'ok' => true,
            'version' => $version,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request);

        $status = $request->input('status');
        if (! in_array($status, ['offline', 'online', 'streaming', 'recording'], true)) {
            $status = $studio->status === 'offline' ? 'online' : $studio->status;
        }

        $studio->update([
            'status' => $status,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
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
            'status' => ['required', 'string', 'in:succeeded,failed'],
            'response_data' => ['nullable', 'array'],
            'error_message' => ['nullable', 'string'],
        ]);

        $commandLog->update([
            'status' => $validated['status'],
            'response_data' => $validated['response_data'] ?? null,
            'error_message' => $validated['error_message'] ?? null,
            'executed_at' => now(),
        ]);

        $freshLog = $commandLog->fresh() ?? $commandLog;
        event(new ObsCommandAcknowledged($freshLog));

        return response()->json([
            'ok' => true,
            'command_uuid' => $freshLog->command_uuid,
            'status' => $freshLog->status,
        ]);
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
     * @return array<string, mixed>
     */
    private function formatStudioBootstrap(ObsStudio $studio): array
    {
        /** @var Collection<int, array<string, mixed>> $pendingCommands */
        $pendingCommands = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->whereIn('status', ['queued', 'dispatched'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('id')
            ->get()
            ->map(fn (ObsCommandLog $log): array => [
                'command_uuid' => $log->command_uuid,
                'request_type' => $log->request_type,
                'request_data' => $log->request_data ?? [],
            ])
            ->values();

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
            'pending_commands' => $pendingCommands,
        ];
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
