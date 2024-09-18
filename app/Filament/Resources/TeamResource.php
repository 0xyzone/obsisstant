<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Imports\TeamImporter;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TeamResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TeamResource\RelationManagers;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'phosphor-microsoft-teams-logo-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-microsoft-teams-logo-fill';
    protected static ?int $navigationSort = 4;

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
                    ->imageEditor()
                    ->directory('teamLogoes'),
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
                Tables\Columns\ImageColumn::make('team_logo_path'),
                Tables\Columns\TextColumn::make('tournament.name')
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
            ->headerActions([
                ImportAction::make()
                    ->importer(TeamImporter::class)
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
