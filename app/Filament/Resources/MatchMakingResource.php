<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatchMakingResource\Pages\CreateMatchMaking;
use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\MatchLog;
use Filament\Forms\Form;
use App\Models\TeamPlayer;
use App\Models\Tournament;
use Filament\Tables\Table;
use App\Models\MatchMaking;
use Illuminate\Support\Arr;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MatchMakingResource\Pages;
use App\Filament\Resources\MatchMakingResource\RelationManagers;

class MatchMakingResource extends Resource
{
    protected static ?string $model = MatchMaking::class;

    protected static ?string $navigationIcon = 'phosphor-sword-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-sword-fill';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tournament_id')
                    ->relationship('tournament', 'name')
                    ->required()
                    ->unique()
                    ->validationMessages([
                        'unique' => 'Match for this :attribute has already been created.',
                    ])
                    ->live()
                    ->columnSpan(2)
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('match_number')
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                    ->required()
                    ->columnSpan(2)
                    ->label('Match Title')
                    ->afterStateUpdated(function ($state, $record) {
                        if ($record) {
                            $record->match_number = $state;
                            $record->save();
                        }
                    })
                    ->live(),
                Forms\Components\Select::make('winning_team')
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                    ->options(function (Get $get): array {
                        if ($get('team_a') != null && $get('team_b') != null) {
                            $teamA = Team::where('id', $get('team_a'))->first();
                            $teamB = Team::where('id', $get('team_b'))->first();
                            return [
                                $teamA->id => $teamA->name,
                                $teamB->id => $teamB->name
                            ];
                        };
                        return [];
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, $record) {
                        if ($record) {
                            $record->winning_team = $get('winning_team');
                            $record->save();
                        }
                    })
                    ->columnSpan(2),
                Actions::make([
                    Action::make('Reset')
                        ->label('Reset MP')
                        ->icon('heroicon-m-backspace')
                        ->action(function ($record, Set $set) {
                            $record->teamA_match_point = 0;
                            $record->teamB_match_point = 0;
                            $set('teamA_match_point', 0);
                            $set('teamB_match_point', 0);
                            $record->save();
                        })
                        // ->size(ActionSize::Large)
                        ->requiresConfirmation()
                        ->extraAttributes([
                            'class' => 'w-6/12'
                        ]),
                    Action::make('Save')
                        ->label('Save log')
                        ->icon('phosphor-floppy-disk-back-duotone')
                        ->size(ActionSize::Large)
                        ->outlined()
                        ->action(function ($record) {
                            $teamA = $record->teamA->name;
                            $teamB = $record->teamB->name;
                            $teamArooster = $record->teamA->teamPlayers->toArray();
                            $teamBrooster = $record->teamB->teamPlayers->toArray();
                            $iterationA = 1;
                            $iterationB = 1;
                            $aRooster = [];
                            $bRooster = [];
                            foreach ($teamArooster as $player) {
                                $aRooster[] = [
                                    'p' . $iterationA++ => [
                                        'name' => $player['name'],
                                        'is_mvp' => $player['is_mvp'],
                                        'k' => $player['kills'],
                                        'd' => $player['deaths'],
                                        'a' => $player['assists'],
                                        'g' => $player['net_worth'],
                                        'hero' => TeamPlayer::where('id', $player['id'])->first()->hero->name,
                                        'hero_damage' => $player['hero_damage'],
                                        'turret_damage' => $player['turret_damage'],
                                        'damage_taken' => $player['damage_taken'],
                                        'fight_participation' => $player['fight_participation'],
                                    ],
                                ];
                            };

                            foreach ($teamBrooster as $player) {
                                $bRooster[] = [
                                    'p' . $iterationB++ => [
                                        'name' => $player['name'],
                                        'is_mvp' => $player['is_mvp'],
                                        'k' => $player['kills'],
                                        'd' => $player['deaths'],
                                        'a' => $player['assists'],
                                        'g' => $player['net_worth'],
                                        'hero' => TeamPlayer::where('id', $player['id'])->first()->hero->name,
                                        'hero_damage' => $player['hero_damage'],
                                        'turret_damage' => $player['turret_damage'],
                                        'damage_taken' => $player['damage_taken'],
                                        'fight_participation' => $player['fight_participation'],
                                    ],
                                ];
                            };
                            $data = [
                                "tournament" => $record->tournament->name,
                                "teamA" => [
                                    "name" => $record->teamA->name,
                                    "mp" => $record->teamA_match_point,
                                    "rooster" => $aRooster
                                ],
                                "teamB" => [
                                    "name" => $record->teamB->name,
                                    "mp" => $record->teamB_match_point,
                                    "rooster" => $bRooster
                                ],
                            ];

                            $log = MatchLog::updateOrCreate(
                                [
                                    'match_title' => $record->match_number,
                                    'tournament_id' => $record->tournament_id,
                                ],
                                [
                                    'tournament_id' => $record->tournament_id,
                                    'team_a' => $record->team_a,
                                    'team_b' => $record->team_b,
                                    'data' => json_encode($data)
                                ]
                            );
                            if ($log) {
                                Notification::make()
                                    ->title('Saved successfully')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->extraAttributes(['class' => 'shrink-0 w-5/12'])
                ])
                    ->extraAttributes(['class' => 'flex justify-end'])
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                ->columns(2)
                ->columnSpan(2),
                Section::make('Team A')
                    ->description('Select a team')
                    ->schema([
                        Forms\Components\Select::make('team_a')
                            ->options(function (Get $get): array {
                                return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, $record) {
                                if ($record) {
                                    $record->team_a = $get('team_a');
                                    $record->save();
                                }
                            })
                            ->columnSpan(5),
                        TextInput::make('teamA_match_point')
                            ->label('MP')
                            ->live()
                            ->hiddenOn('create')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    $record->teamA_match_point = $state;
                                    $record->save();
                                }
                            })
                    ])
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                    ->columnSpan(4)
                    ->columns(6),

                // Team B
                Section::make('Team B')
                    ->description('Select a team')
                    ->schema([
                        Forms\Components\Select::make('team_b')
                            ->options(function (Get $get): array {
                                return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, $record) {
                                if ($record) {
                                    $record->team_b = $get('team_b');
                                    $record->save();
                                }
                            })
                            ->columnSpan(5),
                        TextInput::make('teamB_match_point')
                            ->label('MP')
                            ->hiddenOn('create')
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    $record->teamB_match_point = $state;
                                    $record->save();
                                }
                            })
                    ])
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                    ->columnSpan(4)
                    ->columns(6),
            ])
            ->columns(8);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('match_number')
                    ->label('Match Identifier'),
                Tables\Columns\TextColumn::make('tournament.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\SelectColumn::make('team_a')
                    ->options(function ($record) {
                        $team = Team::where('tournament_id', $record->tournament->id)->pluck('name', 'id');
                        return $team->toArray();
                    }),
                Tables\Columns\SelectColumn::make('team_b')
                    ->options(function ($record) {
                        $teamB = Team::where('tournament_id', $record->tournament->id)->pluck('name', 'id');
                        return $teamB->toArray();
                    }),
                Tables\Columns\ToggleColumn::make('active')
                    ->beforeStateUpdated(function (MatchMaking $record) {
                        MatchMaking::where('id', '!=', $record->id)->update(['active' => false]);
                    }),
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
                Tables\Actions\DeleteAction::make(),
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
