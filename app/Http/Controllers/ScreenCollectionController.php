<?php

namespace App\Http\Controllers;

use App\Models\ObsStudio;
use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\Obs\ScreenCollectionPresetService;
use App\Services\Obs\ScreenCollectionService;
use App\Services\ReverbStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScreenCollectionController extends Controller
{
    public function updateControlMode(
        Request $request,
        ObsStudio $studio,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'control_mode' => ['nullable', 'string', 'in:obs,screens'],
            'active_screen_collection_id' => ['nullable', 'integer'],
        ]);

        $nextCollectionId = $studio->active_screen_collection_id;

        if (array_key_exists('active_screen_collection_id', $validated)) {
            if ($validated['active_screen_collection_id'] === null) {
                $nextCollectionId = null;
            } else {
                $target = ScreenCollection::query()
                    ->ownedBy($user)
                    ->findOrFail((int) $validated['active_screen_collection_id']);
                $nextCollectionId = $target->id;
            }
        }

        if (($validated['control_mode'] ?? $studio->control_mode) === 'screens' && $nextCollectionId === null) {
            $existing = ScreenCollection::query()
                ->ownedBy($user)
                ->where(fn ($q) => $q->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
                ->orderBy('name')
                ->first();

            if (! $existing instanceof ScreenCollection) {
                $existing = $collectionService->createCollection($user, [
                    'name' => "{$studio->name} Stage",
                    'with_starter_views' => true,
                ], $studio);
            }

            $nextCollectionId = $existing->id;
        }

        $studio->forceFill([
            'control_mode' => $validated['control_mode'] ?? $studio->control_mode,
            'active_screen_collection_id' => $nextCollectionId,
        ])->save();

        $activeCollection = $nextCollectionId
            ? ScreenCollection::query()->with(['views', 'assets'])->find($nextCollectionId)
            : null;

        return response()->json([
            'ok' => true,
            'control_mode' => $studio->control_mode,
            'active_screen_collection_id' => $studio->active_screen_collection_id,
            'collection' => $activeCollection instanceof ScreenCollection
                ? ScreenCollectionService::buildStagePayload($activeCollection)
                : null,
        ]);
    }

    public function index(Request $request, ObsStudio $studio): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);

        $collections = ScreenCollection::query()
            ->ownedBy($user)
            ->where(fn ($q) => $q->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->with(['views', 'assets'])
            ->orderBy('name')
            ->get()
            ->map(static fn (ScreenCollection $col): array => ScreenCollectionService::buildStagePayload($col))
            ->values();

        return response()->json([
            'ok' => true,
            'control_mode' => $studio->control_mode,
            'active_screen_collection_id' => $studio->active_screen_collection_id,
            'collections' => $collections,
        ]);
    }

    public function store(
        Request $request,
        ObsStudio $studio,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'width' => ['nullable', 'integer', 'min:320', 'max:7680'],
            'height' => ['nullable', 'integer', 'min:180', 'max:4320'],
            'obs_scene_name' => ['nullable', 'string', 'max:180'],
            'obs_source_name' => ['nullable', 'string', 'max:180'],
            'with_starter_views' => ['nullable', 'boolean'],
            'bind_studio_only' => ['nullable', 'boolean'],
            'activate' => ['nullable', 'boolean'],
        ]);

        $targetStudio = ! empty($validated['bind_studio_only']) ? $studio : $studio;

        $collection = $collectionService->createCollection(
            user: $user,
            data: [
                ...$validated,
                'obs_studio_id' => array_key_exists('bind_studio_only', $validated) && ! $validated['bind_studio_only']
                    ? null
                    : $targetStudio->id,
            ],
            studio: $studio,
        );

        if (! empty($validated['activate'])) {
            $studio->forceFill(['active_screen_collection_id' => $collection->id])->save();
        }

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection),
        ], 201);
    }

    public function builder(Request $request, ObsStudio $studio, ScreenCollection $collection): View
    {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        return view('studio.collection-builder', [
            'currentVersion' => ChangelogService::getCurrentVersion(),
            'studio' => $studio,
            'collection' => $collection,
            'collectionPayload' => ScreenCollectionService::buildStagePayload($collection),
            'layerTypes' => ScreenCollectionPresetService::layerTypeOptions(),
            'transitionTypes' => ScreenCollectionPresetService::transitionOptions(),
            'echoConfig' => ReverbStatusService::clientEchoConfig(),
        ]);
    }

    public function state(Request $request, ObsStudio $studio, ScreenCollection $collection): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        return response()->json(ScreenCollectionService::buildStagePayload($collection));
    }

    public function update(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'width' => ['sometimes', 'integer', 'min:320', 'max:7680'],
            'height' => ['sometimes', 'integer', 'min:180', 'max:4320'],
            'obs_scene_name' => ['sometimes', 'required', 'string', 'max:180'],
            'obs_source_name' => ['sometimes', 'required', 'string', 'max:180'],
            'theme' => ['sometimes', 'array'],
            'default_transition' => ['sometimes', 'array'],
        ]);

        $updates = [];
        foreach (['name', 'width', 'height', 'obs_scene_name', 'obs_source_name'] as $scalar) {
            if (array_key_exists($scalar, $validated)) {
                $updates[$scalar] = $validated[$scalar];
            }
        }
        if (array_key_exists('theme', $validated)) {
            $updates['theme'] = array_merge($collection->getMergedTheme(), $validated['theme']);
        }
        if (array_key_exists('default_transition', $validated)) {
            $updates['default_transition'] = array_merge($collection->getMergedTransition(), $validated['default_transition']);
        }

        $collection->forceFill($updates)->save();
        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ]);
    }

    public function destroy(Request $request, ObsStudio $studio, ScreenCollection $collection): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $collection->delete();

        return response()->json(['ok' => true]);
    }

    public function switchView(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'view_id' => ['required', 'integer'],
            'transition' => ['nullable', 'array'],
            'transition.type' => ['nullable', 'string', 'max:40'],
            'transition.duration_ms' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'transition.swap_point_ms' => ['nullable', 'integer', 'min:0', 'max:3000'],
            'transition.stinger_asset_id' => ['nullable', 'integer'],
            'transition.sweep_color' => ['nullable', 'string', 'max:40'],
        ]);

        $view = ScreenView::query()
            ->where('screen_collection_id', $collection->id)
            ->findOrFail((int) $validated['view_id']);

        $updated = $collectionService->switchView(
            collection: $collection,
            view: $view,
            transitionOverride: $validated['transition'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($updated),
        ]);
    }

    public function updateRuntime(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'timer' => ['nullable', 'array'],
            'scoreboard' => ['nullable', 'array'],
            'lower_third' => ['nullable', 'array'],
            'ticker' => ['nullable', 'array'],
            'alert' => ['nullable', 'array'],
            'toggle_layer' => ['nullable', 'array'],
        ]);

        $updated = $collectionService->updateRuntime($collection, $validated);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($updated),
        ]);
    }

    public function updateDeckLayout(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        if ($request->boolean('reset')) {
            $collection->forceFill(['deck_layout' => null])->save();
            $collectionService->broadcast($collection);

            return response()->json([
                'ok' => true,
                'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
            ]);
        }

        $validated = $request->validate([
            'columns' => ['nullable', 'integer', 'min:0', 'max:6'],
            'tiles' => ['required', 'array', 'max:48'],
            'tiles.*.id' => ['nullable', 'string', 'max:80'],
            'tiles.*.type' => ['required', 'string', 'in:switch_view,timer,scoreboard,lower_third,alert,ticker,layer_toggle,obs_action'],
            'tiles.*.label' => ['required', 'string', 'max:80'],
            'tiles.*.subtitle' => ['nullable', 'string', 'max:120'],
            'tiles.*.color' => ['nullable', 'string', 'in:neutral,gold,emerald,crimson,cobalt,violet'],
            'tiles.*.span' => ['nullable', 'integer', 'in:1,2'],
            'tiles.*.config' => ['nullable', 'array'],
            'tiles.*.config.*' => ['nullable'],
            'tiles.*.config.transition_duration_ms' => ['nullable', 'integer', 'min:0', 'max:3000'],
        ]);

        $sanitizedTiles = [];
        foreach ($validated['tiles'] as $index => $tile) {
            $tileConfig = is_array($tile['config'] ?? null) ? $tile['config'] : [];
            if ($tile['type'] === 'switch_view' && array_key_exists('transition_duration_ms', $tileConfig) && $tileConfig['transition_duration_ms'] !== null && $tileConfig['transition_duration_ms'] !== '') {
                $txType = (string) ($tileConfig['transition_type'] ?? 'default');
                $tileConfig['transition_duration_ms'] = $txType === 'cut'
                    ? 0
                    : max(80, min(3000, (int) $tileConfig['transition_duration_ms']));
            }

            $sanitizedTiles[] = [
                'id' => trim((string) ($tile['id'] ?? '')) ?: 'tile_'.($index + 1).'_'.Str::lower(Str::random(5)),
                'type' => (string) $tile['type'],
                'label' => trim((string) $tile['label']),
                'subtitle' => trim((string) ($tile['subtitle'] ?? '')),
                'color' => (string) ($tile['color'] ?? 'neutral'),
                'span' => (int) ($tile['span'] ?? 1) === 2 ? 2 : 1,
                'config' => $tileConfig,
            ];
        }

        $collection->forceFill([
            'deck_layout' => [
                'columns' => max(0, min(6, (int) ($validated['columns'] ?? 0))),
                'tiles' => $sanitizedTiles,
            ],
        ])->save();

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ]);
    }

    public function pushToObs(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $log = $collectionService->pushStageToObs($studio, $collection, $user);

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
            'stage_url' => $collection->getStageUrl(true),
        ]);
    }

    public function storeView(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'background_mode' => ['nullable', 'string', 'in:theme,solid,transparent,asset'],
            'layers' => ['nullable', 'array'],
        ]);

        $maxOrder = (int) ($collection->views()->max('sort_order') ?? 0);
        $name = trim($validated['name']);
        $baseSlug = Str::slug($name) ?: 'view';
        $slug = ScreenView::query()
            ->where('screen_collection_id', $collection->id)
            ->where('slug', $baseSlug)
            ->exists()
            ? $baseSlug.'-'.Str::lower(Str::random(4))
            : $baseSlug;

        $view = ScreenView::query()->create([
            'screen_collection_id' => $collection->id,
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $maxOrder + 1,
            'background_mode' => $validated['background_mode'] ?? 'theme',
            'layers' => $validated['layers'] ?? [],
            'logic_rules' => ['on_timer_end_action' => 'none'],
        ]);

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'view_id' => $view->id,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ], 201);
    }

    public function updateView(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenView $view,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);
        $this->authorizeView($collection, $view);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'background_mode' => ['sometimes', 'string', 'in:theme,solid,transparent,asset'],
            'background_color' => ['nullable', 'string', 'max:40'],
            'background_asset_id' => ['nullable', 'integer'],
            'theme_override' => ['nullable', 'array'],
            'transition_override' => ['nullable', 'array'],
            'layers' => ['sometimes', 'array'],
            'logic_rules' => ['nullable', 'array'],
        ]);

        if (! empty($validated['background_asset_id'])) {
            ScreenAsset::query()->ownedBy($user)->findOrFail((int) $validated['background_asset_id']);
        }

        $view->forceFill($validated)->save();
        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ]);
    }

    public function duplicateView(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenView $view,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);
        $this->authorizeView($collection, $view);

        $copyName = $view->name.' Copy';
        $maxOrder = (int) ($collection->views()->max('sort_order') ?? 0);

        $layers = array_map(static function (mixed $layer): mixed {
            if (! is_array($layer)) {
                return $layer;
            }
            $layer['id'] = 'lyr_'.Str::lower(Str::random(8));

            return $layer;
        }, $view->layers ?? []);

        $copy = ScreenView::query()->create([
            'screen_collection_id' => $collection->id,
            'user_id' => $user->id,
            'name' => $copyName,
            'slug' => Str::slug($copyName).'-'.Str::lower(Str::random(4)),
            'sort_order' => $maxOrder + 1,
            'background_mode' => $view->background_mode,
            'background_color' => $view->background_color,
            'background_asset_id' => $view->background_asset_id,
            'theme_override' => $view->theme_override,
            'transition_override' => $view->transition_override,
            'layers' => $layers,
            'logic_rules' => $view->logic_rules,
        ]);

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'view_id' => $copy->id,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ], 201);
    }

    public function reorderViews(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'view_ids' => ['required', 'array'],
            'view_ids.*' => ['integer'],
        ]);

        foreach (array_values($validated['view_ids']) as $index => $viewId) {
            ScreenView::query()
                ->where('screen_collection_id', $collection->id)
                ->whereKey((int) $viewId)
                ->update(['sort_order' => $index + 1]);
        }

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ]);
    }

    public function destroyView(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenView $view,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);
        $this->authorizeView($collection, $view);

        if ($collection->views()->count() <= 1) {
            return response()->json([
                'ok' => false,
                'message' => 'A collection must keep at least one Screen View.',
            ], 422);
        }

        $wasActive = (int) $collection->active_view_id === $view->id;
        $view->delete();

        if ($wasActive) {
            $fallback = $collection->views()->first();
            $collection->forceFill(['active_view_id' => $fallback?->id])->save();
        }

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ]);
    }

    public function storeAsset(
        Request $request,
        ObsStudio $studio,
        ScreenCollection $collection,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        $this->authorizeCollection($user, $collection);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:png,jpg,jpeg,webp,gif,svg,mp4,webm,mp3,wav,ogg'],
            'name' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:image,video,stinger,audio'],
        ]);

        $file = $request->file('file');
        $ext = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png'));
        $inferredType = match (true) {
            in_array($ext, ['mp4', 'webm'], true) => 'video',
            in_array($ext, ['mp3', 'wav', 'ogg'], true) => 'audio',
            default => 'image',
        };
        $assetType = $validated['type'] ?? $inferredType;

        $storedPath = Storage::disk('public')->putFile("screen-assets/{$user->id}", $file);

        $asset = ScreenAsset::query()->create([
            'user_id' => $user->id,
            'screen_collection_id' => $collection->id,
            'name' => trim((string) ($validated['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) ?: 'Uploaded Asset',
            'type' => $assetType,
            'disk' => 'public',
            'path' => $storedPath,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => (int) ($file->getSize() ?: 0),
            'original_filename' => $file->getClientOriginalName(),
        ]);

        $collectionService->broadcast($collection);

        return response()->json([
            'ok' => true,
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'url' => $asset->getUrl(false),
                'absolute_url' => $asset->getUrl(true),
            ],
            'collection' => ScreenCollectionService::buildStagePayload($collection->fresh(['views', 'assets']) ?? $collection),
        ], 201);
    }

    public function destroyAsset(
        Request $request,
        ObsStudio $studio,
        ScreenAsset $asset,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);
        abort_if((int) $asset->user_id !== $user->id, 403);

        $collection = $asset->screen_collection_id
            ? ScreenCollection::query()->find($asset->screen_collection_id)
            : null;

        if ($asset->path && Storage::disk($asset->disk ?: 'public')->exists($asset->path)) {
            Storage::disk($asset->disk ?: 'public')->delete($asset->path);
        }

        $asset->delete();

        if ($collection instanceof ScreenCollection) {
            $collectionService->broadcast($collection);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeStudio(Request $request, ObsStudio $studio): User
    {
        $user = $request->user();
        abort_if(! $user instanceof User || (int) $studio->user_id !== $user->id, 403);

        return $user;
    }

    private function authorizeCollection(User $user, ScreenCollection $collection): void
    {
        abort_if((int) $collection->user_id !== $user->id, 403);
    }

    private function authorizeView(ScreenCollection $collection, ScreenView $view): void
    {
        abort_if((int) $view->screen_collection_id !== $collection->id, 404);
    }
}
