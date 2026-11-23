<?php

namespace App\Filament\Widgets;

use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ObsStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $studioIds = ObsStudio::query()
            ->ownedBy($user)
            ->pluck('id');

        $activeStudios = ObsStudio::query()->ownedBy($user)->where('is_active', true)->count();
        $onlineStudios = ObsStudio::query()
            ->ownedBy($user)
            ->whereIn('status', ['online', 'streaming', 'recording'])
            ->count();
        $screensCount = ObsScreen::query()->ownedBy($user)->count();
        $macrosCount = ObsMacro::query()->ownedBy($user)->count();
        $recentCommands = ObsCommandLog::query()
            ->whereIn('obs_studio_id', $studioIds)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $totalCommands = ObsCommandLog::query()
            ->whereIn('obs_studio_id', $studioIds)
            ->count();

        return [
            Stat::make('Studios online', $onlineStudios.' / '.$activeStudios)
                ->description($onlineStudios > 0 ? $onlineStudios.' active relay connected' : $activeStudios.' active studio registered')
                ->color($onlineStudios > 0 ? 'primary' : 'gray'),
            Stat::make('Live screens', (string) $screensCount)
                ->description('Browser source overlays')
                ->color('gray'),
            Stat::make('Broadcast macros', (string) $macrosCount)
                ->description('Multi-step sequences')
                ->color('gray'),
            Stat::make('Commands (24h)', (string) $recentCommands)
                ->description($totalCommands.' total dispatched')
                ->color('gray'),
        ];
    }
}
