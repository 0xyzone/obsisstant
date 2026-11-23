<?php

namespace App\Filament\Widgets;

use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\Obs\ObsAgentInstallerService;
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

    /**
     * @return array{
     *     token: string,
     *     expires_in_minutes: int,
     *     mac_command: string,
     *     windows_command: string,
     *     mac_download_url: string,
     *     windows_download_url: string
     * }|null
     */
    public function getInstallerPayload(): ?array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return null;
        }

        return app(ObsAgentInstallerService::class)->issueBootstrapPayload($user);
    }
}
