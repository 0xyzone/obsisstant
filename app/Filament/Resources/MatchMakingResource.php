<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\TeamPlayer;
use App\Models\Tournament;
use Filament\Tables\Table;
use App\Models\MatchMaking;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MatchMakingResource\Pages;
use App\Filament\Resources\MatchMakingResource\RelationManagers;

class MatchMakingResource extends Resource
{
    protected static ?string $model = MatchMaking::class;

    protected static ?string $navigationIcon = 'phosphor-sword-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-sword-fill';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tournament_id')
                    ->relationship('tournament', 'name')
                    ->required()
                    ->live()
                    ->columnSpanFull()
                    ->disabledOn('edit'),
                Section::make('Team A')
                    ->description('Just a test')
                    ->schema([
                        Forms\Components\Select::make('team_a')
                            ->hidden(fn(Get $get): bool => $get('tournament_id') != null ? false : true)
                            ->options(function (Get $get): array {
                                return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, $record) {
                                $record->team_a = $get('team_a');
                                $record->save();
                            }),
                        CheckboxList::make('a_playing')
                            ->label('Playing')
                            ->options(function (Get $get) {
                                $teamAid = $get('team_a');
                                if ($teamAid) {
                                    $teamAid = $get('team_a');
                                    if ($teamAid) {
                                        $players = TeamPlayer::where(['team_id' => $teamAid, 'is_playing' => true])->pluck('name', 'id');
                                        return $players->toArray();
                                    }
                                    return [];
                                }
                                return [];
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $selectedPlayers = $get('a_playing');
                                TeamPlayer::whereIn('id', $selectedPlayers)->update(['is_playing' => false]);
                                $set('a_playing', []);
                            })
                            ->live()
                            ->dehydrated(false)
                            ->hidden(fn(Get $get): bool => !$get('team_a')),
                        CheckboxList::make('a_not_playing')
                            ->label('Not Playing')
                            ->options(function (Get $get) {
                                $teamAid = $get('team_a');
                                if ($teamAid) {
                                    $players = TeamPlayer::where(['team_id' => $teamAid, 'is_playing' => false])->pluck('name', 'id');
                                    return $players->toArray();
                                }
                                return [];
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $selectedPlayers = $get('a_not_playing');
                                TeamPlayer::whereIn('id', $selectedPlayers)->update(['is_playing' => true]);
                                $set('a_not_playing', []);
                            })
                            ->live()
                            ->dehydrated(false)
                            ->hidden(fn(Get $get): bool => !$get('team_a')),
                    ])
                    ->columnSpan(4),

                // Team B
                Section::make('Team B')
                    ->description('Just a test')
                    ->schema([
                        Forms\Components\Select::make('team_b')
                            ->hidden(fn(Get $get): bool => $get('tournament_id') != null ? false : true)
                            ->options(function (Get $get): array {
                                return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, $record) {
                                $record->team_b = $get('team_b');
                                $record->save();
                            }),
                        CheckboxList::make('b_playing')
                            ->label('Playing')
                            ->options(function (Get $get) {
                                $teamAid = $get('team_b');
                                if ($teamAid) {
                                    $teamAid = $get('team_b');
                                    if ($teamAid) {
                                        $players = TeamPlayer::where(['team_id' => $teamAid, 'is_playing' => true])->pluck('name', 'id');
                                        return $players->toArray();
                                    }
                                    return [];
                                }
                                return [];
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $selectedPlayers = $get('b_playing');
                                TeamPlayer::whereIn('id', $selectedPlayers)->update(['is_playing' => false]);
                                $set('b_playing', []);
                            })
                            ->live()
                            ->dehydrated(false)
                            ->hidden(fn(Get $get): bool => !$get('team_b')),
                        CheckboxList::make('a_not_playing')
                            ->label('Not Playing')
                            ->options(function (Get $get) {
                                $teamAid = $get('team_b');
                                if ($teamAid) {
                                    $players = TeamPlayer::where(['team_id' => $teamAid, 'is_playing' => false])->pluck('name', 'id');
                                    return $players->toArray();
                                }
                                return [];
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $selectedPlayers = $get('a_not_playing');
                                TeamPlayer::whereIn('id', $selectedPlayers)->update(['is_playing' => true]);
                                $set('a_not_playing', []);
                            })
                            ->live()
                            ->dehydrated(false)
                            ->hidden(fn(Get $get): bool => !$get('team_b')),
                    ])
                    ->columnSpan(4),
                Forms\Components\Select::make('winning_team')
                    ->hidden(fn(Get $get): bool => ($get('team_a') != null && $get('team_b') != null) ? false : true)
                    ->options(function (Get $get): array {
                        $teamA = Team::where('id', $get('team_a'))->first();
                        $teamB = Team::where('id', $get('team_b'))->first();
                        if ($get('team_a') != null || $get('team_b') != null) {
                            return [
                                $teamA->id => $teamA->name,
                                $teamB->id => $teamB->name
                            ];
                        };
                        return [];
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, $record) {
                        // dd($record);
                        $record->winning_team = $get('winning_team');
                        $record->save();
                    }),
            ])
            ->columns(8);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tournament.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teamA.name'),
                Tables\Columns\TextColumn::make('teamB.name'),
                Tables\Columns\TextColumn::make('winner.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMatchMakings::route('/'),
            'create' => Pages\CreateMatchMaking::route('/create'),
            'edit' => Pages\EditMatchMaking::route('/{record}/edit'),
        ];
    }
}
