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
        return ObsCommandLog::query()
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
