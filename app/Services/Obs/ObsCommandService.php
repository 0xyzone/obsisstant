<?php

namespace App\Services\Obs;

use App\Events\ObsCommandDispatched;
use App\Events\ObsScreenUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Support\Str;
use stdClass;

class ObsCommandService
{
    /**
     * Ensure empty associative payloads (such as inputSettings or sceneItemTransform)
     * serialize to JSON objects `{}` instead of JSON arrays `[]` for OBS WebSocket v5.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function normalizeRequestDataForJson(?array $data): stdClass
    {
        if (! is_array($data) || $data === []) {
            return new stdClass;
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            if (in_array((string) $key, ['inputSettings', 'sceneItemTransform'], true) && $value === []) {
                $normalized[$key] = new stdClass;
            } elseif ((string) $key === 'requests' && is_array($value)) {
                $normalized[$key] = array_map(static function (mixed $req): mixed {
                    if (is_array($req)) {
                        $req['requestData'] = self::normalizeRequestDataForJson(
                            is_array($req['requestData'] ?? null) ? $req['requestData'] : []
                        );
                    }

                    return $req;
                }, $value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return (object) $normalized;
    }

    /**
     * @param  array<string, mixed>  $requestData
     */
    public function dispatch(
        ObsStudio $studio,
        string $requestType,
        array $requestData = [],
        ?User $user = null,
    ): ObsCommandLog {
        $log = ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'user_id' => $user?->id,
            'command_uuid' => (string) Str::uuid(),
            'request_type' => $requestType,
            'request_data' => $requestData,
            'status' => 'dispatched',
        ]);

        event(new ObsCommandDispatched($log));

        return $log;
    }

    public function pushScreenToObs(
        ObsStudio $studio,
        ObsScreen $screen,
        ?string $sceneName = null,
        ?User $user = null,
    ): ObsCommandLog {
        $targetScene = trim((string) ($sceneName ?: ($screen->obs_scene_name ?: $screen->name)));
        $sourceName = trim((string) ($screen->obs_source_name ?: 'Obsisstant - '.$screen->name));

        return $this->dispatch(
            studio: $studio,
            requestType: 'obsisstant.PushBrowserScreen',
            requestData: [
                'screenId' => $screen->id,
                'sceneName' => $targetScene,
                'inputName' => $sourceName,
                'url' => $screen->getOverlayUrl(),
                'width' => $screen->width,
                'height' => $screen->height,
            ],
            user: $user,
        );
    }

    /**
     * @param  array<string, mixed>  $statePatch
     */
    public function updateScreenState(ObsScreen $screen, array $statePatch): ObsScreen
    {
        $screen->update([
            'state' => array_merge($screen->getMergedState(), $statePatch),
        ]);

        $fresh = $screen->fresh() ?? $screen;
        event(new ObsScreenUpdated($fresh));

        return $fresh;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function executeMacro(ObsStudio $studio, ObsMacro $macro, ?User $user = null): array
    {
        $results = [];
        $queuedObsSteps = [];

        foreach ($macro->steps ?? [] as $step) {
            if (! is_array($step)) {
                continue;
            }

            $type = $step['type'] ?? 'obs_command';

            if ($type === 'screen_update') {
                $screen = null;
                if (! empty($step['screen_id'])) {
                    $screen = ObsScreen::query()->find($step['screen_id']);
                } elseif (! empty($step['screen_slug'])) {
                    $screen = ObsScreen::query()->where('slug', $step['screen_slug'])->first();
                }

                if ($screen instanceof ObsScreen && is_array($step['state'] ?? null)) {
                    $this->updateScreenState($screen, $step['state']);
                    $results[] = ['type' => 'screen_update', 'screen_id' => $screen->id, 'status' => 'broadcasted'];
                }

                continue;
            }

            if ($type === 'delay_ms') {
                $queuedObsSteps[] = [
                    'requestType' => 'Sleep',
                    'requestData' => ['sleepMillis' => max(0, (int) ($step['delay_ms'] ?? 250))],
                ];

                continue;
            }

            if ($type === 'obs_command' && ! empty($step['request_type'])) {
                $queuedObsSteps[] = [
                    'requestType' => (string) $step['request_type'],
                    'requestData' => is_array($step['request_data'] ?? null) ? $step['request_data'] : [],
                ];
            }
        }

        if (count($queuedObsSteps) === 1 && $queuedObsSteps[0]['requestType'] !== 'Sleep') {
            $log = $this->dispatch(
                studio: $studio,
                requestType: $queuedObsSteps[0]['requestType'],
                requestData: $queuedObsSteps[0]['requestData'],
                user: $user,
            );
            $results[] = ['type' => 'obs_command', 'command_uuid' => $log->command_uuid];
        } elseif (count($queuedObsSteps) > 1) {
            $log = $this->dispatch(
                studio: $studio,
                requestType: 'obsisstant.ExecuteBatch',
                requestData: ['requests' => $queuedObsSteps],
                user: $user,
            );
            $results[] = ['type' => 'obs_batch', 'command_uuid' => $log->command_uuid, 'count' => count($queuedObsSteps)];
        }

        return $results;
    }
}
