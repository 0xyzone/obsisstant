<?php

namespace App\Filament\Resources\ObsMacros\Pages;

use App\Filament\Resources\ObsMacros\ObsMacroResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObsMacro extends EditRecord
{
    protected static string $resource = ObsMacroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
