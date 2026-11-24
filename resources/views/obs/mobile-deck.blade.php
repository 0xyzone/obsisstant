<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $studio->name }} · Mobile Control Deck</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;
                --bg: #070706;
                --glass: linear-gradient(155deg, rgba(41, 44, 49, 0.56) 0%, rgba(7, 7, 6, 0.82) 55%, rgba(45, 31, 17, 0.54) 100%);
                --border-gold: rgba(205, 173, 105, 0.36);
                --gold: #F4E185;
                --gold-strong: #CDAD69;
                --text: #FAF7EE;
                --muted: #A99F8D;
            }

            *, *::before, *::after { box-sizing: border-box; }
            html, body {
                margin: 0;
                min-block-size: 100dvb;
                background:
                    radial-gradient(circle at 14% 10%, rgba(244, 225, 133, 0.14), transparent 40%),
                    radial-gradient(circle at 86% 20%, rgba(137, 97, 43, 0.22), transparent 46%),
                    radial-gradient(circle at 30% 85%, rgba(45, 31, 17, 0.52), transparent 50%),
                    #070706;
                color: var(--text);
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            }

            .shell {
                inline-size: min(100%, 56rem);
                margin-inline: auto;
                padding: max(0.85rem, env(safe-area-inset-top)) 1rem max(2.5rem, env(safe-area-inset-bottom));
                display: grid;
                gap: 1rem;
            }

            .glass {
                background: var(--glass);
                -webkit-backdrop-filter: blur(18px);
                backdrop-filter: blur(18px);
                border: 1px solid var(--border-gold);
                border-radius: 1.35rem;
                padding: 1rem;
                box-shadow: 0 18px 44px -18px rgba(0, 0, 0, 0.8);
            }

            .topbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .obs-deck-brand-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .obs-deck-brand-link {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                color: var(--text);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 600;
                letter-spacing: -0.01em;
            }

            .obs-deck-brand-link:hover {
                color: var(--gold);
            }

            .obs-brand-version-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                border: 1px solid var(--border-gold);
                background: rgba(205, 173, 105, 0.12);
                color: var(--gold);
                font-variant-numeric: tabular-nums;
                font-size: 0.6875rem;
                font-weight: 500;
                line-height: 1.35;
                text-decoration: none;
            }

            .obs-reverb-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4375rem;
                padding: 0.25rem 0.625rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
                line-height: 1.3;
                white-space: nowrap;
                border: 1px solid rgba(255, 255, 255, 0.14);
                background: rgba(255, 255, 255, 0.05);
                color: #C8C0AF;
            }

            .obs-reverb-dot {
                inline-size: 0.4375rem;
                block-size: 0.4375rem;
                flex-shrink: 0;
                border-radius: 50%;
                background-color: #8E8777;
            }

            .obs-reverb-pill.is-active {
                border-color: rgba(76, 195, 138, 0.34);
                background-color: rgba(76, 195, 138, 0.08);
                color: #9EE6C3;
            }

            .obs-reverb-pill.is-active .obs-reverb-dot {
                background-color: #4CC38A;
            }

            .obs-reverb-pill.is-offline {
                border-color: rgba(229, 72, 77, 0.32);
                background-color: rgba(229, 72, 77, 0.08);
                color: #F4A3A6;
            }

            .obs-reverb-pill.is-offline .obs-reverb-dot {
                background-color: #E5484D;
            }

            .status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.35rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                border: 1px solid var(--border-gold);
                background: rgba(255, 255, 255, 0.05);
            }

            .status-pill--online { color: #86efac; border-color: rgba(74, 222, 128, 0.45); }
            .status-pill--streaming { color: #fca5a5; border-color: rgba(248, 113, 113, 0.55); }

            .deck-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.65rem;
            }

            @media (min-width: 640px) {
                .deck-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            .deck-btn {
                min-block-size: 3.5rem;
                padding: 0.75rem;
                border-radius: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.045);
                color: var(--text);
                font: inherit;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                text-align: center;
                transition: border-color 150ms ease, background-color 150ms ease;
            }

            .deck-btn:hover { border-color: var(--gold); }
            .deck-btn--accent {
                border-color: rgba(205, 173, 105, 0.44);
                color: var(--gold);
                background: rgba(205, 173, 105, 0.08);
            }
            .deck-btn--active {
                background: linear-gradient(135deg, rgba(76, 195, 138, 0.24), rgba(20, 62, 42, 0.36));
                border-color: rgba(76, 195, 138, 0.65);
                color: #9EE6C3;
            }
            .deck-input {
                min-block-size: 2.5rem;
                border-radius: 0.75rem;
                padding: 0.45rem 0.75rem;
                background: #0d0c09;
                color: var(--text);
                border: 1px solid rgba(255, 255, 255, 0.14);
                font: inherit;
                font-size: 0.85rem;
                inline-size: 100%;
            }
            .deck-unified-stack {
                display: grid;
                gap: 0.9rem;
            }
            #deck-classic-scenes-section[hidden],
            #deck-unified-screen-section[hidden],
            #deck-legacy-overlay-section[hidden] {
                display: none !important;
            }

            .section-title {
                margin: 0 0 0.75rem;
                font-size: 0.78rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--gold);
            }

            .toast {
                position: fixed;
                inset-block-end: 1rem;
                inset-inline: 1rem;
                max-inline-size: 28rem;
                margin-inline: auto;
                padding: 0.75rem 1rem;
                border-radius: 9999px;
                background: rgba(18, 16, 12, 0.95);
                border: 1px solid var(--gold);
                color: var(--gold);
                text-align: center;
                font-size: 0.85rem;
                font-weight: 600;
                z-index: 60;
            }

            .toast--error {
                border-color: rgba(248, 113, 113, 0.7);
                color: #fecaca;
                background: rgba(40, 14, 14, 0.96);
            }

            .toast[hidden] { display: none; }

            .deck-select {
                min-block-size: 2.25rem;
                padding: 0.35rem 0.65rem;
                border-radius: 0.75rem;
                border: 1px solid var(--border-gold);
                background: rgba(13, 12, 9, 0.9);
                color: var(--text);
                font: inherit;
                font-size: 0.8125rem;
            }

            .deck-offline-banner {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.85rem 1rem;
                border-radius: 1rem;
                border: 1px solid rgba(244, 225, 133, 0.42);
                background: linear-gradient(135deg, rgba(205, 173, 105, 0.14), rgba(18, 16, 12, 0.94));
            }

            .deck-offline-banner[hidden] { display: none; }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="glass topbar">
                <div style="display: grid; gap: 0.25rem;">
                    <div class="obs-deck-brand-row">
                        <a href="{{ route('home') }}" class="obs-deck-brand-link" aria-label="Obsisstant home">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CDAD69" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                                <circle cx="12" cy="12" r="2.5" fill="#CDAD69"/>
                            </svg>
                            <span>Obsisstant</span>
                        </a>
                        <a href="{{ route('changelog') }}" class="obs-brand-version-pill" title="View release changelog">v{{ \App\Services\ChangelogService::getCurrentVersion() }}</a>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                        <div style="font-size: clamp(1.125rem, 0.95rem + 0.8vw, 1.5rem); font-weight: 600; line-height: 1.15; letter-spacing: -0.02em; color: var(--gold);">{{ $studio->name }}</div>
                        @if ($studios->count() > 1)
                            <select
                                id="deck-studio-switcher"
                                class="deck-select"
                                aria-label="Switch active studio"
                                onchange="if (this.value) window.location.href = this.value;"
                            >
                                @foreach ($studios as $item)
                                    <option value="{{ route('obs.deck.show', ['studio' => $item->slug], false) }}" @selected($item->id === $studio->id)>
                                        {{ $item->name }} ({{ ucfirst($item->status) }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div style="font-size: 0.75rem; color: var(--muted);">Mobile Wireless Stream Deck</div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.625rem; align-items: center;">
                    <x-reverb-status-pill />
                    <span id="studio-status" class="status-pill {{ $studio->status === 'streaming' ? 'status-pill--streaming' : ($studio->isOnline() ? 'status-pill--online' : '') }}">
                        {{ strtoupper($studio->status) }}
                    </span>
                    <div style="display: inline-flex; gap: 0.25rem;">
                        <button type="button" class="deck-btn{{ ($controlMode ?? 'obs') !== 'screens' ? ' deck-btn--active' : '' }}" data-deck-mode="obs" style="min-block-size: 2rem; padding: 0.25rem 0.65rem; font-size: 0.75rem;">OBS Mode</button>
                        <button type="button" class="deck-btn{{ ($controlMode ?? 'obs') === 'screens' ? ' deck-btn--active' : '' }}" data-deck-mode="screens" style="min-block-size: 2rem; padding: 0.25rem 0.65rem; font-size: 0.75rem;">Screen Mode</button>
                    </div>
                    <a href="{{ route('studio.show', ['studio' => $studio->slug], false) }}" style="color: var(--gold); text-decoration: none; font-size: 0.85rem; font-weight: 500;">Studio</a>
                    <a href="{{ route('knowledge-base', [], false) }}" style="color: var(--muted); text-decoration: none; font-size: 0.85rem; font-weight: 500;">Docs</a>
                    <a href="{{ url('/admin/obs-control-deck') }}" style="color: var(--muted); text-decoration: none; font-size: 0.85rem; font-weight: 500;">Admin</a>
                </div>
            </header>

            <section id="deck-offline-banner" class="deck-offline-banner" @if ($studio->isOnline()) hidden @endif>
                <div style="display: grid; gap: 0.15rem;">
                    <strong style="font-size: 0.9rem; color: var(--gold);">
                        {{ $studio->name }} is currently offline
                    </strong>
                    <span style="font-size: 0.78rem; color: var(--muted);">
                        Button taps cannot reach OBS until <code>obs-agent</code> is running on your streaming PC with OBS WebSocket enabled.
                    </span>
                </div>
                <a
                    href="{{ route('studio.show', ['studio' => $studio->slug], false) }}"
                    class="deck-btn deck-btn--active"
                    style="min-block-size: 2.35rem; padding: 0.4rem 0.9rem; font-size: 0.8rem; text-decoration: none;"
                >
                    Connect / Install Agent
                </a>
            </section>

            <section class="glass">
                <h2 class="section-title">Outputs &amp; Studio Mode</h2>
                <div class="deck-grid">
                    <button type="button" class="deck-btn {{ ! empty($studio->getOutputs()['streaming']) ? 'deck-btn--active' : '' }}" id="deck-btn-stream" data-cmd="ToggleStream">Toggle Stream</button>
                    <button type="button" class="deck-btn {{ ! empty($studio->getOutputs()['recording']) ? 'deck-btn--active' : '' }}" id="deck-btn-record" data-cmd="ToggleRecord">Toggle Record</button>
                    <button type="button" class="deck-btn {{ ! empty($studio->getOutputs()['virtualCam']) ? 'deck-btn--active' : '' }}" id="deck-btn-vcam" data-cmd="ToggleVirtualCam">Virtual Cam</button>
                    <button type="button" class="deck-btn" data-cmd="ToggleRecordPause">Pause Record</button>
                    <button type="button" class="deck-btn" data-cmd="SaveReplayBuffer">Save Replay</button>
                    <button type="button" class="deck-btn" data-cmd="TriggerStudioModeTransition">Transition</button>
                </div>
            </section>

            <section class="glass">
                <h2 class="section-title">1-Click Broadcast Macros</h2>
                <div class="deck-grid">
                    @foreach ($macros as $macro)
                        <button type="button" class="deck-btn deck-btn--accent" data-macro="{{ $macro->id }}">
                            {{ $macro->name }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section id="deck-classic-scenes-section" class="glass" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; margin-block-end: 0.75rem;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                        <h2 class="section-title" style="margin: 0;">Scenes</h2>
                        <span id="deck-action-feedback" style="font-size: 0.72rem; color: var(--gold);"></span>
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <span id="deck-studio-mode-note" style="font-size: 0.72rem; color: var(--muted);">
                            {{ $studio->isStudioModeEnabled() ? 'Studio Mode ON (Cut switches Live)' : 'Direct Cut' }}
                        </span>
                        <button type="button" id="deck-sync-btn" class="deck-btn" style="min-block-size: 1.9rem; padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                            Sync
                        </button>
                    </div>
                </div>
                <div id="scenes-grid" class="deck-grid">
                    @forelse ($studio->getScenes() as $scene)
                        @php $sceneName = $scene['sceneName'] ?? ''; @endphp
                        <button
                            type="button"
                            class="deck-btn {{ $studio->getCurrentProgramScene() === $sceneName ? 'deck-btn--active' : '' }}"
                            data-scene="{{ $sceneName }}"
                        >
                            {{ $sceneName }}
                        </button>
                    @empty
                        <p style="grid-column: 1 / -1; margin: 0; color: var(--muted); font-size: 0.88rem;">
                            Connect obs-agent to populate live scenes.
                        </p>
                    @endforelse
                </div>
            </section>

            <section id="deck-unified-screen-section" class="glass deck-unified-stack" @if (($controlMode ?? 'obs') !== 'screens') hidden @endif>
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <div>
                        <h2 class="section-title" style="margin: 0;">Unified Screen Stage</h2>
                        <div style="font-size: 0.72rem; color: var(--muted);">Tap any Screen View to transition your single OBS Browser Source</div>
                    </div>
                    <div style="display: inline-flex; flex-wrap: wrap; gap: 0.4rem;">
                        <a id="deck-uni-builder-link" href="#" class="deck-btn" style="min-block-size: 2rem; padding: 0.25rem 0.7rem; font-size: 0.75rem; text-decoration: none;">Canvas Builder</a>
                        <button type="button" id="deck-uni-push-btn" class="deck-btn deck-btn--accent" style="min-block-size: 2rem; padding: 0.25rem 0.7rem; font-size: 0.75rem;">Push to OBS</button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0.5rem;">
                    <select id="deck-uni-collection-select" class="deck-select" aria-label="Select Event Screen Collection"></select>
                    <select id="deck-uni-tx-select" class="deck-select" aria-label="Transition effect">
                        <option value="default">Default Transition</option>
                        @foreach (($transitionTypes ?? []) as $txKey => $txLabel)
                            <option value="{{ $txKey }}">{{ $txLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="deck-unified-views-grid" class="deck-grid"></div>

                <div style="display: grid; gap: 0.5rem; padding-block-start: 0.7rem; border-block-start: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="section-title" style="margin: 0;">Live Timer</span>
                        <strong id="deck-uni-timer-display" style="font-family: ui-monospace, monospace; font-size: 1.2rem; color: var(--gold);">05:00</strong>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.4rem;">
                        <button type="button" id="deck-uni-t-start" class="deck-btn deck-btn--accent" style="min-block-size: 2.5rem; font-size: 0.8rem;">Start</button>
                        <button type="button" id="deck-uni-t-pause" class="deck-btn" style="min-block-size: 2.5rem; font-size: 0.8rem;">Pause</button>
                        <button type="button" id="deck-uni-t-reset" class="deck-btn" style="min-block-size: 2.5rem; font-size: 0.8rem;">Reset</button>
                        <button type="button" id="deck-uni-t-plus" class="deck-btn" style="min-block-size: 2.5rem; font-size: 0.8rem;">+1m</button>
                    </div>
                </div>

                <div style="display: grid; gap: 0.5rem; padding-block-start: 0.7rem; border-block-start: 1px solid rgba(255,255,255,0.1);">
                    <span class="section-title" style="margin: 0;">Scoreboard</span>
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem;">
                        <div style="display: grid; gap: 0.3rem; text-align: center;">
                            <span id="deck-uni-sb-a-name" style="font-size: 0.78rem; color: var(--muted);">Team Alpha</span>
                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                <button type="button" id="deck-uni-sb-a-minus" class="deck-btn" style="min-block-size: 2.4rem; flex: 1;">-</button>
                                <strong id="deck-uni-sb-a-score" style="min-inline-size: 2rem;">0</strong>
                                <button type="button" id="deck-uni-sb-a-plus" class="deck-btn deck-btn--accent" style="min-block-size: 2.4rem; flex: 1;">+</button>
                            </div>
                        </div>
                        <div style="display: grid; gap: 0.3rem; text-align: center;">
                            <span id="deck-uni-sb-b-name" style="font-size: 0.78rem; color: var(--muted);">Team Omega</span>
                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                <button type="button" id="deck-uni-sb-b-minus" class="deck-btn" style="min-block-size: 2.4rem; flex: 1;">-</button>
                                <strong id="deck-uni-sb-b-score" style="min-inline-size: 2rem;">0</strong>
                                <button type="button" id="deck-uni-sb-b-plus" class="deck-btn deck-btn--accent" style="min-block-size: 2.4rem; flex: 1;">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; gap: 0.55rem; padding-block-start: 0.7rem; border-block-start: 1px solid rgba(255,255,255,0.1);">
                    <span class="section-title" style="margin: 0;">Lower Third &amp; Alert Banner</span>
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0.45rem;">
                        <input id="deck-uni-l3-title" type="text" class="deck-input" placeholder="Lower third title" aria-label="Lower third title">
                        <input id="deck-uni-l3-sub" type="text" class="deck-input" placeholder="Subtitle / role" aria-label="Lower third subtitle">
                    </div>
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0.45rem;">
                        <button type="button" id="deck-uni-l3-show" class="deck-btn deck-btn--accent" style="min-block-size: 2.4rem; font-size: 0.8rem;">Show Lower Third</button>
                        <button type="button" id="deck-uni-l3-hide" class="deck-btn" style="min-block-size: 2.4rem; font-size: 0.8rem;">Hide Lower Third</button>
                    </div>
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 0.4rem;">
                        <input id="deck-uni-alert-msg" type="text" class="deck-input" placeholder="Alert message..." aria-label="Pop-up alert message">
                        <button type="button" id="deck-uni-alert-show" class="deck-btn deck-btn--accent" style="min-block-size: 2.5rem; padding-inline: 0.85rem; font-size: 0.8rem;">Alert</button>
                        <button type="button" id="deck-uni-alert-hide" class="deck-btn" style="min-block-size: 2.5rem; padding-inline: 0.75rem; font-size: 0.8rem;">Clear</button>
                    </div>
                </div>
            </section>

            @if ($screens->isNotEmpty())
                <section id="deck-legacy-overlay-section" class="glass" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
                    <h2 class="section-title">Quick Live Lower-Third / Alert Push</h2>
                    <div style="display: grid; gap: 0.65rem;">
                        <select id="live-screen-id" style="min-block-size: 2.75rem; border-radius: 0.75rem; padding: 0.5rem; background: #0d0c09; color: var(--text); border: 1px solid rgba(255,255,255,0.16);">
                            @foreach ($screens as $screen)
                                <option value="{{ $screen->id }}">{{ $screen->name }} ({{ $screen->template_type }})</option>
                            @endforeach
                        </select>
                        <input id="live-guest-name" type="text" placeholder="Lower-third name / headline..." style="min-block-size: 2.75rem; border-radius: 0.75rem; padding: 0.5rem 0.75rem; background: #0d0c09; color: var(--text); border: 1px solid rgba(255,255,255,0.16);">
                        <button type="button" id="push-live-text" class="deck-btn deck-btn--accent">Broadcast Live Update</button>
                    </div>
                </section>
            @endif
        </div>

        <div id="toast" class="toast" role="status" hidden></div>

        <script src="/js/filament/filament/echo.js"></script>
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const studioId = @json($studio->id);
                const cmdUrl = {!! json_encode(route('obs.deck.command', ['studio' => $studio->slug], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const macroUrlTpl = {!! json_encode(route('obs.deck.macro', ['studio' => $studio->slug, 'macro' => '__MACRO__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const screenLiveUrlTpl = {!! json_encode(route('obs.deck.screen-live', ['screen' => '__SCREEN__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const snapshotUrl = {!! json_encode(route('studio.snapshot', ['studio' => $studio->slug], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const cmdResultTpl = {!! json_encode(route('studio.command-result', ['studio' => $studio->slug, 'commandUuid' => '__UUID__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
                const toast = document.getElementById('toast');
                const feedbackEl = document.getElementById('deck-action-feedback');
                let toastTimer = null;
                let studioOnline = @json($studio->isOnline());
                let currentProgramScene = @json($studio->getCurrentProgramScene());
                let lastEchoAt = 0;
                let studioChannel = null;
                let latestSceneTapSeq = 0;
                const pendingResolvers = new Map();

                const createCommandUuid = () => {
                    if (window.crypto?.randomUUID) {
                        return window.crypto.randomUUID();
                    }
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                        const r = (Math.random() * 16) | 0;
                        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
                    });
                };

                const isEchoReady = () => Boolean(
                    studioChannel?.subscription?.subscribed
                    && window.Echo?.connector?.pusher?.connection?.state === 'connected'
                );

                const showToast = (msg, isError = false) => {
                    if (feedbackEl) {
                        feedbackEl.textContent = msg;
                        feedbackEl.style.color = isError ? '#fca5a5' : 'var(--gold)';
                    }
                    if (!toast) return;
                    clearTimeout(toastTimer);
                    toast.textContent = msg;
                    toast.classList.toggle('toast--error', Boolean(isError));
                    toast.hidden = false;
                    toastTimer = setTimeout(() => { toast.hidden = true; }, isError ? 3600 : 2200);
                };

                const postJson = async (url, body) => {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(body),
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => null);
                        throw new Error(err?.message || (res.status === 419 ? 'Session expired — reload page' : `HTTP ${res.status}`));
                    }
                    return res.json();
                };

                const waitForCommandResult = (commandUuid, timeoutMs = 4000) => new Promise((resolve) => {
                    if (!commandUuid) {
                        resolve(null);
                        return;
                    }
                    let done = false;
                    const finish = (payload) => {
                        if (done) return;
                        done = true;
                        pendingResolvers.delete(commandUuid);
                        resolve(payload);
                    };
                    pendingResolvers.set(commandUuid, finish);

                    const started = Date.now();
                    const poll = async () => {
                        if (done) return;
                        try {
                            const res = await fetch(cmdResultTpl.replace('__UUID__', commandUuid), {
                                headers: { Accept: 'application/json' },
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data.status === 'succeeded' || data.status === 'failed') {
                                    finish(data);
                                    return;
                                }
                            }
                        } catch {
                            // Keep waiting until timeout
                        }
                        if (Date.now() - started >= timeoutMs) {
                            finish(null);
                            return;
                        }
                        setTimeout(poll, 350);
                    };

                    // Wait briefly for Echo/whisper confirmation before polling HTTP fallback
                    setTimeout(poll, isEchoReady() ? 1150 : 250);
                });

                const applyLiveState = (status, isOnlineFlag, liveState) => {
                    const statusEl = document.getElementById('studio-status');
                    const offlineBanner = document.getElementById('deck-offline-banner');
                    const normalizedStatus = String(status || '').toLowerCase();

                    if (typeof isOnlineFlag === 'boolean') {
                        studioOnline = isOnlineFlag;
                    } else if (normalizedStatus) {
                        studioOnline = normalizedStatus !== 'offline';
                    }

                    if (statusEl && status) {
                        statusEl.textContent = String(status).toUpperCase();
                        statusEl.className = 'status-pill ' + (normalizedStatus === 'streaming'
                            ? 'status-pill--streaming'
                            : (studioOnline ? 'status-pill--online' : ''));
                    }
                    if (offlineBanner) {
                        offlineBanner.hidden = studioOnline;
                    }

                    if (!liveState || typeof liveState !== 'object') return;

                    if (liveState.currentProgramScene) {
                        currentProgramScene = liveState.currentProgramScene;
                    }

                    const modeNote = document.getElementById('deck-studio-mode-note');
                    if (modeNote && liveState.studioModeEnabled !== undefined) {
                        modeNote.textContent = liveState.studioModeEnabled ? 'Studio Mode ON (Cut switches Live)' : 'Direct Cut';
                    }

                    const outputs = liveState.outputs || {};
                    document.getElementById('deck-btn-stream')?.classList.toggle('deck-btn--active', Boolean(outputs.streaming));
                    document.getElementById('deck-btn-record')?.classList.toggle('deck-btn--active', Boolean(outputs.recording));
                    document.getElementById('deck-btn-vcam')?.classList.toggle('deck-btn--active', Boolean(outputs.virtualCam));

                    const grid = document.getElementById('scenes-grid');
                    if (grid && Array.isArray(liveState.scenes)) {
                        grid.innerHTML = '';
                        if (liveState.scenes.length === 0) {
                            const empty = document.createElement('p');
                            empty.style.cssText = 'grid-column: 1 / -1; margin: 0; color: var(--muted); font-size: 0.88rem;';
                            empty.textContent = 'No scenes found in OBS Studio yet.';
                            grid.appendChild(empty);
                        } else {
                            liveState.scenes.forEach((scene) => {
                                const name = scene?.sceneName || '';
                                if (!name) return;
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = `deck-btn${name === currentProgramScene ? ' deck-btn--active' : ''}`;
                                btn.dataset.scene = name;
                                btn.textContent = name;
                                grid.appendChild(btn);
                            });
                        }
                    } else {
                        document.querySelectorAll('[data-scene]').forEach((btn) => {
                            btn.classList.toggle('deck-btn--active', btn.dataset.scene === currentProgramScene);
                        });
                    }
                };

                const refreshSnapshot = async () => {
                    try {
                        const res = await fetch(snapshotUrl, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return null;
                        const data = await res.json();
                        applyLiveState(data.status, data.is_online, data.live_state);
                        return data;
                    } catch {
                        return null;
                    }
                };

                const rollbackSceneSelection = (requestType, previousScene) => {
                    if (requestType !== 'SetCurrentProgramScene') {
                        return;
                    }
                    currentProgramScene = previousScene;
                    document.querySelectorAll('[data-scene]').forEach((b) => {
                        b.classList.toggle('deck-btn--active', b.dataset.scene === previousScene);
                    });
                };

                const sendDeckCommand = async (requestType, requestData = {}, successLabel = null) => {
                    const prevScene = currentProgramScene;
                    const tapSeq = requestType === 'SetCurrentProgramScene' ? ++latestSceneTapSeq : latestSceneTapSeq;
                    const commandUuid = createCommandUuid();
                    const resultPromise = waitForCommandResult(commandUuid, 4500);
                    let usedWhisper = false;

                    if (isEchoReady()) {
                        try {
                            usedWhisper = Boolean(studioChannel.whisper('obs.command', {
                                command_uuid: commandUuid,
                                studio_id: studioId,
                                request_type: requestType,
                                request_data: requestData,
                            }));
                        } catch {
                            usedWhisper = false;
                        }
                    }

                    showToast(successLabel || `Sending ${requestType}...`);

                    const httpPromise = postJson(cmdUrl, {
                        command_uuid: commandUuid,
                        request_type: requestType,
                        request_data: requestData,
                    }).catch((err) => {
                        if (pendingResolvers.has(commandUuid)) {
                            pendingResolvers.get(commandUuid)({ status: 'failed', error_message: err.message });
                        }
                        throw err;
                    });

                    try {
                        const settled = await resultPromise;
                        if (settled?.status === 'failed') {
                            if (requestType !== 'SetCurrentProgramScene' || tapSeq === latestSceneTapSeq) {
                                rollbackSceneSelection(requestType, prevScene);
                            }
                            showToast(`OBS Error: ${settled.error_message || 'Command failed'}`, true);
                        } else if (settled?.status === 'succeeded') {
                            if (!usedWhisper && Date.now() - lastEchoAt > 600) {
                                refreshSnapshot();
                            }
                        } else if (requestType !== 'SetCurrentProgramScene' || tapSeq === latestSceneTapSeq) {
                            rollbackSceneSelection(requestType, prevScene);
                            await refreshSnapshot();
                            showToast(
                                studioOnline
                                    ? 'OBS Agent did not respond — check OBS WebSocket & restart OBS Agent'
                                    : 'OBS Agent offline — install/start agent on your streaming PC',
                                true,
                            );
                        }
                        await httpPromise.catch(() => {});
                    } catch (err) {
                        if (requestType !== 'SetCurrentProgramScene' || tapSeq === latestSceneTapSeq) {
                            rollbackSceneSelection(requestType, prevScene);
                        }
                        showToast(`Failed: ${err.message}`, true);
                    }
                };

                document.querySelectorAll('[data-cmd]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const type = btn.dataset.cmd;
                        if (type) sendDeckCommand(type, {}, `Sent: ${type}`);
                    });
                });

                document.querySelectorAll('[data-macro]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const macroId = btn.dataset.macro;
                        if (!macroId) return;
                        try {
                            await postJson(macroUrlTpl.replace('__MACRO__', macroId), {});
                            showToast(`Macro triggered: ${btn.textContent.trim()}`);
                            setTimeout(refreshSnapshot, 450);
                        } catch (err) {
                            showToast(`Macro failed: ${err.message}`, true);
                        }
                    });
                });

                // Delegate scene button clicks so dynamically updated #scenes-grid works immediately
                document.getElementById('scenes-grid')?.addEventListener('click', (ev) => {
                    const btn = ev.target instanceof Element ? ev.target.closest('[data-scene]') : null;
                    if (!btn) return;
                    const sceneName = btn.getAttribute('data-scene');
                    if (!sceneName) return;

                    currentProgramScene = sceneName;
                    document.querySelectorAll('[data-scene]').forEach((b) => {
                        b.classList.toggle('deck-btn--active', b.getAttribute('data-scene') === sceneName);
                    });

                    sendDeckCommand('SetCurrentProgramScene', { sceneName }, `Scene: ${sceneName}`);
                });

                document.getElementById('deck-sync-btn')?.addEventListener('click', () => {
                    sendDeckCommand('obsisstant.RefreshAllState', {}, 'Syncing OBS scenes...');
                });

                document.getElementById('push-live-text')?.addEventListener('click', async () => {
                    const screenId = document.getElementById('live-screen-id')?.value;
                    const text = document.getElementById('live-guest-name')?.value?.trim();
                    if (!screenId || !text) {
                        showToast('Enter text before broadcasting', true);
                        return;
                    }
                    try {
                        await postJson(screenLiveUrlTpl.replace('__SCREEN__', screenId), {
                            state: { guest_name: text, headline: text, show_lower_third: true },
                        });
                        showToast('Overlay updated via Reverb');
                    } catch (err) {
                        showToast(`Overlay update failed: ${err.message}`, true);
                    }
                });

                const echoCfg = @json($echoConfig);
                if (window.EchoFactory && echoCfg && echoCfg.key) {
                    const runtimeCfg = { ...echoCfg };
                    if (['ob', 'localhost', '127.0.0.1'].includes(runtimeCfg.wsHost) && window.location.hostname) {
                        runtimeCfg.wsHost = window.location.hostname;
                        runtimeCfg.forceTLS = window.location.protocol === 'https:';
                    }
                    const handleStudioState = (payload) => {
                        lastEchoAt = Date.now();
                        applyLiveState(payload?.status, payload?.is_online, payload?.live_state);
                    };
                    const handleCommandAck = (payload) => {
                        const resolver = pendingResolvers.get(payload?.command_uuid);
                        if (resolver) {
                            resolver(payload);
                        }
                        if (payload?.status === 'failed' && payload?.error_message) {
                            showToast(`OBS Error: ${payload.error_message}`, true);
                        }
                    };
                    window.Echo = new window.EchoFactory(runtimeCfg);
                    studioChannel = window.Echo.private(@json("obs.studio.{$studio->id}"))
                        .listen('.obs.studio.state', handleStudioState)
                        .listenForWhisper('obs.studio.state', handleStudioState)
                        .listen('.obs.command.acknowledged', handleCommandAck)
                        .listenForWhisper('obs.command.acknowledged', handleCommandAck);
                }

                // Unified Screen Collection Stage Remote Sync
                let controlMode = @json($controlMode ?? 'obs');
                let collectionsData = @json($collectionsPayload ?? []);
                let activeCollectionId = @json($activeCollectionId);
                let subscribedCollectionToken = null;
                let subscribedCollectionChannel = null;

                const uniUrls = {
                    controlMode: {!! json_encode(route('studio.control-mode', ['studio' => $studio->slug], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
                    builderTpl: {!! json_encode(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => '__COL__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
                    switchTpl: {!! json_encode(route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => '__COL__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
                    runtimeTpl: {!! json_encode(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => '__COL__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
                    pushTpl: {!! json_encode(route('studio.collections.push', ['studio' => $studio->slug, 'collection' => '__COL__'], false), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
                };

                const getActiveCollection = () => collectionsData.find((c) => Number(c.collection_id) === Number(activeCollectionId)) || collectionsData[0] || null;

                const fmtDeckClock = (secs) => {
                    const s = Math.max(0, Math.floor(secs));
                    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
                };

                const subscribeDeckCollectionEcho = (col) => {
                    if (!window.Echo || !col?.public_token || col.public_token === subscribedCollectionToken) return;
                    if (subscribedCollectionChannel && subscribedCollectionToken) {
                        try { window.Echo.leave(`obs.collection.${subscribedCollectionToken}`); } catch {}
                    }
                    subscribedCollectionToken = col.public_token;
                    subscribedCollectionChannel = window.Echo.channel(`obs.collection.${col.public_token}`)
                        .listen('.obs.collection.updated', (payload) => {
                            const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(payload.collection_id));
                            if (idx >= 0) collectionsData[idx] = payload;
                            else collectionsData.push(payload);
                            if (Number(payload.collection_id) === Number(activeCollectionId)) renderUnifiedDeck();
                        });
                };

                const renderUnifiedDeck = () => {
                    document.querySelectorAll('[data-deck-mode]').forEach((b) => {
                        b.classList.toggle('deck-btn--active', b.dataset.deckMode === controlMode);
                    });
                    const classicSec = document.getElementById('deck-classic-scenes-section');
                    const uniSec = document.getElementById('deck-unified-screen-section');
                    const legacyOverlaySec = document.getElementById('deck-legacy-overlay-section');
                    if (classicSec) classicSec.hidden = controlMode === 'screens';
                    if (uniSec) uniSec.hidden = controlMode !== 'screens';
                    if (legacyOverlaySec) legacyOverlaySec.hidden = controlMode === 'screens';

                    const sel = document.getElementById('deck-uni-collection-select');
                    const grid = document.getElementById('deck-unified-views-grid');
                    const col = getActiveCollection();
                    if (col) activeCollectionId = col.collection_id;

                    if (sel) {
                        sel.innerHTML = collectionsData.length === 0 ? '<option value="">No collections</option>' : '';
                        collectionsData.forEach((c) => {
                            const o = document.createElement('option');
                            o.value = String(c.collection_id);
                            o.textContent = c.name;
                            o.selected = Number(c.collection_id) === Number(activeCollectionId);
                            sel.appendChild(o);
                        });
                    }

                    const builderLink = document.getElementById('deck-uni-builder-link');
                    if (builderLink) builderLink.href = col ? uniUrls.builderTpl.replace('__COL__', col.collection_id) : '#';

                    if (!grid) return;
                    grid.innerHTML = '';
                    if (!col) {
                        grid.innerHTML = '<p style="grid-column:1/-1;margin:0;color:var(--muted);font-size:0.85rem;">Create a Screen Collection from Studio to control it here.</p>';
                        return;
                    }

                    subscribeDeckCollectionEcho(col);

                    (col.views || []).forEach((v) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `deck-btn${Number(v.id) === Number(col.active_view_id) ? ' deck-btn--active' : ''}`;
                        btn.textContent = v.name;
                        btn.addEventListener('click', async () => {
                            const txType = document.getElementById('deck-uni-tx-select')?.value || 'default';
                            const res = await postJson(uniUrls.switchTpl.replace('__COL__', col.collection_id), {
                                view_id: v.id,
                                transition: txType === 'default' ? null : { type: txType },
                            });
                            if (res.collection) {
                                const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(res.collection.collection_id));
                                if (idx >= 0) collectionsData[idx] = res.collection;
                                renderUnifiedDeck();
                                showToast(`View: ${v.name}`);
                            }
                        });
                        grid.appendChild(btn);
                    });

                    const sb = col.runtime_state?.scoreboard || {};
                    document.getElementById('deck-uni-sb-a-name').textContent = sb.team_a_name || 'Team Alpha';
                    document.getElementById('deck-uni-sb-b-name').textContent = sb.team_b_name || 'Team Omega';
                    document.getElementById('deck-uni-sb-a-score').textContent = String(sb.team_a_score ?? 0);
                    document.getElementById('deck-uni-sb-b-score').textContent = String(sb.team_b_score ?? 0);

                    const l3 = col.runtime_state?.lower_third || {};
                    const l3TitleEl = document.getElementById('deck-uni-l3-title');
                    const l3SubEl = document.getElementById('deck-uni-l3-sub');
                    if (l3TitleEl && document.activeElement !== l3TitleEl) l3TitleEl.value = l3.title || '';
                    if (l3SubEl && document.activeElement !== l3SubEl) l3SubEl.value = l3.subtitle || '';
                };

                const sendDeckRuntime = async (patch) => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const res = await postJson(uniUrls.runtimeTpl.replace('__COL__', col.collection_id), patch);
                    if (res.collection) {
                        const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(res.collection.collection_id));
                        if (idx >= 0) collectionsData[idx] = res.collection;
                        renderUnifiedDeck();
                    }
                };

                document.querySelectorAll('[data-deck-mode]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const nextMode = btn.dataset.deckMode;
                        const res = await postJson(uniUrls.controlMode, { control_mode: nextMode });
                        controlMode = res.control_mode || nextMode;
                        if (res.collection) {
                            const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(res.collection.collection_id));
                            if (idx >= 0) collectionsData[idx] = res.collection;
                            else collectionsData.push(res.collection);
                            activeCollectionId = res.collection.collection_id;
                        }
                        renderUnifiedDeck();
                    });
                });

                document.getElementById('deck-uni-collection-select')?.addEventListener('change', async (ev) => {
                    if (!ev.target.value) return;
                    activeCollectionId = Number(ev.target.value);
                    await postJson(uniUrls.controlMode, { active_screen_collection_id: activeCollectionId });
                    renderUnifiedDeck();
                });

                document.getElementById('deck-uni-push-btn')?.addEventListener('click', async () => {
                    const col = getActiveCollection();
                    if (!col) return;
                    await postJson(uniUrls.pushTpl.replace('__COL__', col.collection_id), {});
                    showToast('Pushing single Browser Source to OBS');
                });

                document.getElementById('deck-uni-t-start')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'start' } }));
                document.getElementById('deck-uni-t-pause')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'pause' } }));
                document.getElementById('deck-uni-t-reset')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'reset' } }));
                document.getElementById('deck-uni-t-plus')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'add_seconds', seconds: 60 } }));
                document.getElementById('deck-uni-sb-a-minus')?.addEventListener('click', () => sendDeckRuntime({ scoreboard: { delta_a: -1 } }));
                document.getElementById('deck-uni-sb-a-plus')?.addEventListener('click', () => sendDeckRuntime({ scoreboard: { delta_a: 1 } }));
                document.getElementById('deck-uni-sb-b-minus')?.addEventListener('click', () => sendDeckRuntime({ scoreboard: { delta_b: -1 } }));
                document.getElementById('deck-uni-sb-b-plus')?.addEventListener('click', () => sendDeckRuntime({ scoreboard: { delta_b: 1 } }));
                document.getElementById('deck-uni-l3-show')?.addEventListener('click', () => sendDeckRuntime({
                    lower_third: {
                        visible: true,
                        title: document.getElementById('deck-uni-l3-title')?.value || '',
                        subtitle: document.getElementById('deck-uni-l3-sub')?.value || '',
                    },
                }));
                document.getElementById('deck-uni-l3-hide')?.addEventListener('click', () => sendDeckRuntime({ lower_third: { visible: false } }));
                document.getElementById('deck-uni-alert-show')?.addEventListener('click', () => sendDeckRuntime({
                    alert: {
                        visible: true,
                        title: 'Live Update',
                        message: document.getElementById('deck-uni-alert-msg')?.value?.trim() || '',
                    },
                }));
                document.getElementById('deck-uni-alert-hide')?.addEventListener('click', () => sendDeckRuntime({ alert: { visible: false } }));

                setInterval(() => {
                    const col = getActiveCollection();
                    const timerEl = document.getElementById('deck-uni-timer-display');
                    if (!col || !timerEl) return;
                    const t = col.runtime_state?.timer || {};
                    let secs = Number(t.remaining_seconds ?? t.duration_seconds ?? 0);
                    if (t.status === 'running' && t.target_at) {
                        secs = Math.max(0, Math.ceil((Date.parse(t.target_at) - Date.now()) / 1000));
                    }
                    timerEl.textContent = fmtDeckClock(secs);
                }, 250);

                renderUnifiedDeck();

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        refreshSnapshot();
                    }
                });

                setInterval(() => {
                    if (!document.hidden && Date.now() - lastEchoAt > 14000) {
                        refreshSnapshot();
                    }
                }, 15000);
            })();
        </script>
    </body>
</html>
