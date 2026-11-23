<?php

namespace App\Filament\Pages;

use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\Obs\ObsCommandService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ObsControlDeck extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static string|UnitEnum|null $navigationGroup = 'OBS Studio';

    protected static ?string $navigationLabel = 'Control Deck';

    protected static ?string $title = 'OBS Live Control Deck';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.obs-control-deck';

    public ?int $studioId = null;

    public ?int $selectedScreenId = null;

    public string $newSceneName = '';

    public string $liveGuestName = '';

    public string $liveGuestRole = '';

    public string $liveAlertTitle = '';

    public string $liveAlertMessage = '';

    public function mount(): void
    {
        $firstStudio = ObsStudio::query()->where('is_active', true)->orderBy('id')->first();
        $this->studioId = $firstStudio?->id;

        $firstScreen = $this->getScreens()->first();
        $this->selectedScreenId = $firstScreen?->id;
        $this->syncSelectedScreenFields();
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if (! $this->studioId) {
            return [];
        }

        return [
            "echo-private:obs.studio.{$this->studioId},.obs.studio.state" => 'handleStudioStateEvent',
            "echo-private:obs.studio.{$this->studioId},.obs.command.acknowledged" => 'handleCommandAckEvent',
        ];
    }

    public function updatedStudioId(): void
    {
        $firstScreen = $this->getScreens()->first();
        $this->selectedScreenId = $firstScreen?->id;
        $this->syncSelectedScreenFields();
    }

    public function updatedSelectedScreenId(): void
    {
        $this->syncSelectedScreenFields();
    }

    public function handleStudioStateEvent(): void
    {
        // Livewire re-renders with fresh database state from getStudio()
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleCommandAckEvent(array $payload): void
    {
        if (($payload['status'] ?? '') === 'failed') {
            Notification::make()
                ->danger()
                ->title('OBS Command Failed')
                ->body((string) ($payload['error_message'] ?? 'Unknown OBS error'))
                ->send();
        }
    }

    public function getStudio(): ?ObsStudio
    {
        if (! $this->studioId) {
            return null;
        }

        return ObsStudio::query()->find($this->studioId);
    }

    /**
     * @return Collection<int, ObsStudio>
     */
    public function getStudios(): Collection
    {
        return ObsStudio::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, ObsScreen>
     */
    public function getScreens(): Collection
    {
        return ObsScreen::query()
            ->when($this->studioId, fn ($q) => $q->where(fn ($sub) => $sub->where('obs_studio_id', $this->studioId)->orWhereNull('obs_studio_id')))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ObsMacro>
     */
    public function getMacros(): Collection
    {
        return ObsMacro::query()
            ->when($this->studioId, fn ($q) => $q->where(fn ($sub) => $sub->where('obs_studio_id', $this->studioId)->orWhereNull('obs_studio_id')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ObsCommandLog>
     */
    public function getRecentLogs(): Collection
    {
        if (! $this->studioId) {
            return new Collection;
        }

        return ObsCommandLog::query()
            ->where('obs_studio_id', $this->studioId)
            ->latest('id')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $requestData
     */
    public function sendCommand(string $requestType, array $requestData = []): void
    {
        $studio = $this->getStudio();
        if (! $studio instanceof ObsStudio) {
            Notification::make()->warning()->title('Select an OBS Studio first')->send();

            return;
        }

        app(ObsCommandService::class)->dispatch(
            studio: $studio,
            requestType: $requestType,
            requestData: $requestData,
            user: auth()->user(),
        );

        Notification::make()
            ->success()
            ->title("Dispatched {$requestType}")
            ->duration(2000)
            ->send();
    }

    public function switchScene(string $sceneName): void
    {
        $studio = $this->getStudio();
        if (! $studio instanceof ObsStudio) {
            return;
        }

        $method = $studio->isStudioModeEnabled() ? 'SetCurrentPreviewScene' : 'SetCurrentProgramScene';
        $this->sendCommand($method, ['sceneName' => $sceneName]);
    }

    public function createNewScene(): void
    {
        $name = trim($this->newSceneName);
        if ($name === '') {
            return;
        }

        $this->sendCommand('CreateScene', ['sceneName' => $name]);
        $this->newSceneName = '';
    }

    public function toggleStudioMode(): void
    {
        $studio = $this->getStudio();
        if (! $studio instanceof ObsStudio) {
            return;
        }

        $this->sendCommand('SetStudioModeEnabled', [
            'studioModeEnabled' => ! $studio->isStudioModeEnabled(),
        ]);
    }

    public function toggleSceneItem(int $sceneItemId, bool $enabled): void
    {
        $studio = $this->getStudio();
        $sceneName = $studio?->getCurrentProgramScene();
        if (! $studio instanceof ObsStudio || ! $sceneName) {
            return;
        }

        $this->sendCommand('SetSceneItemEnabled', [
            'sceneName' => $sceneName,
            'sceneItemId' => $sceneItemId,
            'sceneItemEnabled' => $enabled,
        ]);
    }

    public function toggleInputMute(string $inputName, bool $muted): void
    {
        $this->sendCommand('SetInputMute', [
            'inputName' => $inputName,
            'inputMuted' => $muted,
        ]);
    }

    public function setInputVolumeDb(string $inputName, float $volumeDb): void
    {
        $this->sendCommand('SetInputVolume', [
            'inputName' => $inputName,
            'inputVolumeDb' => $volumeDb,
        ]);
    }

    public function refreshBrowserSource(string $inputName): void
    {
        $this->sendCommand('PressInputPropertiesButton', [
            'inputName' => $inputName,
            'propertyName' => 'refreshnocache',
        ]);
    }

    public function runMacro(int $macroId): void
    {
        $studio = $this->getStudio();
        $macro = ObsMacro::query()->find($macroId);
        if (! $studio instanceof ObsStudio || ! $macro instanceof ObsMacro) {
            return;
        }

        app(ObsCommandService::class)->executeMacro($studio, $macro, auth()->user());

        Notification::make()
            ->success()
            ->title("Macro '{$macro->name}' executed")
            ->send();
    }

    public function pushSelectedScreenToObs(): void
    {
        $studio = $this->getStudio();
        $screen = $this->selectedScreenId ? ObsScreen::query()->find($this->selectedScreenId) : null;
        if (! $studio instanceof ObsStudio || ! $screen instanceof ObsScreen) {
            return;
        }

        app(ObsCommandService::class)->pushScreenToObs($studio, $screen, null, auth()->user());

        Notification::make()
            ->success()
            ->title("Pushed '{$screen->name}' to {$studio->name}")
            ->send();
    }

    public function updateLiveOverlay(bool $showLowerThird = true): void
    {
        $screen = $this->selectedScreenId ? ObsScreen::query()->find($this->selectedScreenId) : null;
        if (! $screen instanceof ObsScreen) {
            return;
        }

        app(ObsCommandService::class)->updateScreenState($screen, [
            'guest_name' => $this->liveGuestName,
            'guest_role' => $this->liveGuestRole,
            'show_lower_third' => $showLowerThird,
        ]);

        Notification::make()
            ->success()
            ->title('Overlay updated via Reverb')
            ->send();
    }

    public function triggerLiveAlert(bool $visible = true): void
    {
        $screen = $this->selectedScreenId ? ObsScreen::query()->find($this->selectedScreenId) : null;
        if (! $screen instanceof ObsScreen) {
            return;
        }

        app(ObsCommandService::class)->updateScreenState($screen, [
            'alert_visible' => $visible,
            'alert_title' => $this->liveAlertTitle ?: 'Live Notice',
            'alert_message' => $this->liveAlertMessage,
        ]);

        Notification::make()
            ->success()
            ->title($visible ? 'Alert displayed on overlay' : 'Alert hidden')
            ->send();
    }

    protected function syncSelectedScreenFields(): void
    {
        $screen = $this->selectedScreenId ? ObsScreen::query()->find($this->selectedScreenId) : null;
        if (! $screen instanceof ObsScreen) {
            return;
        }

        $state = $screen->getMergedState();
        $this->liveGuestName = (string) ($state['guest_name'] ?? '');
        $this->liveGuestRole = (string) ($state['guest_role'] ?? '');
        $this->liveAlertTitle = (string) ($state['alert_title'] ?? 'Live Notice');
        $this->liveAlertMessage = (string) ($state['alert_message'] ?? '');
    }
}
