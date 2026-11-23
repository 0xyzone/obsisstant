@php
    $studios = $this->getStudios();
    $logs = $this->getRecentLogs();
    $version = $this->getCurrentVersion();
    $primaryStudio = $studios->firstWhere('is_active', true) ?? $studios->first();
    $hasOnlineStudio = $studios->contains(static fn ($item): bool => $item->isOnline());
    $hasReachableAgent = $studios->contains(static fn ($item): bool => $item->isAgentReachable());
    $installer = ! $hasOnlineStudio ? $this->getInstallerPayload() : null;
@endphp

<x-filament-widgets::widget>
    @if (! $hasOnlineStudio && is_array($installer))
        <section
            id="obs-dashboard-install-prompt"
            class="obs-panel"
            style="margin-block-end: 1rem; border-color: rgba(205, 173, 105, 0.45); background: linear-gradient(135deg, rgba(205, 173, 105, 0.08), rgba(18, 18, 16, 0.95));"
            aria-labelledby="dashboard-install-title"
        >
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: grid; gap: 0.25rem; max-inline-size: 46rem;">
                    @if ($hasReachableAgent)
                        <h2 id="dashboard-install-title" class="obs-panel-title" style="color: #F4E185;">
                            OBS Agent is running — waiting for OBS Studio
                        </h2>
                        <p class="obs-panel-sub">
                            Open OBS Studio on your streaming computer, go to <strong>Tools → WebSocket Server Settings</strong>, and check <strong>Enable WebSocket server</strong> (Port 4455).
                        </p>
                    @else
                        <h2 id="dashboard-install-title" class="obs-panel-title" style="color: #F4E185;">
                            OBS Agent is not installed or running yet
                        </h2>
                        <p class="obs-panel-sub">
                            Download and double-click the pre-configured installer on the Mac or Windows PC running OBS Studio — zero code or token copying required.
                        </p>
                    @endif
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <a href="{{ $installer['mac_download_url'] }}" class="obs-action-primary">
                        Download for Mac (.zip)
                    </a>
                    <a href="{{ $installer['windows_download_url'] }}" class="obs-action-primary" style="background: rgba(255,255,255,0.06); color: #F2E8D5; border-color: rgba(205,173,105,0.35);">
                        Download for Windows (.zip)
                    </a>
                    @if ($studios->isEmpty())
                        <a href="{{ route('filament.admin.resources.obs-studios.create') }}" class="obs-link-quiet">
                            Create First Studio
                        </a>
                    @else
                        <a href="{{ route('studio.index') }}" class="obs-link-quiet">
                            Guided Setup in Studio
                        </a>
                    @endif
                </div>
            </div>

            <details style="margin-block-start: 0.75rem;">
                <summary style="cursor: pointer; font-size: 0.8125rem; color: #CDAD69;">
                    Prefer 1-click Terminal / PowerShell setup? (Skips Mac Gatekeeper warnings)
                </summary>
                <div style="display: grid; gap: 0.625rem; margin-block-start: 0.625rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));">
                    <div>
                        <div style="font-size: 0.75rem; color: #A69D8D; margin-block-end: 0.25rem;">macOS Terminal</div>
                        <input
                            type="text"
                            readonly
                            value="{{ $installer['mac_command'] }}"
                            onclick="this.select(); navigator.clipboard?.writeText(this.value);"
                            aria-label="macOS 1-click install command"
                            style="inline-size: 100%; padding: 0.45rem 0.625rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.45); color: #F4E185; font-size: 0.75rem;"
                        >
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: #A69D8D; margin-block-end: 0.25rem;">Windows PowerShell</div>
                        <input
                            type="text"
                            readonly
                            value="{{ $installer['windows_command'] }}"
                            onclick="this.select(); navigator.clipboard?.writeText(this.value);"
                            aria-label="Windows PowerShell 1-click install command"
                            style="inline-size: 100%; padding: 0.45rem 0.625rem; border-radius: 0.375rem; border: 1px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.45); color: #F4E185; font-size: 0.75rem;"
                        >
                    </div>
                </div>
            </details>
        </section>
    @endif

    <div class="obs-workspace-split">
        {{-- Primary 2/3 Section: Connected OBS Studios & Telemetry Table --}}
        <section class="obs-panel obs-workspace-primary" aria-labelledby="studios-heading">
            <div class="obs-panel-head">
                <div>
                    <h2 id="studios-heading" class="obs-panel-title">Studio roster</h2>
                    <p class="obs-panel-sub">Active OBS relays, program scenes, and stream output readiness.</p>
                </div>
                <a href="{{ route('studio.index') }}" class="obs-action-primary">
                    Launch studio center
                </a>
            </div>

            <div tabindex="0" role="region" aria-labelledby="studios-heading" class="obs-table-wrap">
                <table class="obs-data-table">
                    <thead>
                        <tr>
                            <th scope="col">Studio</th>
                            <th scope="col">State</th>
                            <th scope="col">Program scene</th>
                            <th scope="col" class="obs-num-col">FPS</th>
                            <th scope="col" class="obs-num-col">CPU</th>
                            <th scope="col" class="obs-num-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($studios as $studio)
                            @php
                                $stats = $studio->getStats();
                                $isOnline = $studio->isOnline();
                                $statusLabel = match ($studio->status) {
                                    'streaming' => 'Streaming',
                                    'recording' => 'Recording',
                                    'online' => $isOnline ? 'Online' : 'Idle',
                                    default => 'Offline',
                                };
                            @endphp
                            <tr>
                                <th scope="row">
                                    <div class="obs-studio-cell">
                                        <span class="obs-studio-name">{{ $studio->name }}</span>
                                        <span class="obs-studio-meta">{{ $studio->screens_count }} screens · {{ $studio->macros_count }} macros</span>
                                    </div>
                                </th>
                                <td class="obs-cell-state">
                                    <span class="obs-status-inline {{ $isOnline ? 'is-live' : '' }}">
                                        <span class="obs-status-dot" aria-hidden="true"></span>
                                        <span>{{ $statusLabel }}</span>
                                    </span>
                                </td>
                                <td class="obs-cell-scene">
                                    {{ $studio->getCurrentProgramScene() ?? 'Not synced' }}
                                </td>
                                <td class="obs-num-col obs-cell-fps">
                                    {{ isset($stats['activeFps']) ? number_format((float) $stats['activeFps'], 0) : '—' }}
                                </td>
                                <td class="obs-num-col obs-cell-cpu">
                                    {{ isset($stats['cpuUsage']) ? number_format((float) $stats['cpuUsage'], 1).'%' : '—' }}
                                </td>
                                <td class="obs-num-col obs-cell-actions">
                                    <a href="{{ route('obs.deck.show', ['studio' => $studio->slug]) }}" target="_blank" class="obs-link-quiet">
                                        Mobile deck
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="obs-empty-row">
                                    No OBS studios configured yet. Add one in Studios to connect obs-agent.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Secondary 1/3 Section: Recent Command Log & Quick Shortcuts --}}
        <aside class="obs-panel obs-workspace-side" aria-labelledby="dispatch-heading">
            <div class="obs-panel-head">
                <div>
                    <h2 id="dispatch-heading" class="obs-panel-title">Recent commands</h2>
                    <p class="obs-panel-sub">Latest Reverb dispatches and studio shortcuts.</p>
                </div>
                <span class="obs-version-meta">v{{ $version }}</span>
            </div>

            <div class="obs-command-list">
                @forelse ($logs as $log)
                    <div class="obs-command-row">
                        <div class="obs-command-main">
                            <span class="obs-command-name">{{ $log->request_type }}</span>
                            <span class="obs-command-studio">{{ $log->studio?->name ?? 'Studio' }} · {{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                        <span class="obs-command-status {{ $log->status === 'failed' ? 'is-error' : ($log->status === 'succeeded' ? 'is-ok' : '') }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                @empty
                    <p class="obs-empty-note">No commands dispatched yet. Trigger a scene switch or macro to view delivery latency.</p>
                @endforelse
            </div>

            <div class="obs-shortcut-stack">
                <a href="{{ route('filament.admin.resources.obs-studios.index') }}" class="obs-shortcut-link">
                    <span>Manage studios &amp; tokens</span>
                    <span class="obs-shortcut-hint">Studios</span>
                </a>
                <a href="{{ route('filament.admin.resources.obs-screens.index') }}" class="obs-shortcut-link">
                    <span>Design live browser screens</span>
                    <span class="obs-shortcut-hint">Screens</span>
                </a>
                @if ($primaryStudio)
                    <a href="{{ route('obs.deck.show', ['studio' => $primaryStudio->slug]) }}" target="_blank" class="obs-shortcut-link">
                        <span>Open mobile deck ({{ $primaryStudio->name }})</span>
                        <span class="obs-shortcut-hint">Deck</span>
                    </a>
                @endif
            </div>
        </aside>
    </div>
</x-filament-widgets::widget>
