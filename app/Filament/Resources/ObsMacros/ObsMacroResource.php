<?php

namespace App\Filament\Resources\ObsMacros;

use App\Filament\Resources\ObsMacros\Pages\CreateObsMacro;
use App\Filament\Resources\ObsMacros\Pages\EditObsMacro;
use App\Filament\Resources\ObsMacros\Pages\ListObsMacros;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use App\Services\Obs\ObsRequestCatalog;
use App\Services\Saas\EntitlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class ObsMacroResource extends Resource
{
    protected static ?string $model = ObsMacro::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Studio & Automation';

    protected static ?string $navigationLabel = 'Broadcast Macros';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ownedBy(auth()->user());
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(EntitlementService::class)->hasFeature($user, 'macros');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        $entitlements = app(EntitlementService::class);

        return $entitlements->hasFeature($user, 'macros')
            && $entitlements->canCreate($user, 'max_macros', ObsMacro::query()->ownedBy($user)->count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Broadcast Macro Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('obs_studio_id')
                            ->label('Studio (Optional)')
                            ->relationship(
                                name: 'studio',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->ownedBy(auth()->user()),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->helperText('Choose a Studio to populate live synced OBS scenes, sources, and audio inputs below.'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?ObsMacro $record): void {
                                if ($record === null && filled($state)) {
                                    $baseSlug = Str::slug($state);
                                    $uniqueSlug = ObsMacro::query()->where('slug', $baseSlug)->exists()
                                        ? $baseSlug.'-'.Str::lower(Str::random(4))
                                        : $baseSlug;
                                    $set('slug', $uniqueSlug);
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Cue Sequence Steps')
                    ->description(new HtmlString('Chain OBS Studio commands, delays, and live Browser Source updates. Browse every request &amp; payload inside the <a href="/admin/knowledge-base#obs-requests" style="color: #F4E185; text-decoration: underline;">Knowledge Base</a>.'))
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('steps')
                            ->columns(2)
                            ->itemLabel(fn (array $state): string => self::formatStepLabel($state))
                            ->schema([
                                Select::make('type')
                                    ->label('Step Type')
                                    ->options([
                                        'obs_command' => 'OBS WebSocket Command',
                                        'screen_update' => 'Update Screen Overlay (Reverb)',
                                        'delay_ms' => 'Delay (ms)',
                                    ])
                                    ->default('obs_command')
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                Select::make('command_preset')
                                    ->label('OBS Action / Request Type')
                                    ->options(ObsRequestCatalog::groupedSelectOptions())
                                    ->searchable()
                                    ->default('SetCurrentProgramScene')
                                    ->live()
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command')
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command')
                                    ->helperText(fn (Get $get): string => ObsRequestCatalog::describe($get('command_preset')))
                                    ->columnSpanFull(),

                                TextInput::make('custom_request_type')
                                    ->label('Custom OBS Request Type')
                                    ->placeholder('e.g. SetSceneTransitionOverride')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command' && $get('command_preset') === ObsRequestCatalog::CUSTOM_KEY)
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command' && $get('command_preset') === ObsRequestCatalog::CUSTOM_KEY)
                                    ->columnSpanFull(),

                                KeyValue::make('custom_request_data')
                                    ->label('Custom OBS Request Payload')
                                    ->keyPlaceholder('e.g. sceneName')
                                    ->valuePlaceholder('e.g. Main Scene')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command' && $get('command_preset') === ObsRequestCatalog::CUSTOM_KEY)
                                    ->columnSpanFull(),

                                Select::make('param_scene_choice')
                                    ->label('Target OBS Scene')
                                    ->options(function (Get $get): array {
                                        $scenes = self::getSyncedOptions($get)['scenes'];

                                        return [
                                            ...$scenes,
                                            ObsRequestCatalog::CUSTOM_KEY => 'Enter custom scene name...',
                                        ];
                                    })
                                    ->searchable()
                                    ->live()
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneName')
                                        && self::getSyncedOptions($get)['scenes'] !== [])
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneName')
                                        && self::getSyncedOptions($get)['scenes'] !== []),

                                TextInput::make('param_scene_custom')
                                    ->label('OBS Scene Name')
                                    ->placeholder('e.g. Main Scene')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneName')
                                        && (self::getSyncedOptions($get)['scenes'] === [] || $get('param_scene_choice') === ObsRequestCatalog::CUSTOM_KEY))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneName')
                                        && (self::getSyncedOptions($get)['scenes'] === [] || $get('param_scene_choice') === ObsRequestCatalog::CUSTOM_KEY)),

                                TextInput::make('param_scene_custom_only')
                                    ->label('New OBS Scene Name')
                                    ->placeholder('e.g. QA Spotlight')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneNameCustomOnly'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneNameCustomOnly'))
                                    ->columnSpanFull(),

                                Select::make('param_input_choice')
                                    ->label('Audio / Input Source')
                                    ->options(function (Get $get): array {
                                        $inputs = self::getSyncedOptions($get)['audio_inputs'];

                                        return [
                                            ...$inputs,
                                            ObsRequestCatalog::CUSTOM_KEY => 'Enter custom input name...',
                                        ];
                                    })
                                    ->searchable()
                                    ->live()
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputName')
                                        && self::getSyncedOptions($get)['audio_inputs'] !== [])
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputName')
                                        && self::getSyncedOptions($get)['audio_inputs'] !== []),

                                TextInput::make('param_input_custom')
                                    ->label('OBS Input Source Name')
                                    ->placeholder('e.g. Mic/Aux')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputName')
                                        && (self::getSyncedOptions($get)['audio_inputs'] === [] || $get('param_input_choice') === ObsRequestCatalog::CUSTOM_KEY))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputName')
                                        && (self::getSyncedOptions($get)['audio_inputs'] === [] || $get('param_input_choice') === ObsRequestCatalog::CUSTOM_KEY)),

                                Select::make('param_scene_item_choice')
                                    ->label('Scene Source Item')
                                    ->options(function (Get $get): array {
                                        $items = self::getSyncedOptions($get)['scene_items'];

                                        return [
                                            ...$items,
                                            ObsRequestCatalog::CUSTOM_KEY => 'Enter custom Scene Item ID...',
                                        ];
                                    })
                                    ->searchable()
                                    ->live()
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneItemId')
                                        && self::getSyncedOptions($get)['scene_items'] !== [])
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneItemId')
                                        && self::getSyncedOptions($get)['scene_items'] !== []),

                                TextInput::make('param_scene_item_custom')
                                    ->label('OBS Scene Item ID')
                                    ->numeric()
                                    ->placeholder('Numeric sceneItemId (e.g. 4)')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneItemId')
                                        && (self::getSyncedOptions($get)['scene_items'] === [] || $get('param_scene_item_choice') === ObsRequestCatalog::CUSTOM_KEY))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneItemId')
                                        && (self::getSyncedOptions($get)['scene_items'] === [] || $get('param_scene_item_choice') === ObsRequestCatalog::CUSTOM_KEY)),

                                Toggle::make('param_inputMuted')
                                    ->label('Mute Input')
                                    ->default(true)
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputMuted')),

                                TextInput::make('param_inputVolumeDb')
                                    ->label('Input Volume (dB)')
                                    ->numeric()
                                    ->minValue(-100)
                                    ->maxValue(26)
                                    ->step(0.5)
                                    ->default(0)
                                    ->suffix('dB')
                                    ->helperText('-100 dB (silent) to +26 dB (0 dB = unity gain).')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputVolumeDb'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'inputVolumeDb')),

                                Toggle::make('param_sceneItemEnabled')
                                    ->label('Source Visible')
                                    ->default(true)
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneItemEnabled')),

                                Toggle::make('param_studioModeEnabled')
                                    ->label('Enable Studio Mode')
                                    ->default(true)
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'studioModeEnabled')),

                                TextInput::make('param_transitionName')
                                    ->label('Transition Name')
                                    ->placeholder('e.g. Fade or Cut')
                                    ->default('Fade')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'transitionName'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'transitionName')),

                                TextInput::make('param_transitionDuration')
                                    ->label('Transition Duration')
                                    ->numeric()
                                    ->minValue(50)
                                    ->maxValue(20000)
                                    ->default(300)
                                    ->suffix('ms')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'transitionDuration'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'transitionDuration')),

                                TextInput::make('param_propertyName')
                                    ->label('Property Button Identifier')
                                    ->default('refreshnocache')
                                    ->helperText('Use refreshnocache to force-reload an OBS Browser Source.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'propertyName'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'propertyName')),

                                TextInput::make('param_sceneCollectionName')
                                    ->label('Scene Collection Name')
                                    ->placeholder('Exact OBS Scene Collection name')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneCollectionName'))
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'
                                        && ObsRequestCatalog::commandHasField($get('command_preset'), 'sceneCollectionName'))
                                    ->columnSpanFull(),

                                Select::make('screen_slug')
                                    ->label('Target Overlay Screen')
                                    ->options(fn (): array => ObsScreen::query()->ownedBy(auth()->user())->pluck('name', 'slug')->all())
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('type') === 'screen_update')
                                    ->required(fn (Get $get): bool => $get('type') === 'screen_update')
                                    ->columnSpanFull(),

                                KeyValue::make('state')
                                    ->label('Overlay State Patch')
                                    ->keyPlaceholder('e.g. show_lower_third')
                                    ->valuePlaceholder('e.g. true')
                                    ->helperText('Supports true/false booleans, numbers (e.g. countdown_seconds = 180), or text values.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'screen_update')
                                    ->columnSpanFull(),

                                TextInput::make('delay_ms')
                                    ->label('Delay (Milliseconds)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(500)
                                    ->suffix('ms')
                                    ->visible(fn (Get $get): bool => $get('type') === 'delay_ms')
                                    ->required(fn (Get $get): bool => $get('type') === 'delay_ms')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->defaultItems(1),
                    ]),
            ]);
    }

    /**
     * @return array{
     *     scenes: array<string, string>,
     *     audio_inputs: array<string, string>,
     *     scene_items: array<string, string>
     * }
     */
    protected static function getSyncedOptions(Get $get): array
    {
        $rawStudioId = $get('../../obs_studio_id');
        $studioId = filled($rawStudioId) ? (int) $rawStudioId : null;

        return ObsRequestCatalog::syncedOptions($studioId, auth()->user());
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function formatStepLabel(array $state): string
    {
        $type = (string) ($state['type'] ?? 'obs_command');

        if ($type === 'delay_ms') {
            $ms = (int) ($state['delay_ms'] ?? 500);

            return "Delay · {$ms} ms";
        }

        if ($type === 'screen_update') {
            $slug = trim((string) ($state['screen_slug'] ?? ''));

            return $slug !== '' ? "Overlay · {$slug}" : 'Overlay · Screen Update';
        }

        $preset = (string) ($state['command_preset'] ?? ($state['request_type'] ?? ''));
        if ($preset === ObsRequestCatalog::CUSTOM_KEY) {
            $custom = trim((string) ($state['custom_request_type'] ?? 'Custom'));

            return "OBS · {$custom}";
        }

        return $preset !== '' ? "OBS · {$preset}" : 'OBS WebSocket Command';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studio.name')
                    ->label('Studio')
                    ->placeholder('All Studios'),
                TextColumn::make('steps')
                    ->label('Steps')
                    ->formatStateUsing(fn ($state): string => (is_array($state) ? count($state) : 0).' steps'),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('runMacro')
                    ->label('Run Macro')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('warning')
                    ->schema([
                        Select::make('studio_id')
                            ->label('Target OBS Studio')
                            ->options(fn (): array => ObsStudio::query()->ownedBy(auth()->user())->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (ObsMacro $record): ?int => $record->obs_studio_id ?? ObsStudio::query()->ownedBy(auth()->user())->value('id'))
                            ->required(),
                    ])
                    ->action(function (array $data, ObsMacro $record, ObsCommandService $commandService): void {
                        $studio = ObsStudio::query()->ownedBy(auth()->user())->findOrFail($data['studio_id']);
                        $commandService->executeMacro($studio, $record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title("Macro '{$record->name}' triggered")
                            ->body("Dispatched sequence to {$studio->name}.")
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
            'index' => ListObsMacros::route('/'),
            'create' => CreateObsMacro::route('/create'),
            'edit' => EditObsMacro::route('/{record}/edit'),
        ];
    }
}
