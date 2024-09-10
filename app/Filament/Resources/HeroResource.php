<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Hero;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Grouping\Group;
use App\Filament\Imports\HeroImporter;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\HeroResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\HeroResource\RelationManagers;

class HeroResource extends Resource
{
    protected static ?string $model = Hero::class;

    protected static ?string $navigationIcon = 'phosphor-hand-fist-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-hand-fist-fill';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('game_id')
                    ->relationship('game', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\FileUpload::make('hero_image_path')
                    ->image()
                    ->required()
                    ->directory('assets/hero_image')
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('game.game_logo_path'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('hero_image_path'),
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
                    ->importer(HeroImporter::class)
            ])
            ->groups([
                Group::make('game.name')
                ->collapsible()
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
            'index' => Pages\ListHeroes::route('/'),
            'create' => Pages\CreateHero::route('/create'),
            // 'edit' => Pages\EditHero::route('/{record}/edit'),
        ];
    }
}
