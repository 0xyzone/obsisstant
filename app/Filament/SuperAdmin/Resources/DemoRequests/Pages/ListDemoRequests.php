<?php

namespace App\Filament\SuperAdmin\Resources\DemoRequests\Pages;

use App\Filament\SuperAdmin\Resources\DemoRequests\DemoRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemoRequests extends ListRecords
{
    protected static string $resource = DemoRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
