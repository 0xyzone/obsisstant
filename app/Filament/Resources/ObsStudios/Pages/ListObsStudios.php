<?php

namespace App\Filament\Resources\ObsStudios\Pages;

use App\Filament\Resources\ObsStudios\ObsStudioResource;
use App\Services\Obs\ObsLocalRelayService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListObsStudios extends ListRecords
{
    protected static string $resource = ObsStudioResource::class;

    public function getSubheading(): ?string
    {
        $relay = app(ObsLocalRelayService::class);
        $status = $relay->status();

        if ($status['running']) {
            $pidLabel = $status['pid'] ? " (PID {$status['pid']})" : '';

            return "Workspace Relay active{$pidLabel} — studios created or edited here connect automatically.";
        }

        return $relay->isLocalControlAvailable()
            ? 'Local Workspace Relay is stopped. Click Start Local Relay to sync all active studios automatically.'
            : 'Use Pair External Relay to connect a studio computer once — all studios auto-sync afterwards.';
    }

    protected function getHeaderActions(): array
    {
        $relay = app(ObsLocalRelayService::class);
        $localAvailable = $relay->isLocalControlAvailable();
        $status = $relay->status();

        return [
            Action::make('startLocalRelay')
                ->label($status['running'] ? 'Restart Local Relay' : 'Start Local Relay')
                ->icon($status['running'] ? Heroicon::OutlinedArrowPath : Heroicon::OutlinedPlay)
                ->color($status['running'] ? 'gray' : 'primary')
                ->visible($localAvailable)
                ->action(function () use ($relay, $status): void {
                    $result = $status['running'] ? $relay->restart() : $relay->start();
                    $fresh = $relay->status();

                    if ($fresh['running']) {
                        Notification::make()
                            ->success()
                            ->title('Workspace Relay active')
                            ->body("obs-agent synced automatically ({$result}).")
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Could not start Workspace Relay')
                            ->body($result ?: 'Check storage/logs/obs-agent.log for details.')
                            ->send();
                    }
                }),
            Action::make('stopLocalRelay')
                ->label('Stop Relay')
                ->icon(Heroicon::OutlinedStop)
                ->color('danger')
                ->visible($localAvailable && $status['running'])
                ->requiresConfirmation()
                ->action(function () use ($relay): void {
                    $relay->stop();
                    Notification::make()
                        ->warning()
                        ->title('Workspace Relay stopped')
                        ->send();
                }),
            Action::make('pairRelay')
                ->label('Pair External Relay')
                ->icon(Heroicon::OutlinedCommandLine)
                ->color('gray')
                ->modalHeading('One-Time Workspace Relay Pairing')
                ->modalDescription('Pair an external studio PC once. Every Studio created or edited in Obsisstant auto-syncs without editing .env files.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->fillForm(fn (): array => [
                    'relay_token' => (string) (config('obs.relay_token') ?: 'Not set in .env (OBS_RELAY_TOKEN)'),
                    'external_command' => $relay->externalCommandExample(),
                    'recent_logs' => $relay->recentLogs(15),
                ])
                ->schema([
                    TextInput::make('relay_token')
                        ->label('Workspace Relay Token (One-Time Setup)')
                        ->disabled()
                        ->copyable(),
                    TextInput::make('external_command')
                        ->label('Run on Studio Computer (inside obs-agent/)')
                        ->disabled()
                        ->copyable(),
                    Textarea::make('recent_logs')
                        ->label('Recent Local Relay Log')
                        ->rows(6)
                        ->disabled(),
                ]),
            CreateAction::make(),
        ];
    }
}
