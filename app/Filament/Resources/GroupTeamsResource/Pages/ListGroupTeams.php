<?php

namespace App\Filament\Resources\GroupTeamsResource\Pages;

use App\Filament\Resources\GroupTeamsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupTeams extends ListRecords
{
    protected static string $resource = GroupTeamsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
