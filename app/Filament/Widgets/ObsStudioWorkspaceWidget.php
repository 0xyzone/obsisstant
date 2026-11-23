<?php

namespace App\Filament\Widgets;

use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Services\ChangelogService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class ObsStudioWorkspaceWidget extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @var view-string */
    protected string $view = 'filament.widgets.obs-studio-workspace-widget';

    /**
     * @return Collection<int, ObsStudio>
     */
    public function getStudios(): Collection
    {
        return ObsStudio::query()
            ->ownedBy(auth()->user())
            ->withCount(['screens', 'macros'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ObsCommandLog>
     */
    public function getRecentLogs(): Collection
    {
        $studioIds = ObsStudio::query()
            ->ownedBy(auth()->user())
            ->pluck('id');

        return ObsCommandLog::query()
            ->whereIn('obs_studio_id', $studioIds)
            ->with('studio')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function getCurrentVersion(): string
    {
        return ChangelogService::getCurrentVersion();
    }
}
