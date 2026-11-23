<?php

namespace App\Filament\Widgets;

use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\ChangelogService;
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
        $commandsCount = ObsCommandLog::query()->count();

        return [
            Stat::make('Studios Online', $onlineStudios.' / '.$activeStudios)
                ->description($onlineStudios > 0 ? 'Reverb relay connected' : 'Awaiting obs-agent relay')
                ->color($onlineStudios > 0 ? 'success' : 'warning'),
            Stat::make('Live Screen Canvases', (string) $screensCount)
                ->description('Browser Source overlays')
                ->color('primary'),
            Stat::make('Broadcast Macros', (string) $macrosCount)
                ->description('1-click multi-step sequences')
                ->color('primary'),
            Stat::make('Studio Release', 'v'.ChangelogService::getCurrentVersion())
                ->description($commandsCount.' commands dispatched')
                ->color('warning'),
        ];
    }
}
