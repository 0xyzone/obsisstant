<?php

namespace App\Filament\Resources\ObsMacros\Pages;

use App\Filament\Resources\ObsMacros\ObsMacroResource;
use App\Services\Obs\ObsRequestCatalog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObsMacro extends EditRecord
{
    protected static string $resource = ObsMacroResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $studioId = isset($data['obs_studio_id']) ? (int) $data['obs_studio_id'] : null;
        $data['steps'] = ObsRequestCatalog::unpackSteps(
            is_array($data['steps'] ?? null) ? $data['steps'] : [],
            $studioId,
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['steps'] = ObsRequestCatalog::packSteps($data['steps'] ?? []);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
