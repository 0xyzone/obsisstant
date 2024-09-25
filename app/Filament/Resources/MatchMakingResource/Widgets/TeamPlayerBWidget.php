<?php

namespace App\Filament\Resources\MatchMakingResource\Widgets;

use App\Models\Hero;
use Filament\Tables;
use App\Models\TeamPlayer;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamPlayerBWidget extends BaseWidget
{
    public ?Model $record = null;
    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        $record = $this->record;
        return $table
            ->query(
                TeamPlayer::query()->where('team_id', $record->teamB->id)
            )
            ->heading(fn () => $record->teamB->name . ' players')
            ->paginated(false)
            ->deferLoading()
            ->columns([
                TextColumn::make("name"),
                CheckboxColumn::make("is_playing")
                ->label('Playing?')
                ->alignCenter(),
                ToggleColumn::make("is_mvp")
                ->label('Mvp?')
                ->alignCenter()
                ->beforeStateUpdated(function (TeamPlayer $record) {
                    TeamPlayer::where('team_id', $record->team_id)->where('id', '!=', $record->id)->update(['is_mvp' => false]);
                }),
                TextInputColumn::make("kills")
                ->label('K')
                ->type('number')
                ->alignCenter(),
                TextInputColumn::make("deaths")
                ->label('D')
                ->type('number')
                ->alignCenter(),
                TextInputColumn::make("assists")
                ->label('A')
                ->type('number')
                ->alignCenter(),
                TextInputColumn::make("net_worth")
                ->label('Gold')
                ->type('number')
                ->alignCenter()
                ->extraAttributes([
                    'class' => 'w-28'
                ]),
                SelectColumn::make('hero_id')
                ->label('Hero')
                ->options(function ($record) {
                    $game = $record->team->tournament->game_id;
                    $heroes= Hero::where('game_id', $game)->pluck('name', 'id');
                    return $heroes->toArray();
                })
                ->extraAttributes([
                    'class' => 'w-max'
                ]),
                TextInputColumn::make("hero_damage")
                ->type('number')
                ->label('Total Dmg'),
                TextInputColumn::make("turret_damage")
                ->type('number')
                ->label('Turret Dmg'),
                TextInputColumn::make("damage_taken")
                ->type('number')
                ->label('Dmg Taken'),
                TextInputColumn::make("fight_participation")
                ->type('number')
                ->label('Fight Participation'),
            ])
            ->poll('1s');
    }
}
