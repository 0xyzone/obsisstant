<?php

namespace App\Filament\Pages;

use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Services\Obs\ObsCommandService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class StudioSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'OBS Studio';

    protected static ?string $navigationLabel = 'Storage Settings';

    protected static ?string $title = 'OBS Media & Local Storage Settings';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.studio-settings';

    public string $mediaStoragePath = '';

    public ?int $selectedStudioId = null;

    public ?string $pendingPickerCommandUuid = null;

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
}
