<?php

namespace App\Filament\Resources\MatchMakingResource\Pages;

use App\Models\Team;
use Filament\Actions;
use Filament\Forms\Get;
use App\Models\MatchMaking;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MatchMakingResource;
use App\Filament\Resources\MatchMakingResource\Widgets\TeamPlayerWidget;
use App\Filament\Resources\MatchMakingResource\Widgets\TeamPlayerBWidget;

class EditMatchMaking extends EditRecord
{
    protected static string $resource = MatchMakingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('New Match')
            ->model(MatchMaking::class)
                ->form([
                    Select::make('tournament_id')
                        ->relationship('tournament', 'name')
                        ->unique()
                        ->required()
                        ->validationMessages([
                            'unique' => 'Match for this :attribute has already been created.',
                        ])
                        ->live()
                        ->columnSpan(4)
                        ->disabledOn('edit'),
                    Select::make('team_a')
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                        ->options(function (Get $get): array {
                            return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                        })
                        ->live()
                        ->afterStateUpdated(function (Get $get, $record) {
                            if ($record) {
                                $record->team_a = $get('team_a');
                                $record->save();
                            }
                        })
                        ->columnSpan(5),
                    Select::make('team_b')
                    ->hidden(fn(Get $get): bool => !$get('tournament_id'))
                        ->options(function (Get $get): array {
                            return Team::where('tournament_id', $get('tournament_id'))->pluck('name', 'id')->toArray();
                        })
                        ->live()
                        ->afterStateUpdated(function (Get $get, $record) {
                            if ($record) {
                                $record->team_b = $get('team_b');
                                $record->save();
                            }
                        })
                        ->columnSpan(5),
                ])
                ->successRedirectUrl(fn (Model $record): string => route('filament.admin.resources.match-makings.edit', $record)),
            Actions\DeleteAction::make(),
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
