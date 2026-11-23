<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $studio->name }} · Mobile Control Deck</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
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
            .deck-btn--active {
                background: linear-gradient(135deg, rgba(243, 211, 122, 0.28), rgba(161, 98, 7, 0.24));
                border-color: var(--gold);
                color: var(--gold);
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
            }

            .toast[hidden] { display: none; }
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
                            <span>{{ config('app.name', 'Obsisstant') }}</span>
                        </a>
                        <a href="{{ route('changelog') }}" class="obs-brand-version-pill" title="View release changelog">v{{ \App\Services\ChangelogService::getCurrentVersion() }}</a>
                    </div>
                    <div style="font-size: clamp(1.125rem, 0.95rem + 0.8vw, 1.5rem); font-weight: 600; line-height: 1.15; letter-spacing: -0.02em; color: var(--gold);">{{ $studio->name }}</div>
                    <div style="font-size: 0.75rem; color: var(--muted);">Mobile Wireless Stream Deck</div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <x-reverb-status-pill />
                    <span id="studio-status" class="status-pill {{ $studio->isOnline() ? 'status-pill--online' : '' }}">
                        {{ strtoupper($studio->status) }}
                    </span>
                    <a href="{{ url('/admin/obs-control-deck') }}" style="color: var(--gold); text-decoration: none; font-size: 0.85rem; font-weight: 500;">Admin Deck</a>
                </div>
            </header>

            <section class="glass">
                <h2 class="section-title">Outputs &amp; Studio Mode</h2>
                <div class="deck-grid">
                    <button type="button" class="deck-btn" data-cmd="ToggleStream">Toggle Stream</button>
                    <button type="button" class="deck-btn" data-cmd="ToggleRecord">Toggle Record</button>
                    <button type="button" class="deck-btn" data-cmd="ToggleVirtualCam">Virtual Cam</button>
                    <button type="button" class="deck-btn" data-cmd="ToggleRecordPause">Pause Record</button>
                    <button type="button" class="deck-btn" data-cmd="SaveReplayBuffer">Save Replay</button>
                    <button type="button" class="deck-btn" data-cmd="TriggerStudioModeTransition">Transition</button>
                </div>
            </section>

            <section class="glass">
                <h2 class="section-title">1-Click Broadcast Macros</h2>
                <div class="deck-grid">
                    @foreach ($macros as $macro)
                        <button type="button" class="deck-btn deck-btn--active" data-macro="{{ $macro->id }}">
                            {{ $macro->name }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="glass">
                <h2 class="section-title">Scenes</h2>
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

            @if ($screens->isNotEmpty())
                <section class="glass">
                    <h2 class="section-title">Quick Live Lower-Third / Alert Push</h2>
                    <div style="display: grid; gap: 0.65rem;">
                        <select id="live-screen-id" style="min-block-size: 2.75rem; border-radius: 0.75rem; padding: 0.5rem; background: #0d0c09; color: var(--text); border: 1px solid rgba(255,255,255,0.16);">
                            @foreach ($screens as $screen)
                                <option value="{{ $screen->id }}">{{ $screen->name }} ({{ $screen->template_type }})</option>
                            @endforeach
                        </select>
                        <input id="live-guest-name" type="text" placeholder="Lower-third name / headline..." style="min-block-size: 2.75rem; border-radius: 0.75rem; padding: 0.5rem 0.75rem; background: #0d0c09; color: var(--text); border: 1px solid rgba(255,255,255,0.16);">
                        <button type="button" id="push-live-text" class="deck-btn deck-btn--active">Broadcast Live Update</button>
                    </div>
                </section>
            @endif
        </div>

        <div id="toast" class="toast" role="status" hidden></div>

        <script src="{{ asset('js/filament/filament/echo.js') }}"></script>
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const cmdUrl = @json(route('obs.deck.command', ['studio' => $studio->slug]));
                const toast = document.getElementById('toast');

                const showToast = (msg) => {
                    if (!toast) return;
                    toast.textContent = msg;
                    toast.hidden = false;
                    setTimeout(() => { toast.hidden = true; }, 2200);
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
                    if (!res.ok) throw new Error('Request failed');
                    return res.json();
                };

                document.querySelectorAll('[data-cmd]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const type = btn.dataset.cmd;
                        await postJson(cmdUrl, { request_type: type });
                        showToast(`Sent: ${type}`);
                    });
                });

                document.querySelectorAll('[data-macro]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const macroId = btn.dataset.macro;
                        await postJson(`/deck/${@json($studio->slug)}/macro/${macroId}`, {});
                        showToast(`Macro triggered: ${btn.textContent.trim()}`);
                    });
                });

                const bindSceneButtons = () => {
                    document.querySelectorAll('[data-scene]').forEach((btn) => {
                        btn.addEventListener('click', async () => {
                            await postJson(cmdUrl, {
                                request_type: 'SetCurrentProgramScene',
                                request_data: { sceneName: btn.dataset.scene },
                            });
                            showToast(`Scene: ${btn.dataset.scene}`);
                        });
                    });
                };
                bindSceneButtons();

                document.getElementById('push-live-text')?.addEventListener('click', async () => {
                    const screenId = document.getElementById('live-screen-id')?.value;
                    const text = document.getElementById('live-guest-name')?.value?.trim();
                    if (!screenId || !text) return;
                    await postJson(`/deck/screens/${screenId}/live`, {
                        state: { guest_name: text, headline: text, show_lower_third: true },
                    });
                    showToast('Overlay updated via Reverb');
                });

                const echoCfg = @json($echoConfig);
                if (window.EchoFactory && echoCfg && echoCfg.key) {
                    window.Echo = new window.EchoFactory(echoCfg);
                    window.Echo.private(@json("obs.studio.{$studio->id}"))
                        .listen('.obs.studio.state', (payload) => {
                            const statusEl = document.getElementById('studio-status');
                            if (statusEl && payload.status) {
                                statusEl.textContent = String(payload.status).toUpperCase();
                                statusEl.className = 'status-pill ' + (payload.is_online ? 'status-pill--online' : '');
                            }
                            const current = payload.live_state?.currentProgramScene;
                            document.querySelectorAll('[data-scene]').forEach((btn) => {
                                btn.classList.toggle('deck-btn--active', btn.dataset.scene === current);
                            });
                        });
                }
            })();
        </script>
    </body>
</html>
