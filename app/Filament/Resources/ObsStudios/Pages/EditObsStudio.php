<?php

namespace App\Filament\Resources\ObsStudios\Pages;

use App\Filament\Resources\ObsStudios\ObsStudioResource;
use App\Models\ObsStudio;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObsStudio extends EditRecord
{
    protected static string $resource = ObsStudioResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ObsStudio $record */
        $record = $this->getRecord();
        $data['agent_token'] = $record->agent_token;
        $data['obs_password'] = $record->obs_password;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
