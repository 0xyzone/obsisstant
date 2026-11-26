<?php

namespace App\Filament\Resources\ObsStudios;

use App\Filament\Resources\ObsStudios\Pages\CreateObsStudio;
use App\Filament\Resources\ObsStudios\Pages\EditObsStudio;
use App\Filament\Resources\ObsStudios\Pages\ListObsStudios;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsAgentInstallerService;
use App\Services\Obs\ObsCommandService;
use App\Services\Saas\EntitlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnitEnum;

class ObsStudioResource extends Resource
{
    protected static ?string $model = ObsStudio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Studio & Automation';

    protected static ?string $navigationLabel = 'OBS Studios';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ownedBy(auth()->user());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return app(EntitlementService::class)->canCreate(
            $user,
            'max_studios',
            ObsStudio::query()->ownedBy($user)->count(),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('OBS Studio Connection Profile')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?ObsStudio $record): void {
                                if ($record === null && filled($state)) {
                                    $baseSlug = Str::slug($state);
                                    $uniqueSlug = ObsStudio::query()->where('slug', $baseSlug)->exists()
                                        ? $baseSlug.'-'.Str::lower(Str::random(4))
                                        : $baseSlug;
                                    $set('slug', $uniqueSlug);
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('obs_host')
                            ->label('Local OBS Host (used by obs-agent)')
                            ->default('127.0.0.1')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('obs_port')
                            ->label('OBS WebSocket Port')
                            ->numeric()
                            ->default(4455)
                            ->required(),
                        TextInput::make('obs_password')
                            ->label('OBS WebSocket Password')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->helperText('Saved encrypted in the database and synced automatically to your running obs-agent.'),
                        TextInput::make('media_storage_path')
                            ->label('Media Upload Folder Override (Optional)')
                            ->placeholder('~/Pictures/Obsisstant (leave blank for Account Storage Settings)')
                            ->maxLength(500)
                            ->helperText('Desktop/Mac folder where images uploaded for this studio are saved. Defaults to Storage Settings.'),
                        Toggle::make('is_active')
                            ->label('Studio Enabled')
                            ->default(true),
                        TextInput::make('agent_token')
                            ->label('Per-Studio Relay Token')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->visible(fn (?ObsStudio $record): bool => $record !== null)
                            ->helperText('Auto-synced automatically when Workspace Relay mode is running. Copy only if pairing this studio standalone on a separate PC.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'streaming' => 'danger',
                        'recording' => 'warning',
                        'online' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('obs_host')
                    ->label('OBS Endpoint')
                    ->formatStateUsing(fn (ObsStudio $record): string => "{$record->obs_host}:{$record->obs_port}"),
                TextColumn::make('last_seen_at')
                    ->since()
                    ->placeholder('Never connected'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('downloadMacAgent')
                        ->label('Download Mac Installer (.zip)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (ObsStudio $record): string => route('agent-installer.download', [
                            'platform' => ObsAgentInstallerService::PLATFORM_MAC,
                            'studio' => $record->slug,
                        ])),
                    Action::make('downloadWindowsAgent')
                        ->label('Download Windows Installer (.zip)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (ObsStudio $record): string => route('agent-installer.download', [
                            'platform' => ObsAgentInstallerService::PLATFORM_WINDOWS,
                            'studio' => $record->slug,
                        ])),
                ])
                    ->label('Install Agent')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->button(),
                Action::make('mobileDeck')
                    ->label('Mobile Deck')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url(fn (ObsStudio $record): string => route('obs.deck.show', ['studio' => $record->slug]), shouldOpenInNewTab: true),
                Action::make('stopAgent')
                    ->label('Stop Agent')
                    ->icon(Heroicon::OutlinedStop)
                    ->color('danger')
                    ->visible(fn (ObsStudio $record): bool => $record->status !== 'offline' || Cache::has("obs_studio_agent_runtime_{$record->id}"))
                    ->requiresConfirmation()
                    ->modalDescription('This sends a clean shutdown signal to the running OBS Agent process on your Mac or Windows PC.')
                    ->action(function (ObsStudio $record): void {
                        $user = auth()->user();
                        app(ObsCommandService::class)->dispatch($record, 'obsisstant.StopAgent', [], $user instanceof User ? $user : null);
                        Cache::forget("obs_studio_agent_runtime_{$record->id}");
                        $record->update(['status' => 'offline']);
                        Notification::make()
                            ->warning()
                            ->title('Stop signal sent to OBS Agent')
                            ->body("Agent for \"{$record->name}\" is shutting down.")
                            ->send();
                    }),
                Action::make('regenerateToken')
                    ->label('Rotate Token')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (ObsStudio $record): void {
                        $record->update(['agent_token' => 'obs_'.Str::random(48)]);
                        Notification::make()
                            ->success()
                            ->title('Agent token rotated')
                            ->body('Update OBSISSTANT_AGENT_TOKEN in obs-agent/.env and restart the agent.')
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObsStudios::route('/'),
            'create' => CreateObsStudio::route('/create'),
            'edit' => EditObsStudio::route('/{record}/edit'),
        ];
    }
}
