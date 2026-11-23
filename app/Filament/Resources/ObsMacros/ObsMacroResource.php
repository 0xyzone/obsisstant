<?php

namespace App\Filament\Resources\ObsMacros;

use App\Filament\Resources\ObsMacros\Pages\CreateObsMacro;
use App\Filament\Resources\ObsMacros\Pages\EditObsMacro;
use App\Filament\Resources\ObsMacros\Pages\ListObsMacros;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\Obs\ObsCommandService;
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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ObsMacroResource extends Resource
{
    protected static ?string $model = ObsMacro::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'OBS Studio';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Broadcast Macro Details')
                    ->columns(2)
                    ->schema([
                        Select::make('obs_studio_id')
                            ->label('Studio (Optional)')
                            ->relationship('studio', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?ObsMacro $record): void {
                                if ($record === null && filled($state)) {
                                    $set('slug', Str::slug($state));
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
                    ->schema([
                        Repeater::make('steps')
                            ->schema([
                                Select::make('type')
                                    ->options([
                                        'obs_command' => 'OBS WebSocket Command',
                                        'screen_update' => 'Update Screen Overlay (Reverb)',
                                        'delay_ms' => 'Delay (ms)',
                                    ])
                                    ->default('obs_command')
                                    ->required()
                                    ->live(),
                                TextInput::make('request_type')
                                    ->label('OBS Request Type')
                                    ->placeholder('e.g. SetCurrentProgramScene, StartRecord')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command')
                                    ->required(fn (Get $get): bool => $get('type') === 'obs_command'),
                                KeyValue::make('request_data')
                                    ->label('OBS Request Payload')
                                    ->visible(fn (Get $get): bool => $get('type') === 'obs_command'),
                                Select::make('screen_slug')
                                    ->label('Target Overlay Screen')
                                    ->options(fn (): array => ObsScreen::query()->pluck('name', 'slug')->all())
                                    ->visible(fn (Get $get): bool => $get('type') === 'screen_update')
                                    ->required(fn (Get $get): bool => $get('type') === 'screen_update'),
                                KeyValue::make('state')
                                    ->label('Overlay State Patch')
                                    ->visible(fn (Get $get): bool => $get('type') === 'screen_update'),
                                TextInput::make('delay_ms')
                                    ->label('Delay (Milliseconds)')
                                    ->numeric()
                                    ->default(500)
                                    ->visible(fn (Get $get): bool => $get('type') === 'delay_ms'),
                            ])
                            ->collapsible()
                            ->defaultItems(1),
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
                            ->options(fn (): array => ObsStudio::query()->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (ObsMacro $record): ?int => $record->obs_studio_id ?? ObsStudio::query()->value('id'))
                            ->required(),
                    ])
                    ->action(function (array $data, ObsMacro $record, ObsCommandService $commandService): void {
                        $studio = ObsStudio::query()->findOrFail($data['studio_id']);
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
