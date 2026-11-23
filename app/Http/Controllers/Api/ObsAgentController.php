<?php

namespace App\Http\Controllers\Api;

use App\Events\ObsCommandAcknowledged;
use App\Events\ObsStudioStateUpdated;
use App\Http\Controllers\Controller;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pusher\Pusher;

class ObsAgentController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        /** @var ObsStudio $studio */
        $studio = $request->attributes->get('obs_studio');

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

        return response()->json([
            'studio' => [
                'id' => $studio->id,
                'name' => $studio->name,
                'slug' => $studio->slug,
                'obs_host' => $studio->obs_host,
                'obs_port' => $studio->obs_port,
                'obs_password' => $studio->obs_password,
            ],
            'reverb' => [
                'key' => config('reverb.apps.apps.0.key'),
                'host' => config('reverb.apps.apps.0.options.host'),
                'port' => (int) config('reverb.apps.apps.0.options.port', 443),
                'scheme' => config('reverb.apps.apps.0.options.scheme', 'https'),
                'channel' => "private-obs.studio.{$studio->id}.commands",
            ],
            'pending_commands' => $pendingCommands,
        ]);
    }

    public function authenticateBroadcast(Request $request): JsonResponse
    {
        /** @var ObsStudio $studio */
        $studio = $request->attributes->get('obs_studio');

        $validated = $request->validate([
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
        ]);

        $expectedChannel = "private-obs.studio.{$studio->id}.commands";

        if ($validated['channel_name'] !== $expectedChannel) {
            return response()->json(['message' => 'Unauthorized channel for this OBS Studio agent.'], 403);
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
        /** @var ObsStudio $studio */
        $studio = $request->attributes->get('obs_studio');

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:offline,online,streaming,recording'],
            'live_state' => ['required', 'array'],
        ]);

        $mergedState = array_replace_recursive($studio->live_state ?? [], $validated['live_state']);

        // Replace collection arrays directly so deleted/reordered scenes, items, or inputs don't linger
        foreach (['scenes', 'sceneItems', 'audioInputs'] as $listKey) {
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

    public function heartbeat(Request $request): JsonResponse
    {
        /** @var ObsStudio $studio */
        $studio = $request->attributes->get('obs_studio');

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
        /** @var ObsStudio $studio */
        $studio = $request->attributes->get('obs_studio');

        $commandLog = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->where('command_uuid', $commandUuid)
            ->firstOrFail();

        $validated = $request->validate([
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

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function deriveStatus(array $state): string
    {
        $outputs = $state['outputs'] ?? [];
        if (! empty($outputs['streaming'])) {
            return 'streaming';
        }
        if (! empty($outputs['recording'])) {
            return 'recording';
        }

        return 'online';
    }
}
