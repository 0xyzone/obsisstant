<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TeamPlayer;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Imports\TeamPlayerImporter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TeamPlayerResource\Pages;
use App\Filament\Resources\TeamPlayerResource\RelationManagers;

class TeamPlayerResource extends Resource
{
    protected static ?string $model = TeamPlayer::class;

    protected static ?string $navigationIcon = 'phosphor-users-three-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-users-three-fill';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
                Forms\Components\FileUpload::make('player_image_path')
                    ->label('Photo')
                    ->image(),
                Checkbox::make('is_playing')
                    ->default(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('team.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('player_image_path'),
                Tables\Columns\CheckboxColumn::make('is_playing'),
                Tables\Columns\CheckboxColumn::make('is_mvp'),
                Tables\Columns\TextColumn::make('kills')
                    ->numeric(),
                Tables\Columns\TextColumn::make('deaths')
                    ->numeric(),
                Tables\Columns\TextColumn::make('assists')
                    ->numeric(),
                Tables\Columns\TextColumn::make('net_worth')
                    ->numeric(),
                Tables\Columns\TextColumn::make('hero.name')
                    ->numeric(),
                Tables\Columns\TextColumn::make('hero_damage')
                    ->numeric(),
                Tables\Columns\TextColumn::make('turret_damage')
                    ->numeric(),
                Tables\Columns\TextColumn::make('damage_taken')
                    ->numeric(),
                Tables\Columns\TextColumn::make('fight_participation')
                    ->searchable(),
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
                    ->importer(TeamPlayerImporter::class)
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
            'index' => Pages\ListTeamPlayers::route('/'),
            'create' => Pages\CreateTeamPlayer::route('/create'),
            'edit' => Pages\EditTeamPlayer::route('/{record}/edit'),
        ];
    }
}
