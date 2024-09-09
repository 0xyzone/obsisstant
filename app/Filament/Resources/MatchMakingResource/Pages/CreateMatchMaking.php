<?php

namespace App\Filament\Resources\MatchMakingResource\Pages;

use App\Filament\Resources\MatchMakingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMatchMaking extends CreateRecord
{
    protected static string $resource = MatchMakingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
