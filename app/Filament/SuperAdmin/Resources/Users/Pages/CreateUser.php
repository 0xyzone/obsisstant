<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\Subscription;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $initialPackageId = null;

    protected mixed $initialEndsAt = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialPackageId = filled($data['initial_package_id'] ?? null) ? (int) $data['initial_package_id'] : null;
        $this->initialEndsAt = $data['initial_ends_at'] ?? null;

        unset($data['initial_package_id'], $data['initial_ends_at']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialPackageId === null) {
            return;
        }

        /** @var User $user */
        $user = $this->getRecord();

        Subscription::query()->create([
            'user_id' => $user->id,
            'package_id' => $this->initialPackageId,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $this->initialEndsAt,
        ]);
    }
}
