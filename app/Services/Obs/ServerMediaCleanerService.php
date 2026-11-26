<?php

namespace App\Services\Obs;

use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ServerMediaCleanerService
{
    private const STALE_STAGING_MINUTES = 10;

    /**
     * Collect every ScreenAsset ID actively referenced by any of the user's collections, views, or layers.
     *
     * @return array<int, int>
     */
    public function collectReferencedAssetIds(User $user): array
    {
        $referenced = [];

        $collections = ScreenCollection::query()
            ->where('user_id', $user->id)
            ->get(['id', 'theme', 'default_transition']);

        $collectionIds = [];
        foreach ($collections as $collection) {
            $collectionIds[] = $collection->id;
            $theme = is_array($collection->theme) ? $collection->theme : [];
            $transition = is_array($collection->default_transition) ? $collection->default_transition : [];

            $themeBgId = (int) ($theme['background_asset_id'] ?? 0);
            if ($themeBgId > 0) {
                $referenced[$themeBgId] = $themeBgId;
            }

            $stingerId = (int) ($transition['stinger_asset_id'] ?? 0);
            if ($stingerId > 0) {
                $referenced[$stingerId] = $stingerId;
            }
        }

        $views = ScreenView::query()
            ->where(function ($query) use ($user, $collectionIds): void {
                $query->where('user_id', $user->id);
                if ($collectionIds !== []) {
                    $query->orWhereIn('screen_collection_id', $collectionIds);
                }
            })
            ->get(['id', 'background_asset_id', 'layers']);

        foreach ($views as $view) {
            $viewBgId = (int) ($view->background_asset_id ?? 0);
            if ($viewBgId > 0) {
                $referenced[$viewBgId] = $viewBgId;
            }

            foreach ($this->extractAssetIdsFromLayers($view->layers) as $layerAssetId) {
                $referenced[$layerAssetId] = $layerAssetId;
            }
        }

        return array_values($referenced);
    }

    /**
     * Extract all ScreenAsset IDs referenced inside a view's layer array.
     *
     * @return array<int, int>
     */
    public function extractAssetIdsFromLayers(mixed $layers): array
    {
        if (! is_array($layers)) {
            return [];
        }

        $ids = [];
        foreach ($layers as $layer) {
            if (! is_array($layer)) {
                continue;
            }
            $props = is_array($layer['props'] ?? null) ? $layer['props'] : [];

            $assetId = (int) ($props['asset_id'] ?? 0);
            if ($assetId > 0) {
                $ids[$assetId] = $assetId;
            }

            $url = (string) ($props['url'] ?? '');
            if ($url !== '' && preg_match('/\/stage-assets\/(\d+)(?:\/|$|\?)/', $url, $matches)) {
                $matchedId = (int) $matches[1];
                if ($matchedId > 0) {
                    $ids[$matchedId] = $matchedId;
                }
            }
        }

        return array_values($ids);
    }

    public function isAssetReferenced(User $user, int $assetId): bool
    {
        if ($assetId <= 0) {
            return false;
        }

        return in_array($assetId, $this->collectReferencedAssetIds($user), true);
    }

    /**
     * Delete replaced or removed assets (both DB record and server disk file) when no longer referenced anywhere.
     *
     * @param  array<int, int|string|null>  $candidateAssetIds
     * @return array<int, int>
     */
    public function pruneReplacedAssets(User $user, array $candidateAssetIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $candidateAssetIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        $referenced = array_flip($this->collectReferencedAssetIds($user));
        $prunedIds = [];

        $assets = ScreenAsset::query()
            ->ownedBy($user)
            ->whereIn('id', $ids)
            ->get();

        foreach ($assets as $asset) {
            if (isset($referenced[$asset->id])) {
                continue;
            }

            $prunedIds[] = $asset->id;
            $asset->delete();
        }

        return $prunedIds;
    }

    /**
     * Clear any dangling collection theme, stinger, view background, or layer references to a deleted asset.
     */
    public function detachAssetReferences(ScreenAsset $asset): void
    {
        $targetId = (int) $asset->id;
        if ($targetId <= 0 || ! $asset->user_id) {
            return;
        }

        $collections = ScreenCollection::query()
            ->where('user_id', $asset->user_id)
            ->get();

        foreach ($collections as $collection) {
            $changed = false;
            $theme = is_array($collection->theme) ? $collection->theme : [];
            $transition = is_array($collection->default_transition) ? $collection->default_transition : [];

            if ((int) ($theme['background_asset_id'] ?? 0) === $targetId) {
                $theme['background_asset_id'] = null;
                if (($theme['background_mode'] ?? '') === 'asset') {
                    $theme['background_mode'] = 'gradient';
                }
                $collection->theme = $theme;
                $changed = true;
            }

            if ((int) ($transition['stinger_asset_id'] ?? 0) === $targetId) {
                $transition['stinger_asset_id'] = null;
                $collection->default_transition = $transition;
                $changed = true;
            }

            if ($changed) {
                $collection->save();
            }
        }

        $views = ScreenView::query()
            ->where('user_id', $asset->user_id)
            ->get();

        foreach ($views as $view) {
            $changed = false;

            if ((int) ($view->background_asset_id ?? 0) === $targetId) {
                $view->background_asset_id = null;
                if ($view->background_mode === 'asset') {
                    $view->background_mode = 'theme';
                }
                $changed = true;
            }

            $layers = is_array($view->layers) ? $view->layers : [];
            foreach ($layers as $index => $layer) {
                if (! is_array($layer)) {
                    continue;
                }
                $props = is_array($layer['props'] ?? null) ? $layer['props'] : [];
                if ((int) ($props['asset_id'] ?? 0) === $targetId) {
                    $props['asset_id'] = null;
                    $props['url'] = '';
                    $layers[$index]['props'] = $props;
                    $changed = true;
                }
            }

            if ($changed) {
                $view->layers = $layers;
                $view->save();
            }
        }
    }

    /**
     * Inspect server storage for a user (optionally scoped to a single collection) to report unused & orphaned files.
     *
     * @return array{
     *     total_assets_count: int,
     *     total_assets_bytes: int,
     *     total_assets_formatted: string,
     *     used_assets_count: int,
     *     unused_assets: array<int, array<string, mixed>>,
     *     unused_assets_count: int,
     *     unused_assets_bytes: int,
     *     unused_assets_formatted: string,
     *     orphaned_files: array<int, array<string, mixed>>,
     *     orphaned_files_count: int,
     *     orphaned_files_bytes: int,
     *     orphaned_files_formatted: string,
     *     stale_staging_files: array<int, array<string, mixed>>,
     *     stale_staging_count: int,
     *     stale_staging_bytes: int,
     *     stale_staging_formatted: string,
     *     reclaimable_count: int,
     *     reclaimable_bytes: int,
     *     reclaimable_formatted: string
     * }
     */
    public function inspectForUser(User $user, ?ScreenCollection $collection = null): array
    {
        $referencedMap = array_flip($this->collectReferencedAssetIds($user));

        $allUserAssets = ScreenAsset::query()
            ->ownedBy($user)
            ->with('collection:id,name')
            ->latest('id')
            ->get();

        $knownPublicPaths = [];
        foreach ($allUserAssets as $asset) {
            if (($asset->disk ?: 'public') === 'public' && is_string($asset->path) && $asset->path !== '') {
                $knownPublicPaths[$asset->path] = true;
            }
        }

        $scopedAssets = $collection instanceof ScreenCollection
            ? $allUserAssets->filter(static fn (ScreenAsset $asset): bool => (int) $asset->screen_collection_id === $collection->id)->values()
            : $allUserAssets;

        $totalBytes = 0;
        $usedCount = 0;
        $unusedAssets = [];
        $unusedBytes = 0;

        foreach ($scopedAssets as $asset) {
            $diskName = $asset->disk ?: 'public';
            $disk = Storage::disk($diskName);
            $existsOnDisk = is_string($asset->path) && $asset->path !== '' && $disk->exists($asset->path);
            $size = $existsOnDisk ? (int) $disk->size($asset->path) : max(0, (int) $asset->size_bytes);
            $totalBytes += $size;

            if (isset($referencedMap[$asset->id])) {
                $usedCount++;

                continue;
            }

            $unusedBytes += $size;
            $unusedAssets[] = [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'original_filename' => $asset->original_filename ?: basename((string) $asset->path),
                'path' => $asset->path,
                'size_bytes' => $size,
                'size_formatted' => self::formatBytes($size),
                'collection_name' => $asset->collection?->name ?? 'Unassigned',
                'created_at_human' => $asset->created_at?->diffForHumans() ?? 'Recently',
                'url' => $asset->getUrl(false),
                'exists_on_disk' => $existsOnDisk,
            ];
        }

        $orphanedFiles = [];
        $orphanedBytes = 0;
        $publicDisk = Storage::disk('public');
        $userDir = "screen-assets/{$user->id}";

        if ($publicDisk->exists($userDir)) {
            foreach ($publicDisk->allFiles($userDir) as $filePath) {
                if (isset($knownPublicPaths[$filePath])) {
                    continue;
                }
                $size = (int) $publicDisk->size($filePath);
                $orphanedBytes += $size;
                $orphanedFiles[] = [
                    'path' => $filePath,
                    'filename' => basename($filePath),
                    'size_bytes' => $size,
                    'size_formatted' => self::formatBytes($size),
                ];
            }
        }

        $staleStagingFiles = [];
        $staleStagingBytes = 0;

        if (! $collection instanceof ScreenCollection) {
            $localDisk = Storage::disk('local');
            $cutoffTimestamp = now()->subMinutes(self::STALE_STAGING_MINUTES)->getTimestamp();
            if ($localDisk->exists('obs-media-staging')) {
                foreach ($localDisk->allFiles('obs-media-staging') as $stagedPath) {
                    $modifiedAt = (int) $localDisk->lastModified($stagedPath);
                    if ($modifiedAt > $cutoffTimestamp) {
                        continue;
                    }
                    $size = (int) $localDisk->size($stagedPath);
                    $staleStagingBytes += $size;
                    $staleStagingFiles[] = [
                        'path' => $stagedPath,
                        'filename' => basename($stagedPath),
                        'size_bytes' => $size,
                        'size_formatted' => self::formatBytes($size),
                    ];
                }
            }
        }

        $reclaimableBytes = $unusedBytes + $orphanedBytes + $staleStagingBytes;
        $reclaimableCount = count($unusedAssets) + count($orphanedFiles) + count($staleStagingFiles);

        return [
            'total_assets_count' => $scopedAssets->count(),
            'total_assets_bytes' => $totalBytes,
            'total_assets_formatted' => self::formatBytes($totalBytes),
            'used_assets_count' => $usedCount,
            'unused_assets' => $unusedAssets,
            'unused_assets_count' => count($unusedAssets),
            'unused_assets_bytes' => $unusedBytes,
            'unused_assets_formatted' => self::formatBytes($unusedBytes),
            'orphaned_files' => $orphanedFiles,
            'orphaned_files_count' => count($orphanedFiles),
            'orphaned_files_bytes' => $orphanedBytes,
            'orphaned_files_formatted' => self::formatBytes($orphanedBytes),
            'stale_staging_files' => $staleStagingFiles,
            'stale_staging_count' => count($staleStagingFiles),
            'stale_staging_bytes' => $staleStagingBytes,
            'stale_staging_formatted' => self::formatBytes($staleStagingBytes),
            'reclaimable_count' => $reclaimableCount,
            'reclaimable_bytes' => $reclaimableBytes,
            'reclaimable_formatted' => self::formatBytes($reclaimableBytes),
        ];
    }

    /**
     * Bulk delete unused uploaded assets, orphaned disk files, and expired staging uploads for a user.
     *
     * @param  array<int, int|string>|null  $onlyAssetIds
     * @return array{
     *     deleted_assets: int,
     *     deleted_orphans: int,
     *     deleted_staging: int,
     *     total_deleted: int,
     *     freed_bytes: int,
     *     freed_formatted: string
     * }
     */
    public function cleanUnusedForUser(
        User $user,
        ?ScreenCollection $collection = null,
        ?array $onlyAssetIds = null,
        bool $includeOrphans = true,
        bool $includeStaleStaging = true,
    ): array {
        $report = $this->inspectForUser($user, $collection);
        $allowedIds = $onlyAssetIds !== null
            ? array_flip(array_map(static fn (mixed $id): int => (int) $id, $onlyAssetIds))
            : null;

        $deletedAssets = 0;
        $deletedOrphans = 0;
        $deletedStaging = 0;
        $freedBytes = 0;

        foreach ($report['unused_assets'] as $unusedItem) {
            $assetId = (int) $unusedItem['id'];
            if ($allowedIds !== null && ! isset($allowedIds[$assetId])) {
                continue;
            }

            $asset = ScreenAsset::query()->ownedBy($user)->find($assetId);
            if (! $asset instanceof ScreenAsset) {
                continue;
            }

            $freedBytes += (int) $unusedItem['size_bytes'];
            $asset->delete();
            $deletedAssets++;
        }

        if ($includeOrphans) {
            $publicDisk = Storage::disk('public');
            foreach ($report['orphaned_files'] as $orphan) {
                $path = (string) $orphan['path'];
                if ($path !== '' && str_starts_with($path, "screen-assets/{$user->id}/") && $publicDisk->exists($path)) {
                    $publicDisk->delete($path);
                    $freedBytes += (int) $orphan['size_bytes'];
                    $deletedOrphans++;
                }
            }
        }

        if ($includeStaleStaging && ! $collection instanceof ScreenCollection) {
            $localDisk = Storage::disk('local');
            foreach ($report['stale_staging_files'] as $staged) {
                $path = (string) $staged['path'];
                if ($path !== '' && str_starts_with($path, 'obs-media-staging/') && $localDisk->exists($path)) {
                    $localDisk->delete($path);
                    $freedBytes += (int) $staged['size_bytes'];
                    $deletedStaging++;
                }
            }
        }

        return [
            'deleted_assets' => $deletedAssets,
            'deleted_orphans' => $deletedOrphans,
            'deleted_staging' => $deletedStaging,
            'total_deleted' => $deletedAssets + $deletedOrphans + $deletedStaging,
            'freed_bytes' => $freedBytes,
            'freed_formatted' => self::formatBytes($freedBytes),
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return $power === 0
            ? sprintf('%d %s', (int) round($value), $units[$power])
            : sprintf('%.2f %s', $value, $units[$power]);
    }
}
