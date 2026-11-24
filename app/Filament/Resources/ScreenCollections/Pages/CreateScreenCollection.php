<?php

namespace App\Filament\Resources\ScreenCollections\Pages;

use App\Filament\Resources\ScreenCollections\ScreenCollectionResource;
use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Services\Obs\ScreenCollectionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateScreenCollection extends CreateRecord
{
    protected static string $resource = ScreenCollectionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        $studio = ! empty($data['obs_studio_id'])
            ? ObsStudio::query()->ownedBy($user)->find((int) $data['obs_studio_id'])
            : null;

        /** @var ScreenCollection $collection */
        $collection = app(ScreenCollectionService::class)->createCollection($user, [
            ...$data,
            'with_starter_views' => true,
        ], $studio);

        return $collection;
    }
}
