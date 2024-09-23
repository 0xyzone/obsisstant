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
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
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

    public static function canUpdate(): bool
    {
        return false;
    }

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
                    ->columnSpan(3)
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('match_number')
                    ->required()
                    ->columnSpan(2)
                    ->label('Match Title')
                    ->afterStateUpdated(function ($state, $record) {
                        $record->match_number = $state;
                        $record->save();
                    })
                    ->live(),
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
                        $record->winning_team = $get('winning_team');
                        $record->save();
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
                        ->extraAttributes([
                            'class' => 'w-full h-full py-6'
                        ]),
                ]),
                Section::make('Team A')
                    ->description('Select a team')
                    ->schema([
                        Forms\Components\Select::make('team_a')
                            ->hidden(fn(Get $get): bool => !$get('tournament_id'))
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
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    $record->teamA_match_point = $state;
                                    $record->save();
                                }
                            })
                    ])
                    ->columnSpan(4)
                    ->columns(6),

                // Team B
                Section::make('Team B')
                    ->description('Select a team')
                    ->schema([
                        Forms\Components\Select::make('team_b')
                            ->hidden(fn(Get $get): bool => !$get('tournament_id'))
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
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    $record->teamB_match_point = $state;
                                    $record->save();
                                }
                            })
                    ])
                    ->columnSpan(4)
                    ->columns(6),
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
