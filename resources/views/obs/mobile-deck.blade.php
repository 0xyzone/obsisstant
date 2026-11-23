<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $studio->name }} · Mobile Control Deck</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=syne:700,800|plus-jakarta-sans:400,500,600,700|jetbrains-mono:500,600&display=swap" rel="stylesheet">
        <style>
            :root {
                color-scheme: dark;
                --bg: #050506;
                --glass: rgba(17, 15, 11, 0.76);
                --border-gold: rgba(255, 215, 0, 0.28);
                --gold: #ffd700;
                --gold-strong: #f5c451;
                --text: #faf6ec;
                --muted: #9e9582;
            }

            *, *::before, *::after { box-sizing: border-box; }
            html, body {
                margin: 0;
                min-block-size: 100dvb;
                background:
                    radial-gradient(circle at 14% 10%, rgba(255, 215, 0, 0.2), transparent 44%),
                    radial-gradient(circle at 86% 84%, rgba(212, 175, 55, 0.14), transparent 50%),
                    #050506;
                color: var(--text);
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
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
                <div>
                    <div style="font-family: 'Syne', ui-sans-serif, system-ui, sans-serif; font-size: clamp(1.35rem, 1.05rem + 1.2vw, 2rem); font-weight: 800; line-height: 1.05; letter-spacing: -0.03em; color: var(--gold);">{{ $studio->name }}</div>
                    <div style="font-size: 0.76rem; color: var(--muted);">Mobile Wireless Stream Deck</div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <span id="studio-status" class="status-pill {{ $studio->isOnline() ? 'status-pill--online' : '' }}">
                        {{ strtoupper($studio->status) }}
                    </span>
                    <a href="{{ url('/admin/obs-control-deck') }}" style="color: var(--gold); text-decoration: none; font-size: 0.85rem; font-weight: 600;">Admin Deck</a>
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
