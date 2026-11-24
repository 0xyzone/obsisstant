<?php

namespace App\Filament\Resources\ScreenCollections\Pages;

use App\Filament\Resources\ScreenCollections\ScreenCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScreenCollections extends ListRecords
{
    protected static string $resource = ScreenCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
