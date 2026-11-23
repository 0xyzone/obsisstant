<?php

namespace App\Filament\Resources\ObsStudios\Pages;

use App\Filament\Resources\ObsStudios\ObsStudioResource;
use App\Models\User;
use App\Services\Obs\ObsAgentInstallerService;
use App\Services\Obs\ObsLocalRelayService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
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

            return "OBS Agent active{$pidLabel} — studios created or edited here connect automatically.";
        }

        return $relay->isLocalControlAvailable()
            ? 'Start Local Agent for your workstation or click Pair OBS Agent to connect any remote streaming PC.'
            : 'Click Pair OBS Agent to copy your personal Relay Token and launch command on your streaming PC.';
    }

    protected function getHeaderActions(): array
    {
        $relay = app(ObsLocalRelayService::class);
        $localAvailable = $relay->isLocalControlAvailable();
        $status = $relay->status();

        return [
            Action::make('startLocalRelay')
                ->label($status['running'] ? 'Restart Local Agent' : 'Start Local Agent')
                ->icon($status['running'] ? Heroicon::OutlinedArrowPath : Heroicon::OutlinedPlay)
                ->color($status['running'] ? 'gray' : 'primary')
                ->visible($localAvailable)
                ->action(function () use ($relay, $status): void {
                    $result = $status['running'] ? $relay->restart() : $relay->start();
                    $fresh = $relay->status();

                    if ($fresh['running']) {
                        Notification::make()
                            ->success()
                            ->title('OBS Agent active')
                            ->body("obs-agent synced automatically ({$result}).")
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Could not start OBS Agent')
                            ->body($result ?: 'Check storage/logs/obs-agent.log for details.')
                            ->send();
                    }
                }),
            Action::make('stopLocalRelay')
                ->label('Stop Agent')
                ->icon(Heroicon::OutlinedStop)
                ->color('danger')
                ->visible($localAvailable && $status['running'])
                ->requiresConfirmation()
                ->action(function () use ($relay): void {
                    $relay->stop();
                    Notification::make()
                        ->warning()
                        ->title('OBS Agent stopped')
                        ->send();
                }),
            Action::make('pairRelay')
                ->label('Pair OBS Agent')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->modalHeading('Install & Connect OBS Agent (Mac & Windows)')
                ->modalDescription('Download the pre-configured installer on the computer running OBS Studio and double-click to run — zero code or manual token setup required.')
                ->extraModalFooterActions([
                    Action::make('downloadMac')
                        ->label('Download Mac Installer (.zip)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('primary')
                        ->url(fn (): string => route('agent-installer.download', ['platform' => ObsAgentInstallerService::PLATFORM_MAC])),
                    Action::make('downloadWindows')
                        ->label('Download Windows Installer (.zip)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('gray')
                        ->url(fn (): string => route('agent-installer.download', ['platform' => ObsAgentInstallerService::PLATFORM_WINDOWS])),
                ])
                ->modalSubmitActionLabel('Rotate Personal Relay Token')
                ->modalSubmitAction(fn (Action $action): Action => $action->color('warning')->requiresConfirmation())
                ->modalCancelActionLabel('Close')
                ->fillForm(function () use ($relay): array {
                    $user = auth()->user();
                    $personalToken = $user instanceof User ? $user->getOrCreateRelayToken() : 'Sign in to view token';
                    $payload = $user instanceof User
                        ? app(ObsAgentInstallerService::class)->issueBootstrapPayload($user)
                        : ['mac_command' => '', 'windows_command' => ''];

                    return [
                        'mac_command' => $payload['mac_command'],
                        'windows_command' => $payload['windows_command'],
                        'relay_token' => $personalToken,
                        'external_command' => $relay->externalCommandExample($personalToken),
                        'recent_logs' => $relay->recentLogs(15),
                    ];
                })
                ->schema([
                    Section::make('Recommended for Mac (Zero Gatekeeper Warnings) & 1-Click Windows')
                        ->schema([
                            TextInput::make('mac_command')
                                ->label('Mac Terminal (⌘ Space -> Terminal -> Paste & Enter)')
                                ->disabled()
                                ->copyable(),
                            TextInput::make('windows_command')
                                ->label('Windows PowerShell (Paste & Enter)')
                                ->disabled()
                                ->copyable(),
                        ]),
                    Section::make('Option 2: Advanced / Manual Relay Credentials')
                        ->collapsed()
                        ->schema([
                            TextInput::make('relay_token')
                                ->label('Personal Account Relay Token (Syncs All Your Studios)')
                                ->disabled()
                                ->copyable(),
                            TextInput::make('external_command')
                                ->label('Manual CLI Launch inside obs-agent/')
                                ->disabled()
                                ->copyable(),
                            Textarea::make('recent_logs')
                                ->label('Recent Local Agent Log')
                                ->rows(5)
                                ->disabled(),
                        ]),
                ])
                ->action(function (): void {
                    $user = auth()->user();
                    if ($user instanceof User) {
                        $user->rotateRelayToken();
                        Notification::make()
                            ->success()
                            ->title('Personal Relay Token rotated')
                            ->body('Update OBSISSTANT_RELAY_TOKEN on your OBS computer and restart obs-agent.')
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
