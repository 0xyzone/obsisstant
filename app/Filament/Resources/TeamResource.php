<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('short_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('tournament_id')
                    ->relationship(
                        'tournament',
                        'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('type', 'team')->orWhere('type', 'ffa')
                    )
                    ->searchable(['name', 'id'])
                    ->required()
                    ->preload(),
                Forms\Components\FileUpload::make('team_logo_path')
                    ->image()
                    ->imageEditor(),
                Forms\Components\TextInput::make('p')
                    ->hiddenOn('create')
                    ->numeric(),
                Forms\Components\TextInput::make('w')
                    ->hiddenOn('create')
                    ->numeric(),
                Forms\Components\TextInput::make('l')
                    ->hiddenOn('create')
                    ->numeric(),
                Forms\Components\TextInput::make('d')
                    ->hiddenOn('create')
                    ->numeric(),
                Forms\Components\TextInput::make('f')
                    ->hiddenOn('create')
                    ->numeric(),
                Forms\Components\TextInput::make('pts')
                    ->hiddenOn('create')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('team_logo_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tournament.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('p')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('w')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('l')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('d')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('f')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pts')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
