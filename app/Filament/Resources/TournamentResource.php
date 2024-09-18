<?php

namespace App\Filament\Resources;

use App\Enums\TournamentType;
use App\Filament\Resources\TournamentResource\Pages;
use App\Filament\Resources\TournamentResource\RelationManagers;
use App\Models\Tournament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TournamentResource extends Resource
{
    protected static ?string $model = Tournament::class;

    protected static ?string $navigationIcon = 'phosphor-trophy-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-trophy-fill';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('game_id')
                    ->relationship('game', 'name')
                    ->disabledOn('edit')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->hiddenOn('edit')
                    ->options(TournamentType::class)
                    ->default('team')
                    ->disablePlaceholderSelection(),
                Repeater::make('asset')
                    ->relationship()
                    ->schema([
                        FileUpload::make('tournament_logo')
                            ->label('Logo (1:1)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->directory('tournamentAssets/logo')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->moveFiles(),
                        FileUpload::make('background')
                            ->label('Banner (16:9)')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                            ])
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->directory('tournamentAssets/background')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->moveFiles(),
                        FileUpload::make('video_background')
                            ->directory('tournamentAssets/video_background')
                            ->columnSpanFull(),

                    ])
                    ->defaultItems(1)
                    ->columns(2)
                    ->maxItems(1)
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->alignRight()
                    ->width('10px'),
                Tables\Columns\TextInputColumn::make('name')
                    ->searchable()
                    ->extraAttributes([
                        'class' => 'w-full'
                    ]),
                Tables\Columns\BadgeColumn::make('type')
                    ->extraAttributes([
                        'class' => 'capitalize'
                    ]),
                Tables\Columns\ImageColumn::make('game.game_logo_path'),
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
            ])
            ->groups([
                'type'
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
            'index' => Pages\ListTournaments::route('/'),
            'create' => Pages\CreateTournament::route('/create'),
            // 'edit' => Pages\EditTournament::route('/{record}/edit'),
        ];
    }
}
