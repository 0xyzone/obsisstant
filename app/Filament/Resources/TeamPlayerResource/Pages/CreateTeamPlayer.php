<?php

namespace App\Filament\Resources\TeamPlayerResource\Pages;

use App\Filament\Resources\TeamPlayerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamPlayer extends CreateRecord
{
    protected static string $resource = TeamPlayerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
