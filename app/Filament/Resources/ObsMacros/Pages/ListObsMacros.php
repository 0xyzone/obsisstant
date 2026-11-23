<?php

namespace App\Filament\Resources\ObsMacros\Pages;

use App\Filament\Resources\ObsMacros\ObsMacroResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListObsMacros extends ListRecords
{
    protected static string $resource = ObsMacroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
