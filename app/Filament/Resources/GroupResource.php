<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use App\Models\Group;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\GroupResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\GroupResource\RelationManagers;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'phosphor-table-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-table-fill';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tournament_id')
                    ->relationship('tournament', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(2)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('tournament_id', $state);
                    }),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\Toggle::make('active')
                    ->required()
                    ->columnSpan(1),
                Repeater::make('groupTeams')
                    ->relationship()
                    ->schema([
                        Select::make('team_id')
                            ->options(function (Get $get): array {
                                return Team::where('tournament_id', $get('../../tournament_id'))->pluck('name', 'id')->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columnSpanFull()
                    ->grid(4)
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
            ])
            ->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tournament.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('groupTeams.team.name')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(2)
                    ->expandableLimitedList(),
                Tables\Columns\ToggleColumn::make('active'),
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
