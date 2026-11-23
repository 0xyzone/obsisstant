@php
    $studios = $this->getStudios();
    $logs = $this->getRecentLogs();
    $version = $this->getCurrentVersion();
    $primaryStudio = $studios->firstWhere('is_active', true) ?? $studios->first();
@endphp

<x-filament-widgets::widget>
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
