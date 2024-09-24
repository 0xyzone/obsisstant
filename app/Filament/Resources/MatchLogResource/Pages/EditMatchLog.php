<?php

namespace App\Filament\Resources\MatchLogResource\Pages;

use App\Filament\Resources\MatchLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatchLog extends EditRecord
{
    protected static string $resource = MatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
