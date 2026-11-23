<?php

namespace App\Filament\Resources\ObsStudios\Pages;

use App\Filament\Resources\ObsStudios\ObsStudioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObsStudios extends ListRecords
{
    protected static string $resource = ObsStudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
