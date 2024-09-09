<?php

namespace App\Filament\Resources\MatchMakingResource\Widgets;

use App\Models\Hero;
use Filament\Tables;
use App\Models\TeamPlayer;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\SelectColumn;
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
            ->heading('Team B Players')
            ->paginated(false)
            ->columns([
                TextColumn::make("name"),
                CheckboxColumn::make("is_playing")
                ->label('Playing?')
                ->alignCenter(),
                CheckboxColumn::make("is_mvp")
                ->label('Mvp?')
                ->alignCenter(),
                TextInputColumn::make("kills")
                ->label('K')
                ->alignCenter(),
                TextInputColumn::make("deaths")
                ->label('D')
                ->alignCenter(),
                TextInputColumn::make("assists")
                ->label('A')
                ->alignCenter(),
                TextInputColumn::make("net_worth")
                ->label('Gold')
                ->alignCenter()
                ->extraAttributes([
                    'class' => 'w-28'
                ]),
                SelectColumn::make('hero_id')
                ->options(function ($record) {
                    $game = $record->team->tournament->game_id;
                    $heroes= Hero::where('game_id', $game)->pluck('name', 'id');
                    return $heroes->toArray();
                })
                ->extraAttributes([
                    'class' => 'w-max'
                ]),
                TextInputColumn::make("hero_damage")
                ->label('Total Dmg'),
                TextInputColumn::make("turret_damage")
                ->label('Turret Dmg'),
                TextInputColumn::make("damage_taken")
                ->label('Dmg Taken'),
                TextInputColumn::make("fight_participation")
                ->label('Fight Participation'),
            ]);
    }
}
