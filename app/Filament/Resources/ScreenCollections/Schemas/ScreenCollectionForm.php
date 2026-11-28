<?php

namespace App\Filament\Resources\ScreenCollections\Schemas;

use App\Models\ScreenCollection;
use App\Services\Obs\ScreenCollectionPresetService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ScreenCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event / Tournament Screen Collection')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        Select::make('obs_studio_id')
                            ->label('Linked Studio (Optional)')
                            ->relationship(
                                name: 'studio',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->ownedBy(auth()->user()),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('width')
                            ->numeric()
                            ->default(1920)
                            ->required(),
                        TextInput::make('height')
                            ->numeric()
                            ->default(1080)
                            ->required(),
                        TextInput::make('obs_scene_name')
                            ->label('Single OBS Scene Name')
                            ->default('Obsisstant Stage')
                            ->required(),
                        TextInput::make('obs_source_name')
                            ->label('OBS Browser Source Name')
                            ->default('Obsisstant - Unified Screen')
                            ->required(),
                        TextInput::make('public_token')
                            ->label('OBS Browser Source Stage URL')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->formatStateUsing(fn (?ScreenCollection $record): string => $record?->getStageUrl() ?? '')
                            ->visible(fn (?ScreenCollection $record): bool => $record !== null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Default Theme & Transition')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        ColorPicker::make('theme.accent_color')
                            ->label('Accent Color')
                            ->rgba()
                            ->default('#CDAD69'),
                        ColorPicker::make('theme.surface_color')
                            ->label('Surface Color')
                            ->rgba()
                            ->default('#0B0A08'),
                        ColorPicker::make('theme.text_color')
                            ->label('Text Color')
                            ->rgba()
                            ->default('#FAF7EE'),
                        Select::make('default_transition.type')
                            ->label('Default Transition')
                            ->options(ScreenCollectionPresetService::transitionOptions())
                            ->default('fade'),
                        TextInput::make('default_transition.duration_ms')
                            ->label('Transition Duration (ms)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(3000)
                            ->default(1000)
                            ->helperText('Maximum 3000 ms.'),
                        TextInput::make('default_transition.swap_point_ms')
                            ->label('Stinger / Sweep Swap Point (ms)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(3000)
                            ->default(500)
                            ->helperText('Maximum 3000 ms.'),
                    ]),
            ]);
    }
}
