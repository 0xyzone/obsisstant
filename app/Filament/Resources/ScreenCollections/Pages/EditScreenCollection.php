<?php

namespace App\Filament\Resources\ScreenCollections\Pages;

use App\Filament\Resources\ScreenCollections\ScreenCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScreenCollection extends EditRecord
{
    protected static string $resource = ScreenCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
