<?php

namespace App\Filament\Resources\MatchMakingResource\Pages;

use App\Filament\Resources\MatchMakingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatchMaking extends EditRecord
{
    protected static string $resource = MatchMakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
