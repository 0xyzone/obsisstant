<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Tournament;
use Filament\Tables\Table;
use App\Enums\TournamentType;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TournamentResource\Pages;
use App\Filament\Resources\TournamentResource\RelationManagers;

class TournamentResource extends Resource
{
    protected static ?string $model = Tournament::class;

    protected static ?string $navigationIcon = 'phosphor-trophy-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-trophy-fill';
    protected static ?int $navigationSort = 3;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name'),
                ImageEntry::make('game.game_logo_path')
                ->label('')
                ->width("auto")
                ->height(100),
                ColorEntry::make('themes.primary_color')
                ->label('Primary'),
                ColorEntry::make('themes.secondary_color')
                ->label('Secondary'),
                ColorEntry::make('themes.acsent_color')
                ->label('Accent'),
            ]);
    }

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
                Repeater::make('themes')
                    ->relationship()
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->rgba()
                            ->live(),
                        ColorPicker::make('secondary_color')
                            ->rgba()
                            ->live(),
                        ColorPicker::make('acsent_color')
                            ->label('Accent Color')
                            ->rgba()
                            ->live(),
                    ])
                    ->maxItems(1)
                    ->columns(3)
                    ->defaultItems(1)
                    ->columnSpanFull(),
                Repeater::make('asset')
                    ->relationship()
                    ->schema([
                        FileUpload::make('tournament_logo')
                            ->label('Logo (1:1)')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->imagePreviewHeight('250')
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
                        FileUpload::make('square_bg')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->imagePreviewHeight('250')
                            ->directory('tournamentAssets/square_bg')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
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
                            ->openable()
                            ->downloadable()
                            ->moveFiles(),

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
                Tables\Columns\ColorColumn::make('themes.primary_color')
                    ->label('Primary Color')
                    ->copyable()
                    ->copyMessage('Color code copied')
                    ->copyMessageDuration(1500)
                    ->alignCenter(),
                Tables\Columns\ColorColumn::make('themes.secondary_color')
                    ->label('Secondary Color')
                    ->copyable()
                    ->copyMessage('Color code copied')
                    ->copyMessageDuration(1500)
                    ->alignCenter(),
                Tables\Columns\ColorColumn::make('themes.acsent_color')
                    ->label('Accent Color')
                    ->copyable()
                    ->copyMessage('Color code copied')
                    ->copyMessageDuration(1500)
                    ->alignCenter(),
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
                Tables\Actions\ViewAction::make(),
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
            'edit' => Pages\EditTournament::route('/{record}/edit'),
            'view' => Pages\ViewTournaments::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewTournaments::class,
            Pages\EditTournament::class,
        ]);
    }
}
