<?php

namespace App\Filament\Resources\ObsMacros\Pages;

use App\Filament\Resources\ObsMacros\ObsMacroResource;
use App\Services\Obs\ObsRequestCatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateObsMacro extends CreateRecord
{
    protected static string $resource = ObsMacroResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['steps'] = ObsRequestCatalog::packSteps($data['steps'] ?? []);

        return $data;
    }
}
