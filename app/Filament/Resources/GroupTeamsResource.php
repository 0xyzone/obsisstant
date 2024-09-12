<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\GroupTeams;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\GroupTeamsResource\Pages;
use App\Filament\Resources\GroupTeamsResource\RelationManagers;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;

class GroupTeamsResource extends Resource
{
    protected static ?string $model = GroupTeams::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $activeNavigationIcon = 'heroicon-s-rectangle-stack';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
                Forms\Components\Toggle::make('qualified')
                    ->required(),
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group.tournament.name'),
                Tables\Columns\TextColumn::make('group.name'),
                Tables\Columns\ToggleColumn::make('qualified'),
                Tables\Columns\TextColumn::make('team.name'),
                Tables\Columns\TextColumn::make('team.p')
                    ->label('P')
                    ->numeric(),
                Tables\Columns\TextInputColumn::make('team.w')
                    ->label('W')
                    ->afterStateUpdated(function ($record) {
                        $team = $record->team;
                        $p = $team->w + $team->l + $team->d + $team->f;
                        $pts = ($team->w * 3) + $team->d;
                        $team->update(['p' => $p, 'pts' => $pts]);
                        $record->update(['pts' => $pts]);
                    }),
                Tables\Columns\TextInputColumn::make('team.l')
                    ->label('L')
                    ->afterStateUpdated(function ($record) {
                        $team = $record->team;
                        $p = $team->w + $team->l + $team->d + $team->f;
                        $pts = ($team->w * 3) + $team->d;
                        $team->update(['p' => $p, 'pts' => $pts]);
                        $record->update(['pts' => $pts]);
                    }),
                Tables\Columns\TextInputColumn::make('team.d')
                    ->label('D')
                    ->afterStateUpdated(function ($record) {
                        $team = $record->team;
                        $p = $team->w + $team->l + $team->d + $team->f;
                        $pts = ($team->w * 3) + $team->d;
                        $team->update(['p' => $p, 'pts' => $pts]);
                        $record->update(['pts' => $pts]);
                    }),
                Tables\Columns\TextInputColumn::make('team.f')
                    ->label('F')
                    ->afterStateUpdated(function ($record) {
                        $team = $record->team;
                        $p = $team->w + $team->l + $team->d + $team->f;
                        $pts = ($team->w * 3) + $team->d;
                        $team->update(['p' => $p, 'pts' => $pts]);
                        $record->update(['pts' => $pts]);
                    }),
                Tables\Columns\TextColumn::make('team.pts')
                    ->label('Pts.')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->poll('1s')
            ->filters([
                SelectFilter::make('group.tournament.name')
                    ->label('Tournament')
                    ->relationship('group.tournament', 'name')
                    ->selectablePlaceholder(false),
                SelectFilter::make('group')
                    ->relationship('group', 'name')
                    ->selectablePlaceholder(false)
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGroupTeams::route('/'),
            'create' => Pages\CreateGroupTeams::route('/create'),
            'edit' => Pages\EditGroupTeams::route('/{record}/edit'),
        ];
    }
}
