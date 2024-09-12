<?php

namespace App\Filament\Resources\GroupTeamsResource\Pages;

use App\Filament\Resources\GroupTeamsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroupTeams extends EditRecord
{
    protected static string $resource = GroupTeamsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
