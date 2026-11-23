<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $screen->name }} · OBS Overlay</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|jetbrains-mono:500,600,700" rel="stylesheet">
        <style>
            :root {
                --accent: {{ $theme['accent_color'] ?? '#E5B94C' }};
                --surface: {{ $theme['surface_color'] ?? '#0B0A08' }};
                --text: {{ $theme['text_color'] ?? '#F7F3E9' }};
                --blur: {{ (int) ($theme['backdrop_blur'] ?? 18) }}px;
                --radius: {{ (int) ($theme['border_radius'] ?? 24) }}px;
                --opacity: {{ (float) ($theme['glass_opacity'] ?? 0.78) }};
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                inline-size: 100%;
                block-size: 100dvb;
                overflow: hidden;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                color: var(--text);
                background: {{ ! empty($theme['transparent_background']) ? 'transparent' : '#060608' }};
                -webkit-font-smoothing: antialiased;
            }

            .stage {
                position: relative;
                inline-size: 100%;
                block-size: 100dvb;
                padding: clamp(1.25rem, 3vw, 2.75rem);
                display: grid;
                grid-template-rows: auto 1fr auto;
                gap: 1.25rem;
            }

            .stage--opaque::before {
                content: '';
                position: absolute;
                inset: 0;
                z-index: 0;
                background:
                    radial-gradient(circle at 18% 18%, color-mix(in oklab, var(--accent) 26%, transparent), transparent 44%),
                    radial-gradient(circle at 82% 24%, color-mix(in oklab, var(--accent) 16%, transparent), transparent 46%),
                    linear-gradient(160deg, #090806 0%, #050507 100%);
            }

            .stage > * {
                position: relative;
                z-index: 1;
            }

            .glass-panel {
                background: color-mix(in oklab, var(--surface) calc(var(--opacity) * 100%), transparent);
                -webkit-backdrop-filter: blur(var(--blur)) saturate(145%);
                backdrop-filter: blur(var(--blur)) saturate(145%);
                border: 1px solid color-mix(in oklab, var(--accent) 34%, transparent);
                border-radius: var(--radius);
                box-shadow: 0 24px 60px -20px rgba(0, 0, 0, 0.75), inset 0 1px 0 rgba(255, 232, 166, 0.18);
            }

            .top-row {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .live-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.55rem;
                padding: 0.5rem 1rem;
                border-radius: 9999px;
                font-size: 0.9rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #140f06;
                background: linear-gradient(135deg, #f6dc8d, var(--accent));
                box-shadow: 0 10px 30px -10px color-mix(in oklab, var(--accent) 70%, transparent);
            }

            .countdown-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.65rem 1.25rem;
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-size: clamp(1.25rem, 2.2vw, 2rem);
                font-weight: 700;
                color: var(--accent);
            }

            .center-stage {
                display: grid;
                place-items: center;
                min-block-size: 0;
            }

            .hero-scene-card {
                max-inline-size: min(92%, 58rem);
                inline-size: 100%;
                padding: clamp(2rem, 4vw, 3.5rem);
                text-align: center;
                display: grid;
                gap: 1rem;
            }

            .hero-scene-card h1 {
                margin: 0;
                font-size: clamp(2.25rem, 4.5vw, 4.5rem);
                line-height: 1.08;
                letter-spacing: -0.025em;
            }

            .hero-scene-card p {
                margin: 0;
                font-size: clamp(1.05rem, 1.8vw, 1.5rem);
                color: rgba(247, 243, 233, 0.8);
            }

            .webcam-frame {
                inline-size: 100%;
                block-size: 100%;
                border-radius: var(--radius);
                border: 2px solid color-mix(in oklab, var(--accent) 55%, transparent);
                box-shadow: inset 0 0 0 1px rgba(255, 236, 179, 0.2), 0 0 40px -15px color-mix(in oklab, var(--accent) 40%, transparent);
                pointer-events: none;
            }

            .pip-grid {
                display: grid;
                grid-template-columns: 1fr min(28vw, 24rem);
                gap: 1.5rem;
                align-items: end;
                inline-size: 100%;
                block-size: 100%;
            }

            .pip-cam-slot {
                aspect-ratio: 16 / 9;
                border-radius: var(--radius);
                border: 2px solid color-mix(in oklab, var(--accent) 60%, transparent);
                background: rgba(10, 9, 8, 0.25);
                box-shadow: 0 22px 45px -16px rgba(0, 0, 0, 0.8);
            }

            .bottom-stack {
                display: grid;
                gap: 0.9rem;
                align-content: end;
            }

            .lower-third {
                max-inline-size: min(100%, 38rem);
                padding: 1rem 1.45rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                border-inline-start: 4px solid var(--accent);
                transition: opacity 280ms ease, transform 280ms ease;
            }

            .lower-third-name {
                font-size: clamp(1.2rem, 2vw, 1.75rem);
                font-weight: 700;
                line-height: 1.15;
            }

            .lower-third-role {
                font-size: clamp(0.88rem, 1.2vw, 1.05rem);
                color: var(--accent);
                font-weight: 600;
            }

            .ticker-bar {
                padding: 0.75rem 1.25rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                overflow: hidden;
            }

            .ticker-label {
                flex-shrink: 0;
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                padding: 0.25rem 0.65rem;
                border-radius: 9999px;
                background: color-mix(in oklab, var(--accent) 22%, transparent);
                border: 1px solid color-mix(in oklab, var(--accent) 45%, transparent);
                color: var(--accent);
            }

            .ticker-items {
                display: flex;
                align-items: center;
                gap: 2.25rem;
                white-space: nowrap;
                font-size: 1rem;
                font-weight: 500;
                animation: ticker-scroll 28s linear infinite;
            }

            @keyframes ticker-scroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }

            .alert-toast {
                position: fixed;
                inset-block-start: clamp(1.25rem, 3vw, 2.5rem);
                inset-inline-start: 50%;
                transform: translateX(-50%);
                z-index: 40;
                min-inline-size: min(90vw, 28rem);
                max-inline-size: 42rem;
                padding: 1rem 1.4rem;
                border-radius: var(--radius);
                border: 1px solid var(--accent);
                background: rgba(16, 14, 11, 0.9);
                -webkit-backdrop-filter: blur(20px);
                backdrop-filter: blur(20px);
                box-shadow: 0 24px 60px -15px rgba(0, 0, 0, 0.88);
                display: flex;
                align-items: center;
                gap: 0.9rem;
                transition: opacity 240ms ease, transform 240ms ease;
            }

            .alert-toast[hidden],
            [hidden] {
                display: none !important;
            }

            @media (prefers-reduced-motion: reduce) {
                .ticker-items {
                    animation: none;
                }

                *,
                *::before,
                *::after {
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
        @if (! empty($state['custom_css']))
            <style>{!! strip_tags((string) $state['custom_css']) !!}</style>
        @endif
    </head>
    <body>
        @php
            $isOpaqueTemplate = in_array($screen->template_type, ['starting_soon', 'brb', 'ending'], true)
                || empty($theme['transparent_background']);
        @endphp

        <div id="overlay-stage" class="stage {{ $isOpaqueTemplate ? 'stage--opaque' : '' }}">
            <div class="top-row">
                <span id="badge-pill" class="live-pill">{{ $state['badge_text'] ?? 'LIVE' }}</span>

                <div id="countdown-box" class="glass-panel countdown-pill" @if (empty($state['show_countdown'])) hidden @endif>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                    <span id="countdown-display">05:00</span>
                </div>
            </div>

            <div class="center-stage">
                @if (in_array($screen->template_type, ['starting_soon', 'brb', 'ending'], true))
                    <div class="glass-panel hero-scene-card">
                        <h1 id="hero-headline">{{ $state['headline'] ?? $screen->name }}</h1>
                        <p id="hero-subheadline">{{ $state['subheadline'] ?? '' }}</p>
                    </div>
                @elseif ($screen->template_type === 'pip')
                    <div class="pip-grid">
                        <div class="webcam-frame" id="webcam-frame" @if (empty($state['show_webcam_frame'])) hidden @endif></div>
                        <div class="pip-cam-slot"></div>
                    </div>
                @else
                    <div class="webcam-frame" id="webcam-frame" @if (empty($state['show_webcam_frame'])) hidden @endif></div>
                @endif
            </div>

            <div class="bottom-stack">
                <div id="lower-third-box" class="glass-panel lower-third" @if (empty($state['show_lower_third'])) hidden @endif>
                    <div>
                        <div id="lower-third-name" class="lower-third-name">{{ $state['guest_name'] ?? '' }}</div>
                        <div id="lower-third-role" class="lower-third-role">{{ $state['guest_role'] ?? '' }}</div>
                    </div>
                </div>

                <div id="ticker-box" class="glass-panel ticker-bar" @if (empty($state['show_ticker']) || empty($state['ticker_items'])) hidden @endif>
                    <span class="ticker-label">Live Feed</span>
                    <div id="ticker-items" class="ticker-items">
                        @foreach (($state['ticker_items'] ?? []) as $item)
                            <span>{{ $item }}</span>
                        @endforeach
                        @foreach (($state['ticker_items'] ?? []) as $item)
                            <span aria-hidden="true">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div id="alert-toast" class="alert-toast" role="status" @if (empty($state['alert_visible']) || empty($state['alert_message'])) hidden @endif>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" aria-hidden="true">
                <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            </svg>
            <div>
                <div id="alert-title" style="font-weight: 700; color: var(--accent);">{{ $state['alert_title'] ?? 'Notice' }}</div>
                <div id="alert-message" style="font-size: 0.95rem;">{{ $state['alert_message'] ?? '' }}</div>
            </div>
        </div>

        <script src="{{ asset('js/filament/filament/echo.js') }}"></script>
        <script>
            (() => {
                let currentState = @json($state);
                let currentTheme = @json($theme);
                let countdownRemaining = Number(currentState.countdown_seconds || 0);
                let countdownTarget = currentState.countdown_target_at ? Date.parse(currentState.countdown_target_at) : null;

                const formatTime = (secs) => {
                    const safe = Math.max(0, Math.floor(secs));
                    const mins = String(Math.floor(safe / 60)).padStart(2, '0');
                    const rem = String(safe % 60).padStart(2, '0');
                    return `${mins}:${rem}`;
                };

                const tickCountdown = () => {
                    const el = document.getElementById('countdown-display');
                    if (!el) return;
                    if (countdownTarget) {
                        const diff = Math.max(0, Math.floor((countdownTarget - Date.now()) / 1000));
                        el.textContent = formatTime(diff);
                    } else {
                        el.textContent = formatTime(countdownRemaining);
                        if (countdownRemaining > 0) {
                            countdownRemaining -= 1;
                        }
                    }
                };

                tickCountdown();
                setInterval(tickCountdown, 1000);

                const applyUpdate = (payload) => {
                    const nextState = payload.state || {};
                    const nextTheme = payload.theme || {};
                    currentState = { ...currentState, ...nextState };
                    currentTheme = { ...currentTheme, ...nextTheme };

                    const root = document.documentElement.style;
                    if (currentTheme.accent_color) root.setProperty('--accent', currentTheme.accent_color);
                    if (currentTheme.surface_color) root.setProperty('--surface', currentTheme.surface_color);
                    if (currentTheme.text_color) root.setProperty('--text', currentTheme.text_color);
                    if (currentTheme.backdrop_blur !== undefined) root.setProperty('--blur', `${currentTheme.backdrop_blur}px`);
                    if (currentTheme.border_radius !== undefined) root.setProperty('--radius', `${currentTheme.border_radius}px`);
                    if (currentTheme.glass_opacity !== undefined) root.setProperty('--opacity', String(currentTheme.glass_opacity));

                    const badge = document.getElementById('badge-pill');
                    if (badge && currentState.badge_text !== undefined) badge.textContent = currentState.badge_text;

                    const headline = document.getElementById('hero-headline');
                    if (headline && currentState.headline !== undefined) headline.textContent = currentState.headline;

                    const subheadline = document.getElementById('hero-subheadline');
                    if (subheadline && currentState.subheadline !== undefined) subheadline.textContent = currentState.subheadline;

                    const ltBox = document.getElementById('lower-third-box');
                    const ltName = document.getElementById('lower-third-name');
                    const ltRole = document.getElementById('lower-third-role');
                    if (ltName) ltName.textContent = currentState.guest_name || '';
                    if (ltRole) ltRole.textContent = currentState.guest_role || '';
                    if (ltBox) ltBox.hidden = !currentState.show_lower_third;

                    const wcFrame = document.getElementById('webcam-frame');
                    if (wcFrame) wcFrame.hidden = !currentState.show_webcam_frame;

                    const cdBox = document.getElementById('countdown-box');
                    if (cdBox) cdBox.hidden = !currentState.show_countdown;
                    if (nextState.countdown_seconds !== undefined) {
                        countdownRemaining = Number(nextState.countdown_seconds || 0);
                    }
                    if (nextState.countdown_target_at !== undefined) {
                        countdownTarget = nextState.countdown_target_at ? Date.parse(nextState.countdown_target_at) : null;
                    }
                    tickCountdown();

                    const tickerBox = document.getElementById('ticker-box');
                    const tickerItems = document.getElementById('ticker-items');
                    const items = Array.isArray(currentState.ticker_items) ? currentState.ticker_items : [];
                    if (tickerItems) {
                        tickerItems.replaceChildren();
                        [...items, ...items].forEach((text, idx) => {
                            const span = document.createElement('span');
                            span.textContent = String(text);
                            if (idx >= items.length) span.setAttribute('aria-hidden', 'true');
                            tickerItems.appendChild(span);
                        });
                    }
                    if (tickerBox) tickerBox.hidden = !currentState.show_ticker || items.length === 0;

                    const alertToast = document.getElementById('alert-toast');
                    const alertTitle = document.getElementById('alert-title');
                    const alertMessage = document.getElementById('alert-message');
                    if (alertTitle) alertTitle.textContent = currentState.alert_title || 'Notice';
                    if (alertMessage) alertMessage.textContent = currentState.alert_message || '';
                    if (alertToast) alertToast.hidden = !currentState.alert_visible || !currentState.alert_message;
                };

                const echoCfg = @json($echoConfig);
                if (window.EchoFactory && echoCfg && echoCfg.key) {
                    window.Echo = new window.EchoFactory(echoCfg);
                    window.Echo.channel(@json("obs.screen.{$screen->public_token}"))
                        .listen('.obs.screen.updated', applyUpdate);
                }
            })();
        </script>
    </body>
</html>
