<?php

namespace App\Filament\Resources\MatchMakingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MatchMakingResource\Widgets\TeamPlayerWidget;
use App\Filament\Resources\MatchMakingResource\Widgets\TeamPlayerBWidget;
use App\Filament\Resources\MatchMakingResource;

class EditMatchMaking extends EditRecord
{
    protected static string $resource = MatchMakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    public function getFormActions(): array
    {
        return []; // Return an empty array to remove the "Save" action button
    }

    protected function getFooterWidgets(): array
    {
        return [
            TeamPlayerWidget::class,
            TeamPlayerBWidget::class,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getActions(): array
    {
        return []; // Remove all actions, including "Save"
    }
}
