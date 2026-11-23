<?php

namespace App\Filament\Resources\ObsScreens\Pages;

use App\Events\ObsScreenUpdated;
use App\Filament\Resources\ObsScreens\ObsScreenResource;
use App\Models\ObsScreen;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObsScreen extends EditRecord
{
    protected static string $resource = ObsScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if ($record instanceof ObsScreen) {
            event(new ObsScreenUpdated($record));
        }
    }
}
