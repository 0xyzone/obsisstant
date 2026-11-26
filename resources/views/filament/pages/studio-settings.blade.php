<x-filament-panels::page>
    <div
        @if ($pendingPickerCommandUuid) wire:poll.600ms="checkNativePickerResult" @endif
        class="obs-deck-stack"
    >
        <x-filament::section
            heading="Native Desktop / Mac Media Storage"
            description="Choose the folder on your OBS computer (Mac/Windows/Linux) where uploaded Image Source files are stored so OBS Studio can load them directly."
        >
            <div class="obs-list-stack-md">
                <form wire:submit="save" class="obs-list-stack-md">
                    <div class="obs-inline-form">
                        <input
                            type="text"
                            wire:model="mediaStoragePath"
                            placeholder="~/Pictures/Obsisstant or /Users/yourname/Pictures/OBS"
                            class="obs-deck-input obs-inline-grow"
                            aria-label="Native Desktop Storage Path"
                        >
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="openNativeFolderPicker"
                        >
                            @if ($pendingPickerCommandUuid)
                                Waiting for Finder...
                            @else
                                Browse Desktop Folder...
                            @endif
                        </x-filament::button>
                        <x-filament::button type="submit" color="primary">
                            Save Location
                        </x-filament::button>
                    </div>

                    @error('mediaStoragePath')
                        <p style="margin: 0; font-size: 0.75rem; color: #f4a3a6;">{{ $message }}</p>
                    @enderror
                </form>

                <div>
                    <p class="obs-deck-meta" style="margin-block-end: 0.5rem;">
                        Quick Mac / Desktop presets:
                    </p>
                    <div class="obs-btn-row">
                        @foreach ($this->getFolderPresets() as $preset)
                            <x-filament::button
                                type="button"
                                size="xs"
                                :color="$mediaStoragePath === $preset['path'] ? 'primary' : 'gray'"
                                wire:click="applyPreset('{{ addslashes($preset['path']) }}')"
                            >
                                {{ $preset['label'] }} ({{ $preset['path'] }})
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                @php
                    $studios = $this->getStudios();
                @endphp
                @if ($studios->isNotEmpty())
                    <div class="obs-deck-divider">
                        <label for="picker-studio-select" class="obs-deck-meta" style="display: block; margin-block-end: 0.375rem;">
                            Desktop Agent Used For Native Folder Browser:
                        </label>
                        <div class="obs-inline-form">
                            <select id="picker-studio-select" wire:model="selectedStudioId" class="obs-deck-input obs-deck-select">
                                @foreach ($studios as $studioItem)
                                    <option value="{{ $studioItem->id }}">
                                        {{ $studioItem->name }} ({{ $studioItem->isOnline() ? 'Online · Native Finder ready' : 'Offline' }})
                                    </option>
                                @endforeach
                            </select>
                            <x-filament::button
                                tag="a"
                                size="sm"
                                color="gray"
                                :href="route('studio.index')"
                            >
                                Open Studio Control Center
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        @php
            $storageAudit = $this->getStorageAudit();
            $unusedAssets = $storageAudit['unused_assets'] ?? [];
            $orphanedFiles = $storageAudit['orphaned_files'] ?? [];
            $staleStagingFiles = $storageAudit['stale_staging_files'] ?? [];
        @endphp

        <x-filament::section
            heading="Server Media & Bulk Unused File Cleaner"
            description="Uploaded backgrounds, videos, stingers, and overlays are automatically deleted when replaced or removed. Use the bulk cleaner below to inspect and purge unused uploads or orphaned files on the server."
        >
            <div class="obs-list-stack-md">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.75rem;">
                    <div style="padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.02);">
                        <div class="obs-deck-meta">Total Server Assets</div>
                        <div style="font-size: 1.25rem; font-weight: 600; margin-block-start: 0.25rem;">
                            {{ $storageAudit['total_assets_count'] }}
                            <span class="obs-deck-meta" style="font-weight: 400;">({{ $storageAudit['total_assets_formatted'] }})</span>
                        </div>
                        <div class="obs-deck-meta" style="margin-block-start: 0.15rem;">
                            {{ $storageAudit['used_assets_count'] }} actively used in collections
                        </div>
                    </div>

                    <div style="padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(205,173,105,0.22); background: rgba(205,173,105,0.05);">
                        <div class="obs-deck-meta">Unused Uploads</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: #F4E185; margin-block-start: 0.25rem;">
                            {{ $storageAudit['unused_assets_count'] }}
                            <span class="obs-deck-meta" style="font-weight: 400;">({{ $storageAudit['unused_assets_formatted'] }})</span>
                        </div>
                        <div class="obs-deck-meta" style="margin-block-start: 0.15rem;">
                            Not attached to any view or theme
                        </div>
                    </div>

                    <div style="padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.02);">
                        <div class="obs-deck-meta">Orphaned / Stale Disk Files</div>
                        <div style="font-size: 1.25rem; font-weight: 600; margin-block-start: 0.25rem;">
                            {{ ($storageAudit['orphaned_files_count'] ?? 0) + ($storageAudit['stale_staging_count'] ?? 0) }}
                            <span class="obs-deck-meta" style="font-weight: 400;">({{ $storageAudit['orphaned_files_formatted'] }})</span>
                        </div>
                        <div class="obs-deck-meta" style="margin-block-start: 0.15rem;">
                            Unlinked files on server storage
                        </div>
                    </div>

                    <div style="padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.05);">
                        <div class="obs-deck-meta">Reclaimable Space</div>
                        <div style="font-size: 1.25rem; font-weight: 600; color: #FCA5A5; margin-block-start: 0.25rem;">
                            {{ $storageAudit['reclaimable_formatted'] }}
                        </div>
                        <div class="obs-deck-meta" style="margin-block-start: 0.15rem;">
                            {{ $storageAudit['reclaimable_count'] }} file(s) ready to clean
                        </div>
                    </div>
                </div>

                <div class="obs-btn-row" style="justify-content: space-between; align-items: center;">
                    <div class="obs-btn-row">
                        <x-filament::button type="button" size="sm" color="gray" wire:click="$refresh">
                            Rescan Server Storage
                        </x-filament::button>
                        @if (count($unusedAssets) > 0)
                            <x-filament::button type="button" size="sm" color="gray" wire:click="selectAllUnusedAssets">
                                Select All Unused ({{ count($unusedAssets) }})
                            </x-filament::button>
                            @if (count($selectedUnusedAssetIds) > 0)
                                <x-filament::button type="button" size="sm" color="gray" wire:click="clearUnusedSelection">
                                    Clear Selection
                                </x-filament::button>
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="danger"
                                    wire:click="cleanSelectedUnusedAssets"
                                    wire:confirm="Permanently delete {{ count($selectedUnusedAssetIds) }} selected unused file(s) from server storage? This cannot be undone."
                                >
                                    Delete Selected ({{ count($selectedUnusedAssetIds) }})
                                </x-filament::button>
                            @endif
                        @endif
                    </div>

                    <x-filament::button
                        type="button"
                        size="sm"
                        color="danger"
                        :disabled="($storageAudit['reclaimable_count'] ?? 0) === 0"
                        wire:click="cleanAllUnusedMedia"
                        wire:confirm="Permanently delete all {{ $storageAudit['reclaimable_count'] }} unused and orphaned file(s) ({{ $storageAudit['reclaimable_formatted'] }}) from server storage? Active files in use will be kept."
                    >
                        Clean All Unused &amp; Orphaned ({{ $storageAudit['reclaimable_count'] }})
                    </x-filament::button>
                </div>

                @if (count($unusedAssets) > 0)
                    <div style="display: grid; gap: 0.5rem;">
                        <p class="obs-deck-meta" style="margin: 0;">Unused Uploaded Assets (select to bulk-delete or remove individually):</p>
                        @foreach ($unusedAssets as $assetItem)
                            <div
                                wire:key="unused-asset-{{ $assetItem['id'] }}"
                                style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 0.625rem; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.02);"
                            >
                                <label style="display: flex; align-items: center; gap: 0.65rem; cursor: pointer; flex: 1; min-width: 14rem;">
                                    <input
                                        type="checkbox"
                                        value="{{ $assetItem['id'] }}"
                                        wire:model.live="selectedUnusedAssetIds"
                                        style="accent-color: #CDAD69; width: 1rem; height: 1rem;"
                                    >
                                    <div>
                                        <div style="font-size: 0.875rem; font-weight: 500;">
                                            {{ $assetItem['name'] }}
                                            <span class="obs-deck-meta" style="text-transform: uppercase; font-size: 0.68rem; margin-inline-start: 0.35rem;">
                                                {{ $assetItem['type'] }}
                                            </span>
                                        </div>
                                        <div class="obs-deck-meta">
                                            {{ $assetItem['original_filename'] }} · {{ $assetItem['size_formatted'] }} · Collection: {{ $assetItem['collection_name'] }} · {{ $assetItem['created_at_human'] }}
                                        </div>
                                    </div>
                                </label>
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="danger"
                                    wire:click="deleteSingleUnusedAsset({{ $assetItem['id'] }})"
                                    wire:confirm="Permanently delete &quot;{{ addslashes($assetItem['name']) }}&quot; from server storage?"
                                >
                                    Delete
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding: 0.85rem 1rem; border-radius: 0.625rem; border: 1px dashed rgba(255,255,255,0.12); text-align: center;" class="obs-deck-meta">
                        All uploaded Screen Assets are currently referenced in active collections or views.
                    </div>
                @endif

                @if (count($orphanedFiles) > 0 || count($staleStagingFiles) > 0)
                    <div class="obs-deck-divider" style="display: grid; gap: 0.4rem;">
                        <p class="obs-deck-meta" style="margin: 0;">Orphaned &amp; Expired Staging Files on Server Disk:</p>
                        @foreach ($orphanedFiles as $orphan)
                            <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: rgba(255,255,255,0.72);">
                                <code>{{ $orphan['path'] }}</code>
                                <span>{{ $orphan['size_formatted'] }}</span>
                            </div>
                        @endforeach
                        @foreach ($staleStagingFiles as $staged)
                            <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: rgba(255,255,255,0.72);">
                                <code>{{ $staged['path'] }} (expired staging)</code>
                                <span>{{ $staged['size_formatted'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
