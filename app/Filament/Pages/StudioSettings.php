<?php

namespace App\Filament\Pages;

use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Models\ScreenAsset;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use App\Services\Obs\ServerMediaCleanerService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class StudioSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Studio & Automation';

    protected static ?string $navigationLabel = 'Storage & Cleaner';

    protected static ?string $title = 'OBS Media & Storage Settings';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.studio-settings';

    public string $mediaStoragePath = '';

    public ?int $selectedStudioId = null;

    public ?string $pendingPickerCommandUuid = null;

    /** @var array<int, int|string> */
    public array $selectedUnusedAssetIds = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->mediaStoragePath = trim((string) ($user?->media_storage_path ?: config('obs.media_storage_path', '~/Pictures/Obsisstant')));
        $this->selectedStudioId = ObsStudio::query()
            ->ownedBy($user)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->value('id');
    }

    /**
     * @return Collection<int, ObsStudio>
     */
    public function getStudios(): Collection
    {
        return ObsStudio::query()
            ->ownedBy(auth()->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array{label: string, path: string}>
     */
    public function getFolderPresets(): array
    {
        $presets = [];
        $studio = $this->selectedStudioId
            ? ObsStudio::query()->ownedBy(auth()->user())->find($this->selectedStudioId)
            : null;

        $agentHost = is_array($studio?->live_state['agentHost'] ?? null) ? $studio->live_state['agentHost'] : [];
        if (! empty($agentHost['defaultMediaDir'])) {
            $presets[] = ['label' => 'Detected Desktop Pictures', 'path' => (string) $agentHost['defaultMediaDir']];
        }

        $presets[] = ['label' => 'Pictures / Obsisstant', 'path' => '~/Pictures/Obsisstant'];
        $presets[] = ['label' => 'Desktop / Obsisstant', 'path' => '~/Desktop/Obsisstant'];
        $presets[] = ['label' => 'Movies / Obsisstant Media', 'path' => '~/Movies/ObsisstantMedia'];
        $presets[] = ['label' => 'Documents / Obsisstant', 'path' => '~/Documents/Obsisstant'];

        return $presets;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'mediaStoragePath' => ['required', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $user?->update([
            'media_storage_path' => trim($validated['mediaStoragePath']),
        ]);

        Notification::make()
            ->success()
            ->title('Storage settings saved')
            ->body('Uploaded OBS images will be saved inside '.trim($validated['mediaStoragePath']))
            ->send();
    }

    public function applyPreset(string $path): void
    {
        $this->mediaStoragePath = trim($path);
        $this->save();
    }

    public function openNativeFolderPicker(): void
    {
        $user = auth()->user();
        $studio = $this->selectedStudioId
            ? ObsStudio::query()->ownedBy($user)->find($this->selectedStudioId)
            : ObsStudio::query()->ownedBy($user)->where('is_active', true)->orderByDesc('last_seen_at')->first();

        if (! $studio instanceof ObsStudio || ! $studio->isOnline()) {
            Notification::make()
                ->warning()
                ->title('Connect obs-agent first to open Finder')
                ->body('Enter or click a preset path below, or connect your OBS Studio agent to browse native desktop folders.')
                ->send();

            return;
        }

        $log = app(ObsCommandService::class)->dispatch(
            studio: $studio,
            requestType: 'obsisstant.PickLocalDirectory',
            requestData: [
                'prompt' => 'Select Obsisstant OBS Media Storage Folder',
                'initialPath' => $this->mediaStoragePath,
            ],
            user: $user,
        );

        $this->pendingPickerCommandUuid = $log->command_uuid;

        Notification::make()
            ->info()
            ->title('Finder / Folder chooser opened')
            ->body("Choose your folder in the native dialog opened on {$studio->name}.")
            ->send();
    }

    public function checkNativePickerResult(): void
    {
        if (! $this->pendingPickerCommandUuid) {
            return;
        }

        $log = ObsCommandLog::query()
            ->where('command_uuid', $this->pendingPickerCommandUuid)
            ->first();

        if (! $log instanceof ObsCommandLog || $log->status === 'dispatched') {
            return;
        }

        $this->pendingPickerCommandUuid = null;

        if ($log->status === 'failed') {
            Notification::make()
                ->danger()
                ->title('Folder selection failed')
                ->body((string) ($log->error_message ?? 'Could not open desktop folder chooser.'))
                ->send();

            return;
        }

        $response = is_array($log->response_data) ? $log->response_data : [];
        if (! empty($response['cancelled'])) {
            Notification::make()
                ->warning()
                ->title('Folder selection cancelled')
                ->send();

            return;
        }

        $pickedPath = trim((string) ($response['path'] ?? ''));
        if ($pickedPath !== '') {
            $this->mediaStoragePath = $pickedPath;
            $this->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStorageAudit(): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [
                'total_assets_count' => 0,
                'total_assets_formatted' => '0 B',
                'used_assets_count' => 0,
                'unused_assets' => [],
                'unused_assets_count' => 0,
                'unused_assets_formatted' => '0 B',
                'orphaned_files' => [],
                'orphaned_files_count' => 0,
                'orphaned_files_formatted' => '0 B',
                'stale_staging_files' => [],
                'stale_staging_count' => 0,
                'stale_staging_formatted' => '0 B',
                'reclaimable_count' => 0,
                'reclaimable_bytes' => 0,
                'reclaimable_formatted' => '0 B',
            ];
        }

        return app(ServerMediaCleanerService::class)->inspectForUser($user);
    }

    public function selectAllUnusedAssets(): void
    {
        $audit = $this->getStorageAudit();
        $this->selectedUnusedAssetIds = array_values(array_map(
            static fn (array $item): int => (int) $item['id'],
            $audit['unused_assets'] ?? [],
        ));
    }

    public function clearUnusedSelection(): void
    {
        $this->selectedUnusedAssetIds = [];
    }

    public function deleteSingleUnusedAsset(int $assetId): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $asset = ScreenAsset::query()->ownedBy($user)->find($assetId);
        if (! $asset instanceof ScreenAsset) {
            return;
        }

        $cleaner = app(ServerMediaCleanerService::class);
        if ($cleaner->isAssetReferenced($user, $asset->id)) {
            Notification::make()
                ->warning()
                ->title('Asset is currently in use')
                ->body('Remove it from your collection theme or screen view before deleting.')
                ->send();

            return;
        }

        $assetName = $asset->name;
        $asset->delete();

        $this->selectedUnusedAssetIds = array_values(array_filter(
            $this->selectedUnusedAssetIds,
            static fn (int|string $id): bool => (int) $id !== $assetId,
        ));

        Notification::make()
            ->success()
            ->title('Server file deleted')
            ->body("Removed \"{$assetName}\" from database and server storage.")
            ->send();
    }

    public function cleanSelectedUnusedAssets(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $ids = array_values(array_unique(array_filter(
            array_map(static fn (int|string $id): int => (int) $id, $this->selectedUnusedAssetIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            Notification::make()
                ->warning()
                ->title('No unused files selected')
                ->body('Select one or more unused files first, or use Clean All Unused Files.')
                ->send();

            return;
        }

        $result = app(ServerMediaCleanerService::class)->cleanUnusedForUser(
            user: $user,
            onlyAssetIds: $ids,
            includeOrphans: false,
            includeStaleStaging: false,
        );

        $this->selectedUnusedAssetIds = [];

        Notification::make()
            ->success()
            ->title('Selected unused files deleted')
            ->body("Removed {$result['deleted_assets']} file(s) and freed {$result['freed_formatted']} of server storage.")
            ->send();
    }

    public function cleanAllUnusedMedia(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $result = app(ServerMediaCleanerService::class)->cleanUnusedForUser(
            user: $user,
            onlyAssetIds: null,
            includeOrphans: true,
            includeStaleStaging: true,
        );

        $this->selectedUnusedAssetIds = [];

        if ($result['total_deleted'] === 0) {
            Notification::make()
                ->info()
                ->title('Server storage is already clean')
                ->body('No unused uploads, orphaned disk files, or expired staging files were found.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Bulk media cleanup complete')
            ->body("Deleted {$result['total_deleted']} unused/orphaned file(s) and reclaimed {$result['freed_formatted']}.")
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanAllUnusedMediaAction')
                ->label('Bulk Clean Unused Files')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clean All Unused & Orphaned Server Files?')
                ->modalDescription('This permanently deletes all uploaded media assets not referenced by any collection, view, or layer, plus any orphaned disk files and expired staging files. Active files currently used on stage will NOT be touched.')
                ->modalSubmitActionLabel('Delete Unused Files')
                ->action(fn () => $this->cleanAllUnusedMedia()),
        ];
    }
}
