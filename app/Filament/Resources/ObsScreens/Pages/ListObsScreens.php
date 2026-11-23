<?php

namespace App\Filament\Resources\ObsScreens\Pages;

use App\Filament\Resources\ObsScreens\ObsScreenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObsScreens extends ListRecords
{
    protected static string $resource = ObsScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
