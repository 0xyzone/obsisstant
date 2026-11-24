<?php

namespace App\Filament\Resources\ScreenCollections\Tables;

use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScreenCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studio.name')
                    ->label('Studio')
                    ->placeholder('All Studios'),
                TextColumn::make('views_count')
                    ->counts('views')
                    ->label('Screen Views'),
                TextColumn::make('width')
                    ->label('Canvas')
                    ->formatStateUsing(fn (ScreenCollection $record): string => "{$record->width}×{$record->height}"),
            ])
            ->recordActions([
                Action::make('openStage')
                    ->label('Stage URL')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (ScreenCollection $record): string => $record->getStageUrl(), shouldOpenInNewTab: true),
                Action::make('canvasBuilder')
                    ->label('Canvas Builder')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->color('warning')
                    ->url(function (ScreenCollection $record): ?string {
                        $studio = $record->studio ?? ObsStudio::query()->ownedBy(auth()->user())->orderBy('id')->first();

                        return $studio instanceof ObsStudio
                            ? route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $record->id])
                            : null;
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
