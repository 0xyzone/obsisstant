<?php

namespace App\Filament\Resources\TeamPlayerResource\Pages;

use App\Filament\Resources\TeamPlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeamPlayers extends ListRecords
{
    protected static string $resource = TeamPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
