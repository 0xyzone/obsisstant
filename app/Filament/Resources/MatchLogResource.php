<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\MatchLog;
use Filament\Forms\Form;
use Pages\ViewMatchLogs;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\MatchLogResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MatchLogResource\RelationManagers;

class MatchLogResource extends Resource
{
    protected static ?string $model = MatchLog::class;

    protected static ?string $navigationIcon = 'phosphor-floppy-disk-back-duotone';
    protected static ?string $activeNavigationIcon = 'phosphor-floppy-disk-back-fill';
    protected static ?int $navigationSort = 9;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('tournament.name'),
                TextEntry::make('match_title'),
                ViewEntry::make('data')
                ->label('')
                ->columnSpanFull()
                ->view('infolists.components.team-arooster'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tournament.name')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('match_title')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('teamA.name')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('teamB.name')
                    ->searchable(isIndividual: true),
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
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListMatchLogs::route('/'),
            'view' => Pages\ViewMatchLog::route('/{record}'),
        ];
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewMatchLog::class,
        ]);
    }
}
