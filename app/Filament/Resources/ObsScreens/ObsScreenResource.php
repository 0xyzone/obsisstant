<?php

namespace App\Filament\Resources\ObsScreens;

use App\Filament\Resources\ObsScreens\Pages\CreateObsScreen;
use App\Filament\Resources\ObsScreens\Pages\EditObsScreen;
use App\Filament\Resources\ObsScreens\Pages\ListObsScreens;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\Obs\ObsCommandService;
use App\Services\Obs\ObsScreenPresetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Str;
use UnitEnum;

class ObsScreenResource extends Resource
{
    protected static ?string $model = ObsScreen::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Stage & Overlays';

    protected static ?string $navigationLabel = 'Overlay Screens';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ownedBy(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Screen Identity & OBS Scene Mapping')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('obs_studio_id')
                            ->label('Target Studio (Optional)')
                            ->relationship(
                                name: 'studio',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->ownedBy(auth()->user()),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave blank to make this screen usable across all studios.'),
                        Select::make('template_type')
                            ->label('Preset Template')
                            ->options(ObsScreenPresetService::templateOptions())
                            ->default('lower_thirds')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state, ?ObsScreen $record): void {
                                if ($record === null && filled($state)) {
                                    $set('theme', ObsScreenPresetService::defaultTheme($state));
                                    $set('state', ObsScreenPresetService::defaultState($state));
                                }
                            }),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?ObsScreen $record): void {
                                if ($record === null && filled($state)) {
                                    $baseSlug = Str::slug($state);
                                    $uniqueSlug = ObsScreen::query()->where('slug', $baseSlug)->exists()
                                        ? $baseSlug.'-'.Str::lower(Str::random(4))
                                        : $baseSlug;
                                    $set('slug', $uniqueSlug);
                                    $set('obs_source_name', 'Obsisstant - '.$state);
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('width')
                            ->numeric()
                            ->default(1920)
                            ->required(),
                        TextInput::make('height')
                            ->numeric()
                            ->default(1080)
                            ->required(),
                        TextInput::make('obs_scene_name')
                            ->label('Target OBS Scene Name')
                            ->placeholder('e.g. Main Scene')
                            ->maxLength(255),
                        TextInput::make('obs_source_name')
                            ->label('OBS Browser Source Name')
                            ->placeholder('e.g. Obsisstant - Lower Thirds')
                            ->maxLength(255),
                        Toggle::make('is_live')
                            ->label('Overlay Active')
                            ->default(true),
                        TextInput::make('public_token')
                            ->label('OBS Browser Source URL')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->formatStateUsing(fn (?ObsScreen $record): string => $record?->getOverlayUrl() ?? '')
                            ->visible(fn (?ObsScreen $record): bool => $record !== null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Overlay Content & Live Widgets')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('state.badge_text')
                            ->label('Badge Pill Text'),
                        TextInput::make('state.headline')
                            ->label('Main Headline'),
                        TextInput::make('state.subheadline')
                            ->label('Subheadline')
                            ->columnSpanFull(),
                        TextInput::make('state.guest_name')
                            ->label('Lower-Third Name'),
                        TextInput::make('state.guest_role')
                            ->label('Lower-Third Role / Subtitle'),
                        Toggle::make('state.show_lower_third')
                            ->label('Show Lower Third')
                            ->default(true),
                        Toggle::make('state.show_webcam_frame')
                            ->label('Show Gold Camera Frame')
                            ->default(false),
                        Toggle::make('state.show_countdown')
                            ->label('Show Countdown Timer')
                            ->default(false),
                        TextInput::make('state.countdown_seconds')
                            ->label('Countdown Seconds')
                            ->numeric()
                            ->default(300),
                        Toggle::make('state.show_ticker')
                            ->label('Show Live Ticker Bar')
                            ->default(true),
                        TagsInput::make('state.ticker_items')
                            ->label('Ticker Messages')
                            ->columnSpanFull(),
                        Toggle::make('state.alert_visible')
                            ->label('Show Pop-Up Alert Toast')
                            ->default(false),
                        TextInput::make('state.alert_title')
                            ->label('Alert Title'),
                        TextInput::make('state.alert_message')
                            ->label('Alert Message')
                            ->columnSpanFull(),
                        Textarea::make('state.custom_css')
                            ->label('Custom Overlay CSS')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Glassmorphism Theme')
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        ColorPicker::make('theme.accent_color')
                            ->label('Accent Color')
                            ->rgba()
                            ->default('#E5B94C'),
                        ColorPicker::make('theme.surface_color')
                            ->label('Surface Color')
                            ->rgba()
                            ->default('#0B0A08'),
                        ColorPicker::make('theme.text_color')
                            ->label('Text Color')
                            ->rgba()
                            ->default('#F7F3E9'),
                        TextInput::make('theme.backdrop_blur')
                            ->label('Backdrop Blur (px)')
                            ->numeric()
                            ->default(18),
                        TextInput::make('theme.border_radius')
                            ->label('Border Radius (px)')
                            ->numeric()
                            ->default(24),
                        TextInput::make('theme.glass_opacity')
                            ->label('Glass Opacity (0.1 - 1.0)')
                            ->numeric()
                            ->step(0.05)
                            ->default(0.78),
                        Toggle::make('theme.transparent_background')
                            ->label('Transparent Canvas (recommended for overlays)')
                            ->default(true)
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
                TextColumn::make('template_type')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('studio.name')
                    ->label('Studio')
                    ->placeholder('All Studios'),
                TextColumn::make('obs_scene_name')
                    ->label('OBS Scene')
                    ->placeholder('—'),
                TextColumn::make('width')
                    ->label('Resolution')
                    ->formatStateUsing(fn (ObsScreen $record): string => "{$record->width}×{$record->height}"),
                IconColumn::make('is_live')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Preview')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (ObsScreen $record): string => $record->getOverlayUrl(), shouldOpenInNewTab: true),
                Action::make('pushToObs')
                    ->label('Push to OBS')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->schema([
                        Select::make('studio_id')
                            ->label('Target OBS Studio')
                            ->options(fn (): array => ObsStudio::query()->ownedBy(auth()->user())->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (ObsScreen $record): ?int => $record->obs_studio_id ?? ObsStudio::query()->ownedBy(auth()->user())->value('id'))
                            ->required(),
                        TextInput::make('scene_name')
                            ->label('OBS Scene Name')
                            ->default(fn (ObsScreen $record): string => $record->obs_scene_name ?: $record->name)
                            ->required(),
                    ])
                    ->action(function (array $data, ObsScreen $record, ObsCommandService $commandService): void {
                        $studio = ObsStudio::query()->ownedBy(auth()->user())->findOrFail($data['studio_id']);
                        $commandService->pushScreenToObs($studio, $record, $data['scene_name'], auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Screen pushed to OBS')
                            ->body("Dispatched Browser Source setup for '{$data['scene_name']}' to {$studio->name}.")
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
            'index' => ListObsScreens::route('/'),
            'create' => CreateObsScreen::route('/create'),
            'edit' => EditObsScreen::route('/{record}/edit'),
        ];
    }
}
