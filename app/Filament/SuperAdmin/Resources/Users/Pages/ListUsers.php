<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?int $initialPackageId = null;

    protected mixed $initialEndsAt = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $this->initialPackageId = filled($data['initial_package_id'] ?? null)
                        ? (int) $data['initial_package_id']
                        : null;
                    $neverExpires = (bool) ($data['initial_never_expires'] ?? false);
                    $this->initialEndsAt = $neverExpires ? null : ($data['initial_ends_at'] ?? null);

                    unset($data['initial_package_id'], $data['initial_ends_at'], $data['initial_never_expires']);

                    return $data;
                })
                ->after(function (User $record): void {
                    if ($this->initialPackageId === null) {
                        return;
                    }

                    Subscription::query()->create([
                        'user_id' => $record->id,
                        'package_id' => $this->initialPackageId,
                        'status' => Subscription::STATUS_ACTIVE,
                        'starts_at' => now(),
                        'ends_at' => $this->initialEndsAt,
                    ]);
                }),
        ];
    }
}
