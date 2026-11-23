<?php

namespace App\Services\Obs;

use App\Models\ObsStudio;
use Illuminate\Support\Collection;

class ObsRequestCatalog
{
    public const string CUSTOM_KEY = '__custom__';

    /**
     * Ordered catalog of common OBS WebSocket v5 requests shared between
     * the Filament Macro Builder and the in-app Knowledge Base.
     *
     * @return array<string, array{
     *     category: string,
     *     label: string,
     *     summary: string,
     *     fields: array<int, string>,
     *     example_payload: array<string, mixed>,
     *     requires_studio_sync?: bool,
     *     notes?: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            'SetCurrentProgramScene' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Switch Program Scene (Live Cut)',
                'summary' => 'Immediately switch the live OBS Program output to the selected scene.',
                'fields' => ['sceneName'],
                'example_payload' => ['sceneName' => 'Main Scene'],
            ],
            'SetCurrentPreviewScene' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Stage Preview Scene (Studio Mode)',
                'summary' => 'Load a scene into the Studio Mode Preview window before transitioning live.',
                'fields' => ['sceneName'],
                'example_payload' => ['sceneName' => 'Guest Interview'],
                'notes' => 'Requires Studio Mode to be enabled in OBS.',
            ],
            'TriggerStudioModeTransition' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Trigger Studio Mode Transition',
                'summary' => 'Transition the currently staged Preview scene to Program output.',
                'fields' => [],
                'example_payload' => [],
            ],
            'CreateScene' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Create OBS Scene',
                'summary' => 'Create a new empty scene with the given name inside OBS Studio.',
                'fields' => ['sceneNameCustomOnly'],
                'example_payload' => ['sceneName' => 'QA Spotlight'],
            ],
            'SetCurrentSceneTransition' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Set Active Transition',
                'summary' => 'Change the active scene transition (such as Fade, Cut, or Stinger).',
                'fields' => ['transitionName'],
                'example_payload' => ['transitionName' => 'Fade'],
            ],
            'SetCurrentSceneTransitionDuration' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Set Transition Duration (ms)',
                'summary' => 'Adjust how long the active transition takes in milliseconds.',
                'fields' => ['transitionDuration'],
                'example_payload' => ['transitionDuration' => 350],
            ],
            'SetCurrentSceneCollection' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Switch Scene Collection',
                'summary' => 'Load a different OBS Scene Collection by exact collection name.',
                'fields' => ['sceneCollectionName'],
                'example_payload' => ['sceneCollectionName' => 'Main Podcast Setup'],
            ],
            'CreateSceneCollection' => [
                'category' => 'Scenes & Transitions',
                'label' => 'Create Scene Collection',
                'summary' => 'Create and switch to a new empty OBS Scene Collection.',
                'fields' => ['sceneCollectionName'],
                'example_payload' => ['sceneCollectionName' => 'Event Broadcast'],
            ],
            'StartStream' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Start Streaming',
                'summary' => 'Begin live broadcasting to the configured OBS stream output.',
                'fields' => [],
                'example_payload' => [],
            ],
            'StopStream' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Stop Streaming',
                'summary' => 'Stop the active broadcast stream.',
                'fields' => [],
                'example_payload' => [],
            ],
            'ToggleStream' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Toggle Stream',
                'summary' => 'Start the stream if stopped, or stop it if currently live.',
                'fields' => [],
                'example_payload' => [],
            ],
            'StartRecord' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Start Recording',
                'summary' => 'Begin local disk recording in OBS Studio.',
                'fields' => [],
                'example_payload' => [],
            ],
            'StopRecord' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Stop Recording',
                'summary' => 'Finalize and save the active local recording.',
                'fields' => [],
                'example_payload' => [],
            ],
            'ToggleRecord' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Toggle Recording',
                'summary' => 'Start local recording if idle, or stop it if currently recording.',
                'fields' => [],
                'example_payload' => [],
            ],
            'ToggleRecordPause' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Pause / Resume Recording',
                'summary' => 'Toggle recording pause while keeping a single output file.',
                'fields' => [],
                'example_payload' => [],
            ],
            'SaveReplayBuffer' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Save Replay Buffer Clip',
                'summary' => 'Write the rolling instant-replay buffer to disk.',
                'fields' => [],
                'example_payload' => [],
                'notes' => 'Replay Buffer must already be running in OBS.',
            ],
            'StartReplayBuffer' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Start Replay Buffer',
                'summary' => 'Arm the OBS rolling replay buffer in memory.',
                'fields' => [],
                'example_payload' => [],
            ],
            'StopReplayBuffer' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Stop Replay Buffer',
                'summary' => 'Shut down the rolling replay buffer.',
                'fields' => [],
                'example_payload' => [],
            ],
            'ToggleVirtualCam' => [
                'category' => 'Stream, Record & Outputs',
                'label' => 'Toggle Virtual Camera',
                'summary' => 'Turn the OBS Virtual Camera feed on or off for video calls.',
                'fields' => [],
                'example_payload' => [],
            ],
            'SetInputMute' => [
                'category' => 'Audio Mixer',
                'label' => 'Mute or Unmute Audio Input',
                'summary' => 'Explicitly set an OBS audio input to muted or live.',
                'fields' => ['inputName', 'inputMuted'],
                'example_payload' => ['inputName' => 'Mic/Aux', 'inputMuted' => true],
            ],
            'ToggleInputMute' => [
                'category' => 'Audio Mixer',
                'label' => 'Toggle Audio Input Mute',
                'summary' => 'Flip the mute status on an OBS audio input channel.',
                'fields' => ['inputName'],
                'example_payload' => ['inputName' => 'Mic/Aux'],
            ],
            'SetInputVolume' => [
                'category' => 'Audio Mixer',
                'label' => 'Set Audio Volume (dB)',
                'summary' => 'Set exact audio gain in decibels (-100 dB to +26 dB, 0 dB = unity).',
                'fields' => ['inputName', 'inputVolumeDb'],
                'example_payload' => ['inputName' => 'Mic/Aux', 'inputVolumeDb' => -6.0],
            ],
            'SetSceneItemEnabled' => [
                'category' => 'Sources & Studio Mode',
                'label' => 'Show or Hide Scene Source',
                'summary' => 'Toggle visibility of a specific source item inside an OBS scene.',
                'fields' => ['sceneName', 'sceneItemId', 'sceneItemEnabled'],
                'example_payload' => [
                    'sceneName' => 'Main Scene',
                    'sceneItemId' => 4,
                    'sceneItemEnabled' => true,
                ],
                'notes' => 'OBS identifies items per scene using their numeric sceneItemId.',
            ],
            'SetStudioModeEnabled' => [
                'category' => 'Sources & Studio Mode',
                'label' => 'Enable or Disable Studio Mode',
                'summary' => 'Switch OBS Studio Mode (dual Preview/Program) on or off.',
                'fields' => ['studioModeEnabled'],
                'example_payload' => ['studioModeEnabled' => true],
            ],
            'PressInputPropertiesButton' => [
                'category' => 'Sources & Studio Mode',
                'label' => 'Press Input Button (Refresh Browser Source)',
                'summary' => 'Trigger a button in an OBS input property panel, such as Browser Source cache refresh.',
                'fields' => ['inputName', 'propertyName'],
                'example_payload' => [
                    'inputName' => 'Obsisstant - Live Lower Thirds',
                    'propertyName' => 'refreshnocache',
                ],
            ],
        ];
    }

    /**
     * Grouped options array suitable for Filament Select components.
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedSelectOptions(): array
    {
        $grouped = [];

        foreach (self::definitions() as $requestType => $definition) {
            $grouped[$definition['category']][$requestType] = "{$definition['label']} ({$requestType})";
        }

        $grouped['Advanced'] = [
            self::CUSTOM_KEY => 'Custom OBS WebSocket v5 Request (Manual Entry)',
        ];

        return $grouped;
    }

    /**
     * Collect synced scenes, audio inputs, and scene items from either the chosen studio or active studios.
     *
     * @return array{
     *     scenes: array<string, string>,
     *     audio_inputs: array<string, string>,
     *     scene_items: array<string, string>
     * }
     */
    public static function syncedOptions(?int $studioId = null): array
    {
        $query = ObsStudio::query()->where('is_active', true)->orderBy('id');

        if ($studioId !== null) {
            $studio = $query->where('id', $studioId)->first();
            $studios = $studio instanceof ObsStudio ? collect([$studio]) : collect();
        } else {
            /** @var Collection<int, ObsStudio> $studios */
            $studios = $query->get();
        }

        $scenes = [];
        $audioInputs = [];
        $sceneItems = [];

        foreach ($studios as $studio) {
            foreach ($studio->getScenes() as $scene) {
                $name = trim((string) ($scene['sceneName'] ?? ''));
                if ($name !== '') {
                    $scenes[$name] = $name;
                }
            }

            foreach ($studio->getAudioInputs() as $input) {
                $name = trim((string) ($input['inputName'] ?? ''));
                if ($name !== '') {
                    $audioInputs[$name] = $name;
                }
            }

            foreach ($studio->getSceneItems() as $item) {
                $id = $item['sceneItemId'] ?? null;
                $sourceName = trim((string) ($item['sourceName'] ?? ''));
                if ($id !== null && $sourceName !== '') {
                    $sceneItems[(string) $id] = "{$sourceName} (#{$id})";
                }
            }
        }

        return [
            'scenes' => $scenes,
            'audio_inputs' => $audioInputs,
            'scene_items' => $sceneItems,
        ];
    }

    /**
     * Check whether a catalog request expects a specific field key.
     */
    public static function commandHasField(?string $preset, string $field): bool
    {
        if (! $preset || $preset === self::CUSTOM_KEY) {
            return false;
        }

        $definitions = self::definitions();

        return in_array($field, $definitions[$preset]['fields'] ?? [], true);
    }

    /**
     * Summary helper text for a selected request type.
     */
    public static function describe(?string $preset): string
    {
        if (! $preset) {
            return 'Choose any OBS WebSocket v5 action or pick Custom for plugin/vendor commands.';
        }

        if ($preset === self::CUSTOM_KEY) {
            return 'Enter any exact OBS WebSocket v5 request name and key/value payload pairs.';
        }

        $definition = self::definitions()[$preset] ?? null;
        if (! is_array($definition)) {
            return 'Enter any exact OBS WebSocket v5 request name.';
        }

        $summary = $definition['summary'];
        if (! empty($definition['notes'])) {
            $summary .= ' Note: '.$definition['notes'];
        }

        return $summary;
    }

    /**
     * Unpack stored DB macro steps into form UI state for EditObsMacro.
     *
     * @param  array<int, array<string, mixed>>|null  $steps
     * @return array<int, array<string, mixed>>
     */
    public static function unpackSteps(?array $steps, ?int $studioId = null): array
    {
        if (! is_array($steps)) {
            return [];
        }

        $synced = self::syncedOptions($studioId);
        $definitions = self::definitions();

        return array_values(array_map(function ($step) use ($synced, $definitions): array {
            if (! is_array($step)) {
                return [];
            }

            $type = (string) ($step['type'] ?? 'obs_command');

            if ($type === 'screen_update') {
                $rawState = is_array($step['state'] ?? null) ? $step['state'] : [];
                $stringified = [];

                foreach ($rawState as $key => $value) {
                    if (is_bool($value)) {
                        $stringified[$key] = $value ? 'true' : 'false';
                    } elseif (is_scalar($value)) {
                        $stringified[$key] = (string) $value;
                    }
                }

                return [
                    ...$step,
                    'type' => 'screen_update',
                    'state' => $stringified,
                ];
            }

            if ($type !== 'obs_command') {
                return $step;
            }

            $requestType = trim((string) ($step['request_type'] ?? ''));
            $requestData = is_array($step['request_data'] ?? null) ? $step['request_data'] : [];

            if (! isset($definitions[$requestType])) {
                return [
                    'type' => 'obs_command',
                    'command_preset' => self::CUSTOM_KEY,
                    'custom_request_type' => $requestType,
                    'custom_request_data' => self::stringifyKeyValues($requestData),
                ];
            }

            $unpacked = [
                'type' => 'obs_command',
                'command_preset' => $requestType,
            ];

            $fields = $definitions[$requestType]['fields'];

            if (in_array('sceneName', $fields, true)) {
                $sceneName = (string) ($requestData['sceneName'] ?? '');
                if ($sceneName !== '' && isset($synced['scenes'][$sceneName])) {
                    $unpacked['param_scene_choice'] = $sceneName;
                    $unpacked['param_scene_custom'] = null;
                } else {
                    $unpacked['param_scene_choice'] = $synced['scenes'] === [] ? null : self::CUSTOM_KEY;
                    $unpacked['param_scene_custom'] = $sceneName;
                }
            }

            if (in_array('sceneNameCustomOnly', $fields, true)) {
                $unpacked['param_scene_custom_only'] = (string) ($requestData['sceneName'] ?? '');
            }

            if (in_array('inputName', $fields, true)) {
                $inputName = (string) ($requestData['inputName'] ?? '');
                if ($inputName !== '' && isset($synced['audio_inputs'][$inputName])) {
                    $unpacked['param_input_choice'] = $inputName;
                    $unpacked['param_input_custom'] = null;
                } else {
                    $unpacked['param_input_choice'] = $synced['audio_inputs'] === [] ? null : self::CUSTOM_KEY;
                    $unpacked['param_input_custom'] = $inputName;
                }
            }

            if (in_array('sceneItemId', $fields, true)) {
                $itemId = $requestData['sceneItemId'] ?? null;
                $itemIdStr = $itemId === null ? '' : (string) $itemId;
                if ($itemIdStr !== '' && isset($synced['scene_items'][$itemIdStr])) {
                    $unpacked['param_scene_item_choice'] = $itemIdStr;
                    $unpacked['param_scene_item_custom'] = null;
                } else {
                    $unpacked['param_scene_item_choice'] = $synced['scene_items'] === [] ? null : self::CUSTOM_KEY;
                    $unpacked['param_scene_item_custom'] = $itemIdStr;
                }
            }

            if (in_array('inputMuted', $fields, true)) {
                $unpacked['param_inputMuted'] = (bool) ($requestData['inputMuted'] ?? true);
            }

            if (in_array('inputVolumeDb', $fields, true)) {
                $unpacked['param_inputVolumeDb'] = (float) ($requestData['inputVolumeDb'] ?? 0.0);
            }

            if (in_array('sceneItemEnabled', $fields, true)) {
                $unpacked['param_sceneItemEnabled'] = (bool) ($requestData['sceneItemEnabled'] ?? true);
            }

            if (in_array('studioModeEnabled', $fields, true)) {
                $unpacked['param_studioModeEnabled'] = (bool) ($requestData['studioModeEnabled'] ?? true);
            }

            if (in_array('transitionName', $fields, true)) {
                $unpacked['param_transitionName'] = (string) ($requestData['transitionName'] ?? 'Fade');
            }

            if (in_array('transitionDuration', $fields, true)) {
                $unpacked['param_transitionDuration'] = (int) ($requestData['transitionDuration'] ?? 300);
            }

            if (in_array('propertyName', $fields, true)) {
                $unpacked['param_propertyName'] = (string) ($requestData['propertyName'] ?? 'refreshnocache');
            }

            if (in_array('sceneCollectionName', $fields, true)) {
                $unpacked['param_sceneCollectionName'] = (string) ($requestData['sceneCollectionName'] ?? '');
            }

            return $unpacked;
        }, $steps));
    }

    /**
     * Pack form UI steps cleanly back into typed obs_macros JSON payload steps.
     *
     * @param  array<int|string, array<string, mixed>>|null  $steps
     * @return array<int, array<string, mixed>>
     */
    public static function packSteps(?array $steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        $definitions = self::definitions();
        $packed = [];

        foreach ($steps as $step) {
            if (! is_array($step)) {
                continue;
            }

            $type = (string) ($step['type'] ?? 'obs_command');

            if ($type === 'screen_update') {
                $rawState = is_array($step['state'] ?? null) ? $step['state'] : [];
                $typedState = [];

                foreach ($rawState as $key => $value) {
                    $cleanKey = trim((string) $key);
                    if ($cleanKey === '') {
                        continue;
                    }
                    $typedState[$cleanKey] = self::coerceScalar($value);
                }

                $packed[] = [
                    'type' => 'screen_update',
                    'screen_slug' => $step['screen_slug'] ?? null,
                    'state' => $typedState,
                ];

                continue;
            }

            if ($type === 'delay_ms') {
                $packed[] = [
                    'type' => 'delay_ms',
                    'delay_ms' => max(0, (int) ($step['delay_ms'] ?? 500)),
                ];

                continue;
            }

            if ($type !== 'obs_command') {
                continue;
            }

            $preset = (string) ($step['command_preset'] ?? '');

            // Backwards compatibility if an unmodified step already has request_type without command_preset
            if ($preset === '' && ! empty($step['request_type'])) {
                $packed[] = [
                    'type' => 'obs_command',
                    'request_type' => (string) $step['request_type'],
                    'request_data' => is_array($step['request_data'] ?? null) ? $step['request_data'] : [],
                ];

                continue;
            }

            if ($preset === self::CUSTOM_KEY) {
                $rawCustomData = is_array($step['custom_request_data'] ?? null) ? $step['custom_request_data'] : [];
                $typedData = [];
                foreach ($rawCustomData as $key => $value) {
                    $cleanKey = trim((string) $key);
                    if ($cleanKey !== '') {
                        $typedData[$cleanKey] = self::coerceScalar($value);
                    }
                }

                $packed[] = [
                    'type' => 'obs_command',
                    'request_type' => trim((string) ($step['custom_request_type'] ?? '')),
                    'request_data' => $typedData,
                ];

                continue;
            }

            $fields = $definitions[$preset]['fields'] ?? [];
            $requestData = [];

            if (in_array('sceneName', $fields, true)) {
                $choice = (string) ($step['param_scene_choice'] ?? '');
                $sceneName = ($choice === '' || $choice === self::CUSTOM_KEY)
                    ? trim((string) ($step['param_scene_custom'] ?? ''))
                    : $choice;

                if ($sceneName !== '') {
                    $requestData['sceneName'] = $sceneName;
                }
            }

            if (in_array('sceneNameCustomOnly', $fields, true)) {
                $sceneName = trim((string) ($step['param_scene_custom_only'] ?? ''));
                if ($sceneName !== '') {
                    $requestData['sceneName'] = $sceneName;
                }
            }

            if (in_array('inputName', $fields, true)) {
                $choice = (string) ($step['param_input_choice'] ?? '');
                $inputName = ($choice === '' || $choice === self::CUSTOM_KEY)
                    ? trim((string) ($step['param_input_custom'] ?? ''))
                    : $choice;

                if ($inputName !== '') {
                    $requestData['inputName'] = $inputName;
                }
            }

            if (in_array('sceneItemId', $fields, true)) {
                $choice = (string) ($step['param_scene_item_choice'] ?? '');
                $rawItemId = ($choice === '' || $choice === self::CUSTOM_KEY)
                    ? ($step['param_scene_item_custom'] ?? null)
                    : $choice;

                if ($rawItemId !== null && $rawItemId !== '') {
                    $requestData['sceneItemId'] = (int) $rawItemId;
                }
            }

            if (in_array('inputMuted', $fields, true)) {
                $requestData['inputMuted'] = (bool) ($step['param_inputMuted'] ?? true);
            }

            if (in_array('inputVolumeDb', $fields, true)) {
                $requestData['inputVolumeDb'] = round((float) ($step['param_inputVolumeDb'] ?? 0.0), 2);
            }

            if (in_array('sceneItemEnabled', $fields, true)) {
                $requestData['sceneItemEnabled'] = (bool) ($step['param_sceneItemEnabled'] ?? true);
            }

            if (in_array('studioModeEnabled', $fields, true)) {
                $requestData['studioModeEnabled'] = (bool) ($step['param_studioModeEnabled'] ?? true);
            }

            if (in_array('transitionName', $fields, true)) {
                $transitionName = trim((string) ($step['param_transitionName'] ?? ''));
                if ($transitionName !== '') {
                    $requestData['transitionName'] = $transitionName;
                }
            }

            if (in_array('transitionDuration', $fields, true)) {
                $requestData['transitionDuration'] = max(0, (int) ($step['param_transitionDuration'] ?? 300));
            }

            if (in_array('propertyName', $fields, true)) {
                $propertyName = trim((string) ($step['param_propertyName'] ?? 'refreshnocache'));
                if ($propertyName !== '') {
                    $requestData['propertyName'] = $propertyName;
                }
            }

            if (in_array('sceneCollectionName', $fields, true)) {
                $collectionName = trim((string) ($step['param_sceneCollectionName'] ?? ''));
                if ($collectionName !== '') {
                    $requestData['sceneCollectionName'] = $collectionName;
                }
            }

            $packed[] = [
                'type' => 'obs_command',
                'request_type' => $preset,
                'request_data' => $requestData,
            ];
        }

        return $packed;
    }

    protected static function coerceScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if (is_numeric($trimmed)) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected static function stringifyKeyValues(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $result[(string) $key] = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $result[(string) $key] = (string) $value;
            }
        }

        return $result;
    }
}
