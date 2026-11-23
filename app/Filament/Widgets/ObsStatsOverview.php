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
        $activeStudios = ObsStudio::query()->where('is_active', true)->count();
        $onlineStudios = ObsStudio::query()
            ->whereIn('status', ['online', 'streaming', 'recording'])
            ->count();
        $screensCount = ObsScreen::query()->count();
        $macrosCount = ObsMacro::query()->count();
        $recentCommands = ObsCommandLog::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $totalCommands = ObsCommandLog::query()->count();

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
