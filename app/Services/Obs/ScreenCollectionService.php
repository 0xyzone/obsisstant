<?php

namespace App\Services\Obs;

use App\Events\ScreenCollectionUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsStudio;
use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ScreenCollectionService
{
    public function __construct(
        private readonly ObsCommandService $commandService,
    ) {}

    /**
     * Build the single serialized payload used by Stage Player, Control Center, Mobile Deck, and Reverb.
     *
     * @return array<string, mixed>
     */
    public static function buildStagePayload(ScreenCollection $collection): array
    {
        $collection->loadMissing(['views', 'assets']);

        $userAssets = ScreenAsset::query()
            ->where('user_id', $collection->user_id)
            ->where(function ($query) use ($collection): void {
                $query->where('screen_collection_id', $collection->id)
                    ->orWhereNull('screen_collection_id');
            })
            ->latest('id')
            ->get();

        $referencedIds = [];
        $owner = $collection->user;
        if ($owner instanceof User) {
            $referencedIds = array_flip(app(ServerMediaCleanerService::class)->collectReferencedAssetIds($owner));
        }

        $assetsMap = [];
        $serializedAssets = [];
        foreach ($userAssets as $asset) {
            $item = [
                'id' => $asset->id,
                'screen_collection_id' => $asset->screen_collection_id,
                'name' => $asset->name,
                'type' => $asset->type,
                'mime_type' => $asset->mime_type,
                'size_bytes' => $asset->size_bytes,
                'in_use' => isset($referencedIds[$asset->id]),
                'url' => $asset->getUrl(false),
                'absolute_url' => $asset->getUrl(true),
            ];
            $assetsMap[$asset->id] = $item;
            $serializedAssets[] = $item;
        }

        $views = $collection->views->map(static function (ScreenView $view) use ($assetsMap): array {
            $layers = array_map(static function (mixed $layer) use ($assetsMap): mixed {
                if (! is_array($layer)) {
                    return $layer;
                }
                $props = is_array($layer['props'] ?? null) ? $layer['props'] : [];
                if (! empty($props['asset_id']) && isset($assetsMap[(int) $props['asset_id']])) {
                    $asset = $assetsMap[(int) $props['asset_id']];
                    $props['url'] = $asset['url'];
                    if (empty($props['media_kind'])) {
                        $props['media_kind'] = in_array($asset['type'], ['video', 'stinger'], true) ? 'video' : 'image';
                    }
                }
                $layer['props'] = $props;

                return $layer;
            }, $view->layers ?? []);

            $bgAssetUrl = ! empty($view->background_asset_id) && isset($assetsMap[(int) $view->background_asset_id])
                ? $assetsMap[(int) $view->background_asset_id]['url']
                : null;

            return [
                'id' => $view->id,
                'screen_collection_id' => $view->screen_collection_id,
                'name' => $view->name,
                'slug' => $view->slug,
                'sort_order' => $view->sort_order,
                'background_mode' => $view->background_mode,
                'background_color' => $view->background_color,
                'background_asset_id' => $view->background_asset_id,
                'background_asset_url' => $bgAssetUrl,
                'background_asset_type' => ! empty($view->background_asset_id) && isset($assetsMap[(int) $view->background_asset_id])
                    ? $assetsMap[(int) $view->background_asset_id]['type']
                    : null,
                'theme_override' => $view->theme_override,
                'transition_override' => $view->transition_override,
                'layers' => array_values($layers),
                'logic_rules' => $view->logic_rules ?? ['on_timer_end_action' => 'none'],
            ];
        })->values()->all();

        $mergedTheme = $collection->getMergedTheme();
        $themeBgAssetId = ! empty($mergedTheme['background_asset_id']) ? (int) $mergedTheme['background_asset_id'] : null;
        if ($themeBgAssetId !== null && isset($assetsMap[$themeBgAssetId])) {
            $mergedTheme['background_asset_id'] = $themeBgAssetId;
            $mergedTheme['background_asset_url'] = $assetsMap[$themeBgAssetId]['url'];
            $mergedTheme['background_asset_type'] = $assetsMap[$themeBgAssetId]['type'];
        } else {
            $mergedTheme['background_asset_id'] = null;
            $mergedTheme['background_asset_url'] = null;
            $mergedTheme['background_asset_type'] = null;
        }

        $defaultTransition = $collection->getMergedTransition();
        if (! empty($defaultTransition['stinger_asset_id']) && isset($assetsMap[(int) $defaultTransition['stinger_asset_id']])) {
            $defaultTransition['stinger_url'] = $assetsMap[(int) $defaultTransition['stinger_asset_id']]['url'];
        } else {
            $defaultTransition['stinger_url'] = null;
        }

        $nowUtc = Carbon::now('UTC');
        $serverNowMs = (int) $nowUtc->getPreciseTimestamp(3);
        $runtimeState = $collection->getMergedRuntimeState();
        $runtimeState['timer'] = self::hydrateTimerSnapshot($runtimeState['timer'], $serverNowMs);

        return [
            'collection_id' => $collection->id,
            'obs_studio_id' => $collection->obs_studio_id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'public_token' => $collection->public_token,
            'stage_url' => $collection->getStageUrl(true),
            'stage_path' => $collection->getStageUrl(false),
            'logic_trigger_url' => route('obs.collection.logic-trigger', ['token' => $collection->public_token], false),
            'width' => $collection->width,
            'height' => $collection->height,
            'active_view_id' => $collection->active_view_id ?? ($views[0]['id'] ?? null),
            'obs_scene_name' => $collection->obs_scene_name,
            'obs_source_name' => $collection->obs_source_name,
            'theme' => $mergedTheme,
            'default_transition' => $defaultTransition,
            'runtime_state' => $runtimeState,
            'deck_layout' => $collection->getNormalizedDeckLayout(),
            'views' => $views,
            'assets' => $serializedAssets,
            'server_now_ms' => $serverNowMs,
            'updated_at' => $collection->updated_at?->toIso8601String() ?? $nowUtc->toIso8601String(),
        ];
    }

    /**
     * Enrich serialized timer state with server-relative remaining/elapsed milliseconds so clients are immune to wall-clock skew.
     *
     * @param  array<string, mixed>  $timer
     * @return array<string, mixed>
     */
    private static function hydrateTimerSnapshot(array $timer, int $serverNowMs): array
    {
        $mode = (string) ($timer['mode'] ?? 'countdown');
        $status = (string) ($timer['status'] ?? 'stopped');
        $durationSecs = max(0, (int) ($timer['duration_seconds'] ?? 300));
        $remainingSecs = max(0, (int) ($timer['remaining_seconds'] ?? $durationSecs));
        $elapsedBaseSecs = max(0, (int) ($timer['elapsed_base_seconds'] ?? 0));

        $remainingMs = $remainingSecs * 1000;
        $elapsedMs = $elapsedBaseSecs * 1000;

        if ($status === 'running' && $mode === 'countdown' && ! empty($timer['target_at'])) {
            $targetMs = (int) Carbon::parse((string) $timer['target_at'])->utc()->getPreciseTimestamp(3);
            $remainingMs = max(0, $targetMs - $serverNowMs);
            $remainingSecs = (int) ceil($remainingMs / 1000);
        } elseif ($status === 'running' && $mode === 'countup' && ! empty($timer['started_at'])) {
            $startedMs = (int) Carbon::parse((string) $timer['started_at'])->utc()->getPreciseTimestamp(3);
            $elapsedMs = ($elapsedBaseSecs * 1000) + max(0, $serverNowMs - $startedMs);
        }

        $timer['server_now_ms'] = $serverNowMs;
        $timer['remaining_ms'] = $remainingMs;
        $timer['remaining_seconds'] = $remainingSecs;
        $timer['elapsed_ms'] = $elapsedMs;

        return $timer;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCollection(User $user, array $data, ?ObsStudio $studio = null): ScreenCollection
    {
        return DB::transaction(function () use ($user, $data, $studio): ScreenCollection {
            $name = trim((string) ($data['name'] ?? 'New Event Collection'));
            $baseSlug = Str::slug($name) ?: 'event';
            $slug = ScreenCollection::query()->where('slug', $baseSlug)->exists()
                ? $baseSlug.'-'.Str::lower(Str::random(5))
                : $baseSlug;

            $collection = ScreenCollection::query()->create([
                'user_id' => $user->id,
                'obs_studio_id' => $studio?->id ?? ($data['obs_studio_id'] ?? null),
                'name' => $name,
                'slug' => $slug,
                'public_token' => Str::random(32),
                'width' => max(320, (int) ($data['width'] ?? 1920)),
                'height' => max(180, (int) ($data['height'] ?? 1080)),
                'theme' => array_merge(ScreenCollectionPresetService::defaultTheme(), is_array($data['theme'] ?? null) ? $data['theme'] : []),
                'default_transition' => array_merge(ScreenCollectionPresetService::defaultTransition(), is_array($data['default_transition'] ?? null) ? $data['default_transition'] : []),
                'runtime_state' => ScreenCollectionPresetService::defaultRuntimeState(),
                'obs_scene_name' => trim((string) ($data['obs_scene_name'] ?? 'Obsisstant Stage')) ?: 'Obsisstant Stage',
                'obs_source_name' => trim((string) ($data['obs_source_name'] ?? 'Obsisstant - Unified Screen')) ?: 'Obsisstant - Unified Screen',
            ]);

            $withStarter = (bool) ($data['with_starter_views'] ?? true);
            $firstView = null;

            if ($withStarter) {
                foreach (ScreenCollectionPresetService::starterViews() as $preset) {
                    $view = ScreenView::query()->create([
                        'screen_collection_id' => $collection->id,
                        'user_id' => $user->id,
                        'name' => $preset['name'],
                        'slug' => $preset['slug'],
                        'sort_order' => $preset['sort_order'],
                        'background_mode' => $preset['background_mode'],
                        'transition_override' => $preset['transition_override'] ?? null,
                        'layers' => $preset['layers'] ?? [],
                        'logic_rules' => $preset['logic_rules'] ?? ['on_timer_end_action' => 'none'],
                    ]);
                    $firstView ??= $view;
                }
            } else {
                $firstView = ScreenView::query()->create([
                    'screen_collection_id' => $collection->id,
                    'user_id' => $user->id,
                    'name' => 'Main View',
                    'slug' => 'main-view',
                    'sort_order' => 1,
                    'background_mode' => 'theme',
                    'layers' => [
                        ScreenCollectionPresetService::makeDefaultLayer('camera_cutout', 'Camera Cutout'),
                    ],
                    'logic_rules' => ['on_timer_end_action' => 'none'],
                ]);
            }

            if ($firstView instanceof ScreenView) {
                $collection->forceFill(['active_view_id' => $firstView->id])->save();
            }

            if ($studio instanceof ObsStudio && $studio->active_screen_collection_id === null) {
                $studio->forceFill(['active_screen_collection_id' => $collection->id])->save();
            }

            return $collection->fresh(['views', 'assets']) ?? $collection;
        });
    }

    /**
     * Switch the currently displayed Screen View on a Collection with an explicit or inherited transition.
     *
     * @param  array<string, mixed>|null  $transitionOverride
     */
    public function switchView(ScreenCollection $collection, ScreenView $view, ?array $transitionOverride = null): ScreenCollection
    {
        $fromViewId = $collection->active_view_id;
        $runtime = $collection->getMergedRuntimeState();
        $baseTransition = $collection->getMergedTransition();

        if (is_array($view->transition_override) && ! empty($view->transition_override['type'])) {
            $baseTransition = array_merge($baseTransition, $view->transition_override);
        }

        if (is_array($transitionOverride) && ! empty($transitionOverride['type']) && $transitionOverride['type'] !== 'default') {
            $baseTransition = array_merge($baseTransition, array_filter(
                $transitionOverride,
                static fn (mixed $v): bool => $v !== null && $v !== '',
            ));
        }

        $type = (string) ($baseTransition['type'] ?? 'fade');
        $durationMs = $type === 'cut' ? 0 : max(80, min(3000, (int) ($baseTransition['duration_ms'] ?? 450)));
        $swapPointMs = max(0, min($durationMs, (int) ($baseTransition['swap_point_ms'] ?? (int) round($durationMs * 0.5))));

        $stingerUrl = null;
        $stingerId = $baseTransition['stinger_asset_id'] ?? null;
        if (! empty($stingerId)) {
            $stinger = ScreenAsset::query()
                ->where('user_id', $collection->user_id)
                ->find((int) $stingerId);
            if ($stinger instanceof ScreenAsset) {
                $stingerUrl = $stinger->getUrl(false);
            }
        }

        $prevSeq = (int) ($runtime['last_transition']['seq'] ?? 0);
        $runtime['last_transition'] = [
            'seq' => $prevSeq + 1,
            'from_view_id' => $fromViewId,
            'to_view_id' => $view->id,
            'type' => $type,
            'duration_ms' => $durationMs,
            'swap_point_ms' => $swapPointMs,
            'stinger_url' => $stingerUrl,
            'sweep_color' => (string) ($baseTransition['sweep_color'] ?? ($collection->getMergedTheme()['accent_color'] ?? '#CDAD69')),
            'triggered_at' => now()->toIso8601String(),
        ];

        $collection->forceFill([
            'active_view_id' => $view->id,
            'runtime_state' => $runtime,
        ])->save();

        $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
        $this->broadcastSafely($fresh);

        return $fresh;
    }

    /**
     * Mutate live timer, scoreboard, lower third, ticker, alerts, or layer visibility on a Collection.
     *
     * @param  array<string, mixed>  $input
     */
    public function updateRuntime(ScreenCollection $collection, array $input): ScreenCollection
    {
        $runtime = $collection->getMergedRuntimeState();

        if (is_array($input['timer'] ?? null)) {
            $timerAction = (string) ($input['timer']['action'] ?? 'update');
            if (in_array($timerAction, ['start', 'reset', 'set'], true) && ! empty($runtime['timer']['auto_hidden_layer_ids'])) {
                $autoHidden = array_values(array_filter(
                    is_array($runtime['timer']['auto_hidden_layer_ids']) ? $runtime['timer']['auto_hidden_layer_ids'] : [],
                    static fn (mixed $id): bool => is_string($id) && $id !== '',
                ));
                $runtime['hidden_layers'] = array_values(array_diff(
                    is_array($runtime['hidden_layers'] ?? null) ? $runtime['hidden_layers'] : [],
                    $autoHidden,
                ));
                $runtime['timer']['auto_hidden_layer_ids'] = [];
            }

            $runtime['timer'] = $this->applyTimerAction($runtime['timer'], $input['timer']);
        }

        if (is_array($input['scoreboard'] ?? null)) {
            $runtime['scoreboard'] = $this->applyScoreboardPatch($runtime['scoreboard'], $input['scoreboard']);
        }

        if (is_array($input['lower_third'] ?? null)) {
            $runtime['lower_third'] = array_merge($runtime['lower_third'], $input['lower_third']);
        }

        if (is_array($input['ticker'] ?? null)) {
            $tickerInput = $input['ticker'];
            if (isset($tickerInput['items']) && is_string($tickerInput['items'])) {
                $tickerInput['items'] = array_values(array_filter(array_map('trim', explode("\n", $tickerInput['items']))));
            }
            $runtime['ticker'] = array_merge($runtime['ticker'], $tickerInput);
        }

        if (is_array($input['alert'] ?? null)) {
            $runtime['alert'] = array_merge($runtime['alert'], $input['alert']);
        }

        if (is_array($input['toggle_layer'] ?? null) && ! empty($input['toggle_layer']['layer_id'])) {
            $layerId = (string) $input['toggle_layer']['layer_id'];
            $hidden = array_values(array_filter($runtime['hidden_layers'], static fn (mixed $id): bool => is_string($id) && $id !== ''));
            $currentlyHidden = in_array($layerId, $hidden, true);
            $nextVisible = array_key_exists('visible', $input['toggle_layer'])
                ? (bool) $input['toggle_layer']['visible']
                : $currentlyHidden;

            if ($nextVisible) {
                $hidden = array_values(array_diff($hidden, [$layerId]));
            } elseif (! $currentlyHidden) {
                $hidden[] = $layerId;
            }
            $runtime['hidden_layers'] = $hidden;
        }

        $collection->forceFill(['runtime_state' => $runtime])->save();

        $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
        $this->broadcastSafely($fresh);

        return $fresh;
    }

    /**
     * Evaluate and fire automatic timer-end view logic idempotently so multiple open Browser Sources never double-trigger.
     *
     * @return array<string, mixed>
     */
    public function fireTimerEndLogic(ScreenCollection $collection, int $expectedViewId, ?string $marker = null): array
    {
        $lockKey = "obs.stage.timer_end.{$collection->id}.{$expectedViewId}.".($marker ?: 'latest');
        if (! Cache::add($lockKey, 1, now()->addSeconds(15))) {
            return ['handled' => false, 'reason' => 'debounced'];
        }

        $collection->refresh();
        if ((int) $collection->active_view_id !== $expectedViewId) {
            return ['handled' => false, 'reason' => 'view_changed'];
        }

        $view = ScreenView::query()
            ->where('screen_collection_id', $collection->id)
            ->find($expectedViewId);

        if (! $view instanceof ScreenView) {
            return ['handled' => false, 'reason' => 'missing_view'];
        }

        $rules = is_array($view->logic_rules) ? $view->logic_rules : [];
        $action = (string) ($rules['on_timer_end_action'] ?? 'none');
        if ($action === 'none' || $action === '') {
            return ['handled' => false, 'reason' => 'no_action'];
        }

        $runtime = $collection->getMergedRuntimeState();
        $durationSecs = max(0, (int) ($runtime['timer']['duration_seconds'] ?? 300));
        $runtime['timer']['status'] = 'stopped';
        $runtime['timer']['remaining_seconds'] = 0;
        $runtime['timer']['target_at'] = null;
        $collection->forceFill(['runtime_state' => $runtime])->save();

        if ($action === 'switch_view' && ! empty($rules['on_timer_end_target_view_id'])) {
            $targetView = ScreenView::query()
                ->where('screen_collection_id', $collection->id)
                ->find((int) $rules['on_timer_end_target_view_id']);

            if ($targetView instanceof ScreenView && $targetView->id !== $view->id) {
                $updated = $this->switchView($collection, $targetView);

                return [
                    'handled' => true,
                    'action' => 'switch_view',
                    'target_view_id' => $targetView->id,
                    'collection' => self::buildStagePayload($updated),
                ];
            }
        }

        if ($action === 'show_alert') {
            $updated = $this->updateRuntime($collection, [
                'alert' => [
                    'visible' => true,
                    'title' => trim((string) ($rules['on_timer_end_alert_title'] ?? '')) ?: 'Time Up',
                    'message' => trim((string) ($rules['on_timer_end_alert_message'] ?? '')) ?: 'Countdown finished.',
                ],
            ]);

            return [
                'handled' => true,
                'action' => 'show_alert',
                'collection' => self::buildStagePayload($updated),
            ];
        }

        if ($action === 'show_lower_third') {
            $l3Patch = ['visible' => true];
            $badge = trim((string) ($rules['on_timer_end_l3_badge'] ?? ''));
            $title = trim((string) ($rules['on_timer_end_l3_title'] ?? ''));
            $subtitle = trim((string) ($rules['on_timer_end_l3_subtitle'] ?? ''));
            if ($badge !== '') {
                $l3Patch['badge'] = $badge;
            }
            if ($title !== '') {
                $l3Patch['title'] = $title;
            }
            if ($subtitle !== '') {
                $l3Patch['subtitle'] = $subtitle;
            }

            $updated = $this->updateRuntime($collection, ['lower_third' => $l3Patch]);

            return [
                'handled' => true,
                'action' => 'show_lower_third',
                'collection' => self::buildStagePayload($updated),
            ];
        }

        if ($action === 'hide_timer') {
            $timerLayerIds = [];
            foreach ($view->layers ?? [] as $layer) {
                if (is_array($layer) && ($layer['type'] ?? '') === 'timer' && ! empty($layer['id'])) {
                    $timerLayerIds[] = (string) $layer['id'];
                }
            }

            $hidden = array_values(array_unique(array_merge(
                array_values(array_filter($runtime['hidden_layers'] ?? [], static fn (mixed $id): bool => is_string($id) && $id !== '')),
                $timerLayerIds,
            )));
            $runtime['hidden_layers'] = $hidden;
            $runtime['timer']['auto_hidden_layer_ids'] = $timerLayerIds;
            $collection->forceFill(['runtime_state' => $runtime])->save();

            $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
            $this->broadcastSafely($fresh);

            return [
                'handled' => true,
                'action' => 'hide_timer',
                'hidden_layer_ids' => $timerLayerIds,
                'collection' => self::buildStagePayload($fresh),
            ];
        }

        if ($action === 'reset_timer') {
            $updated = $this->updateRuntime($collection, [
                'timer' => [
                    'action' => 'reset',
                    'duration_seconds' => $durationSecs,
                ],
            ]);

            return [
                'handled' => true,
                'action' => 'reset_timer',
                'collection' => self::buildStagePayload($updated),
            ];
        }

        if ($action === 'restart_timer') {
            $restartSecs = max(1, $durationSecs > 0 ? $durationSecs : 60);
            $updated = $this->updateRuntime($collection, [
                'timer' => [
                    'action' => 'set',
                    'duration_seconds' => $restartSecs,
                    'auto_start' => true,
                ],
            ]);

            return [
                'handled' => true,
                'action' => 'restart_timer',
                'collection' => self::buildStagePayload($updated),
            ];
        }

        $obsStudio = $this->resolveStudioForCollection($collection);

        if ($action === 'obs_switch_scene' && $obsStudio instanceof ObsStudio) {
            $sceneName = trim((string) ($rules['on_timer_end_obs_scene'] ?? ''));
            if ($sceneName !== '') {
                $log = $this->commandService->dispatch(
                    studio: $obsStudio,
                    requestType: 'SetCurrentProgramScene',
                    requestData: ['sceneName' => $sceneName],
                    user: $collection->user,
                );
                $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
                $this->broadcastSafely($fresh);

                return [
                    'handled' => true,
                    'action' => 'obs_switch_scene',
                    'scene_name' => $sceneName,
                    'command_uuid' => $log->command_uuid,
                    'collection' => self::buildStagePayload($fresh),
                ];
            }
        }

        $simpleObsCommands = [
            'obs_start_stream' => 'StartStream',
            'obs_stop_stream' => 'StopStream',
            'obs_start_record' => 'StartRecord',
            'obs_stop_record' => 'StopRecord',
        ];

        if (isset($simpleObsCommands[$action]) && $obsStudio instanceof ObsStudio) {
            $requestType = $simpleObsCommands[$action];
            $log = $this->commandService->dispatch(
                studio: $obsStudio,
                requestType: $requestType,
                requestData: [],
                user: $collection->user,
            );
            $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
            $this->broadcastSafely($fresh);

            return [
                'handled' => true,
                'action' => $action,
                'request_type' => $requestType,
                'command_uuid' => $log->command_uuid,
                'collection' => self::buildStagePayload($fresh),
            ];
        }

        if ($action === 'run_macro' && $obsStudio instanceof ObsStudio && ! empty($rules['on_timer_end_macro_id'])) {
            $macro = ObsMacro::query()
                ->where('user_id', $collection->user_id)
                ->find((int) $rules['on_timer_end_macro_id']);

            if ($macro instanceof ObsMacro) {
                $results = $this->commandService->executeMacro($obsStudio, $macro, $collection->user);
                $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
                $this->broadcastSafely($fresh);

                return [
                    'handled' => true,
                    'action' => 'run_macro',
                    'macro_id' => $macro->id,
                    'results' => $results,
                    'collection' => self::buildStagePayload($fresh),
                ];
            }
        }

        $this->broadcast($collection);

        return ['handled' => false, 'reason' => 'unsupported_action'];
    }

    private function resolveStudioForCollection(ScreenCollection $collection): ?ObsStudio
    {
        if ($collection->obs_studio_id !== null) {
            $linked = $collection->studio()->first();
            if ($linked instanceof ObsStudio) {
                return $linked;
            }
        }

        return ObsStudio::query()
            ->where('user_id', $collection->user_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function pushStageToObs(ObsStudio $studio, ScreenCollection $collection, ?User $user = null): ObsCommandLog
    {
        $sceneName = trim((string) ($collection->obs_scene_name ?: 'Obsisstant Stage'));
        $sourceName = trim((string) ($collection->obs_source_name ?: 'Obsisstant - Unified Screen'));

        return $this->commandService->dispatch(
            studio: $studio,
            requestType: 'obsisstant.PushBrowserScreen',
            requestData: [
                'collectionId' => $collection->id,
                'sceneName' => $sceneName,
                'inputName' => $sourceName,
                'url' => $collection->getStageUrl(true),
                'width' => $collection->width,
                'height' => $collection->height,
                'rerouteAudio' => true,
            ],
            user: $user,
        );
    }

    public function broadcast(ScreenCollection $collection): void
    {
        $fresh = $collection->fresh(['views', 'assets']) ?? $collection;
        $this->broadcastSafely($fresh);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    private function applyTimerAction(array $current, array $patch): array
    {
        $action = (string) ($patch['action'] ?? 'update');
        $previousMode = (string) ($current['mode'] ?? 'countdown');
        $mode = (string) ($patch['mode'] ?? $previousMode);
        $now = Carbon::now('UTC');
        $nowMs = (int) $now->getPreciseTimestamp(3);

        if (array_key_exists('label', $patch)) {
            $current['label'] = (string) $patch['label'];
        }
        $current['mode'] = $mode;

        if ($action === 'update' && $mode !== $previousMode && ($current['status'] ?? '') === 'running') {
            if ($mode === 'countup') {
                $current['started_at'] = $now->toIso8601ZuluString('millisecond');
                $current['target_at'] = null;
            } elseif ($mode === 'countdown') {
                $rem = max(1, (int) ($current['remaining_seconds'] ?? $current['duration_seconds'] ?? 300));
                $current['remaining_seconds'] = $rem;
                $current['target_at'] = $now->copy()->addSeconds($rem)->toIso8601ZuluString('millisecond');
                $current['started_at'] = null;
            } else {
                $current['target_at'] = null;
                $current['started_at'] = null;
            }

            return $current;
        }

        if ($action === 'set') {
            $seconds = max(0, (int) ($patch['duration_seconds'] ?? $patch['seconds'] ?? $current['duration_seconds'] ?? 300));
            $current['duration_seconds'] = $seconds;
            $current['remaining_seconds'] = $seconds;
            $current['elapsed_base_seconds'] = 0;
            $current['status'] = ! empty($patch['auto_start']) ? 'running' : 'stopped';
            $current['target_at'] = $current['status'] === 'running' && $mode === 'countdown'
                ? $now->copy()->addSeconds($seconds)->toIso8601ZuluString('millisecond')
                : null;
            $current['started_at'] = $current['status'] === 'running' && $mode === 'countup'
                ? $now->toIso8601ZuluString('millisecond')
                : null;

            return $current;
        }

        if ($action === 'start') {
            if ($mode === 'countup') {
                if (($current['status'] ?? '') === 'running' && ! empty($current['started_at'])) {
                    return $current;
                }
                $current['status'] = 'running';
                $current['started_at'] = $now->toIso8601ZuluString('millisecond');
                $current['target_at'] = null;
            } else {
                if (($current['status'] ?? '') === 'running' && ! empty($current['target_at'])) {
                    $existingTargetMs = (int) Carbon::parse((string) $current['target_at'])->utc()->getPreciseTimestamp(3);
                    if ($existingTargetMs > $nowMs) {
                        return $current;
                    }
                }
                $remaining = max(0, (int) ($current['remaining_seconds'] ?? $current['duration_seconds'] ?? 300));
                if ($remaining === 0) {
                    $remaining = max(10, (int) ($current['duration_seconds'] ?? 300));
                }
                $current['remaining_seconds'] = $remaining;
                $current['status'] = 'running';
                $current['target_at'] = $now->copy()->addSeconds($remaining)->toIso8601ZuluString('millisecond');
                $current['started_at'] = null;
            }

            return $current;
        }

        if ($action === 'pause') {
            if (($current['status'] ?? '') === 'running') {
                if ($mode === 'countup' && ! empty($current['started_at'])) {
                    $startedMs = (int) Carbon::parse((string) $current['started_at'])->utc()->getPreciseTimestamp(3);
                    $elapsed = max(0, (int) floor(($nowMs - $startedMs) / 1000));
                    $current['elapsed_base_seconds'] = (int) ($current['elapsed_base_seconds'] ?? 0) + $elapsed;
                } elseif (! empty($current['target_at'])) {
                    $targetMs = (int) Carbon::parse((string) $current['target_at'])->utc()->getPreciseTimestamp(3);
                    $current['remaining_seconds'] = max(0, (int) ceil(($targetMs - $nowMs) / 1000));
                }
            }
            $current['status'] = 'paused';
            $current['target_at'] = null;
            $current['started_at'] = null;

            return $current;
        }

        if ($action === 'reset') {
            $dur = max(0, (int) ($patch['duration_seconds'] ?? $current['duration_seconds'] ?? 300));
            $current['duration_seconds'] = $dur;
            $current['remaining_seconds'] = $dur;
            $current['elapsed_base_seconds'] = 0;
            $current['status'] = 'stopped';
            $current['target_at'] = null;
            $current['started_at'] = null;

            return $current;
        }

        if ($action === 'add_seconds') {
            $delta = (int) ($patch['seconds'] ?? 60);
            if (($current['status'] ?? '') === 'running' && $mode === 'countdown' && ! empty($current['target_at'])) {
                $target = Carbon::parse((string) $current['target_at'])->utc()->addSeconds($delta);
                $targetMs = (int) $target->getPreciseTimestamp(3);
                if ($targetMs <= $nowMs) {
                    $current['remaining_seconds'] = 0;
                    $current['target_at'] = $now->toIso8601ZuluString('millisecond');
                } else {
                    $remSecs = max(0, (int) ceil(($targetMs - $nowMs) / 1000));
                    $current['remaining_seconds'] = $remSecs;
                    $current['duration_seconds'] = max($remSecs, (int) ($current['duration_seconds'] ?? 0));
                    $current['target_at'] = $target->toIso8601ZuluString('millisecond');
                }
            } elseif ($mode === 'countup') {
                $current['elapsed_base_seconds'] = max(0, (int) ($current['elapsed_base_seconds'] ?? 0) + $delta);
            } else {
                $next = max(0, (int) ($current['remaining_seconds'] ?? 0) + $delta);
                $current['remaining_seconds'] = $next;
                $current['duration_seconds'] = max($next, (int) ($current['duration_seconds'] ?? 0));
            }

            return $current;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    private function applyScoreboardPatch(array $current, array $patch): array
    {
        if (! empty($patch['swap'])) {
            [$current['team_a_name'], $current['team_b_name']] = [$current['team_b_name'] ?? 'Team B', $current['team_a_name'] ?? 'Team A'];
            [$current['team_a_tag'], $current['team_b_tag']] = [$current['team_b_tag'] ?? 'B', $current['team_a_tag'] ?? 'A'];
            [$current['team_a_score'], $current['team_b_score']] = [(int) ($current['team_b_score'] ?? 0), (int) ($current['team_a_score'] ?? 0)];
            [$current['team_a_color'], $current['team_b_color']] = [$current['team_b_color'] ?? '#4CC38A', $current['team_a_color'] ?? '#CDAD69'];
            [$current['team_a_logo'], $current['team_b_logo']] = [$current['team_b_logo'] ?? '', $current['team_a_logo'] ?? ''];

            return $current;
        }

        if (! empty($patch['reset_scores'])) {
            $current['team_a_score'] = 0;
            $current['team_b_score'] = 0;

            return $current;
        }

        if (isset($patch['delta_a'])) {
            $current['team_a_score'] = max(0, (int) ($current['team_a_score'] ?? 0) + (int) $patch['delta_a']);
        }
        if (isset($patch['delta_b'])) {
            $current['team_b_score'] = max(0, (int) ($current['team_b_score'] ?? 0) + (int) $patch['delta_b']);
        }

        foreach (['match_label', 'round_label', 'team_a_name', 'team_a_tag', 'team_a_color', 'team_a_logo', 'team_b_name', 'team_b_tag', 'team_b_color', 'team_b_logo'] as $field) {
            if (array_key_exists($field, $patch)) {
                $current[$field] = (string) $patch[$field];
            }
        }
        foreach (['team_a_score', 'team_b_score'] as $scoreField) {
            if (array_key_exists($scoreField, $patch)) {
                $current[$scoreField] = max(0, (int) $patch[$scoreField]);
            }
        }

        return $current;
    }

    private function broadcastSafely(ScreenCollection $collection): void
    {
        try {
            event(new ScreenCollectionUpdated($collection));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
