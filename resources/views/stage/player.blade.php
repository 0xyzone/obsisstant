<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $collection->name }} · Unified Broadcast Stage</title>
        <x-app-favicon />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800|jetbrains-mono:500,600,700|poppins:400,500,600,700,800&display=swap" rel="stylesheet">
        <style>
            :root {
                --stage-w: {{ (int) ($stagePayload['width'] ?? 1920) }};
                --stage-h: {{ (int) ($stagePayload['height'] ?? 1080) }};
                --accent: {{ $stagePayload['theme']['accent_color'] ?? '#CDAD69' }};
                --secondary: {{ $stagePayload['theme']['secondary_color'] ?? '#F4E185' }};
                --surface: {{ $stagePayload['theme']['surface_color'] ?? '#0B0A08' }};
                --text: {{ $stagePayload['theme']['text_color'] ?? '#FAF7EE' }};
                --muted: {{ $stagePayload['theme']['muted_color'] ?? '#ABA291' }};
                --blur: {{ (int) ($stagePayload['theme']['backdrop_blur'] ?? 18) }}px;
                --radius: {{ (int) ($stagePayload['theme']['border_radius'] ?? 20) }}px;
                --opacity: {{ (float) ($stagePayload['theme']['glass_opacity'] ?? 0.82) }};
            }

            *, *::before, *::after {
                box-sizing: border-box;
            }

            html, body {
                margin: 0;
                padding: 0;
                inline-size: 100%;
                block-size: 100dvb;
                overflow: hidden;
                background: transparent;
                color: var(--text);
                font-family: 'Instrument Sans', 'Poppins', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .stage-viewport {
                position: relative;
                inline-size: 100%;
                block-size: 100dvb;
                display: grid;
                place-items: center;
                overflow: hidden;
                background: transparent;
            }

            .stage-frame {
                position: relative;
                inline-size: calc(var(--stage-w) * 1px);
                block-size: calc(var(--stage-h) * 1px);
                transform-origin: center center;
                overflow: hidden;
                background: transparent;
            }

            .stage-slot {
                position: absolute;
                inset: 0;
                overflow: hidden;
                will-change: transform, opacity, clip-path;
            }

            .stage-slot[hidden] {
                display: none !important;
            }

            .view-backdrop-wrap {
                position: absolute;
                inset: 0;
                z-index: 0;
                overflow: hidden;
            }

            .view-backdrop-theme {
                position: absolute;
                inset: 0;
                background:
                    radial-gradient(circle at 16% 18%, color-mix(in oklab, var(--v-accent, var(--accent)) 28%, transparent), transparent 44%),
                    radial-gradient(circle at 84% 22%, color-mix(in oklab, var(--v-secondary, var(--secondary)) 18%, transparent), transparent 46%),
                    radial-gradient(circle at 48% 88%, color-mix(in oklab, var(--v-accent, var(--accent)) 14%, transparent), transparent 48%),
                    linear-gradient(155deg, color-mix(in oklab, var(--v-surface, var(--surface)) 96%, #000) 0%, #050507 100%);
            }

            .view-backdrop-media {
                position: absolute;
                inset: 0;
                inline-size: 100%;
                block-size: 100%;
                object-fit: cover;
                display: block;
            }

            .svg-defs-host {
                position: absolute;
                inline-size: 0;
                block-size: 0;
                pointer-events: none;
                overflow: hidden;
            }

            .layer-node {
                position: absolute;
                transform-origin: center center;
                transition: opacity 220ms ease;
            }

            .layer-node[hidden] {
                display: none !important;
            }

            .cutout-frame {
                inline-size: 100%;
                block-size: 100%;
                background: transparent;
                pointer-events: none;
            }

            .glass-box {
                inline-size: 100%;
                block-size: 100%;
                background: color-mix(in oklab, var(--v-surface, var(--surface)) calc(var(--v-opacity, var(--opacity)) * 100%), transparent);
                -webkit-backdrop-filter: blur(var(--v-blur, var(--blur))) saturate(140%);
                backdrop-filter: blur(var(--v-blur, var(--blur))) saturate(140%);
                border: 1px solid color-mix(in oklab, var(--v-accent, var(--accent)) 36%, transparent);
                box-shadow: 0 24px 60px -20px rgba(0, 0, 0, 0.72), inset 0 1px 0 rgba(255, 236, 179, 0.14);
            }

            .timer-box {
                inline-size: 100%;
                block-size: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                padding: 0.75rem 1.25rem;
                text-align: center;
            }

            .timer-label {
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: var(--v-muted, var(--muted));
            }

            .timer-digits {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                line-height: 1;
                color: var(--v-accent, var(--accent));
            }

            .scoreboard-box {
                inline-size: 100%;
                block-size: 100%;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
                align-items: center;
                gap: 1rem;
                padding: 0.5rem 1.25rem;
            }

            .sb-team {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-inline-size: 0;
            }

            .sb-team--right {
                flex-direction: row-reverse;
                text-align: end;
            }

            .sb-name {
                font-size: 1.5rem;
                font-weight: 700;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .sb-score {
                min-inline-size: 3rem;
                padding: 0.2rem 0.75rem;
                border-radius: 0.6rem;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid color-mix(in oklab, var(--v-accent, var(--accent)) 45%, transparent);
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-size: 1.85rem;
                font-weight: 800;
                text-align: center;
                font-variant-numeric: tabular-nums;
            }

            .sb-meta {
                text-align: center;
                padding-inline: 0.75rem;
            }

            .sb-match {
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--v-accent, var(--accent));
            }

            .sb-round {
                font-size: 0.85rem;
                color: var(--v-muted, var(--muted));
                font-weight: 600;
            }

            .lower-third-box {
                inline-size: 100%;
                block-size: 100%;
                padding: 0.9rem 1.4rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                border-inline-start: 5px solid var(--v-accent, var(--accent));
            }

            .l3-badge {
                padding: 0.25rem 0.65rem;
                border-radius: 9999px;
                background: color-mix(in oklab, var(--v-accent, var(--accent)) 22%, transparent);
                color: var(--v-accent, var(--accent));
                font-size: 0.78rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .l3-title {
                font-size: 1.65rem;
                font-weight: 700;
                line-height: 1.15;
            }

            .l3-sub {
                font-size: 1rem;
                font-weight: 600;
                color: var(--v-accent, var(--accent));
            }

            .ticker-box {
                inline-size: 100%;
                block-size: 100%;
                padding: 0 1.2rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                overflow: hidden;
            }

            .ticker-pill {
                flex-shrink: 0;
                padding: 0.25rem 0.7rem;
                border-radius: 9999px;
                background: color-mix(in oklab, var(--v-accent, var(--accent)) 24%, transparent);
                border: 1px solid color-mix(in oklab, var(--v-accent, var(--accent)) 45%, transparent);
                color: var(--v-accent, var(--accent));
                font-size: 0.82rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .ticker-track {
                display: flex;
                align-items: center;
                gap: 2.5rem;
                white-space: nowrap;
                font-size: 1.05rem;
                font-weight: 500;
            }

            @media (prefers-reduced-motion: no-preference) {
                .ticker-track {
                    animation: stage-ticker-scroll var(--ticker-sec, 28s) linear infinite;
                }
                .loop-pulse { animation: stage-pulse 2.2s ease-in-out infinite; }
                .loop-float { animation: stage-float 3.4s ease-in-out infinite; }
                .loop-glow { animation: stage-glow 2.4s ease-in-out infinite; }
            }

            @keyframes stage-ticker-scroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
            @keyframes stage-pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.03); }
            }
            @keyframes stage-float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-8px); }
            }
            @keyframes stage-glow {
                0%, 100% { filter: drop-shadow(0 0 6px color-mix(in oklab, var(--accent) 45%, transparent)); }
                50% { filter: drop-shadow(0 0 18px color-mix(in oklab, var(--accent) 85%, transparent)); }
            }

            /* Alert Toast */
            .stage-alert-toast {
                position: absolute;
                inset-block-start: 44px;
                inset-inline-start: 50%;
                transform: translateX(-50%);
                z-index: 90;
                min-inline-size: 520px;
                max-inline-size: 920px;
                padding: 1.15rem 1.65rem;
                border-radius: 1.15rem;
                border: 2px solid var(--accent);
                background: rgba(11, 10, 8, 0.94);
                -webkit-backdrop-filter: blur(22px);
                backdrop-filter: blur(22px);
                box-shadow: 0 28px 65px -16px rgba(0, 0, 0, 0.9);
                display: flex;
                align-items: center;
                gap: 1rem;
                opacity: 1;
                transition: opacity 250ms ease, transform 250ms ease;
            }

            .stage-alert-toast[hidden] {
                display: none !important;
            }

            /* Color Sweep & Stinger Overlay */
            .transition-sweep {
                position: absolute;
                inset: -10% -25%;
                z-index: 95;
                pointer-events: none;
                transform: translateX(-125%) skewX(-14deg);
            }

            .transition-sweep[hidden] {
                display: none !important;
            }

            .stinger-layer {
                position: absolute;
                inset: 0;
                z-index: 98;
                pointer-events: none;
                background: transparent;
            }

            .stinger-layer[hidden] {
                display: none !important;
            }

            .stinger-layer video {
                inline-size: 100%;
                block-size: 100%;
                object-fit: cover;
                display: block;
            }
        </style>
        @if (! empty($stagePayload['theme']['custom_css']))
            <style>{!! strip_tags((string) $stagePayload['theme']['custom_css']) !!}</style>
        @endif
    </head>
    <body>
        <div class="stage-viewport">
            <div id="stage-frame" class="stage-frame">
                <div id="slot-a" class="stage-slot"></div>
                <div id="slot-b" class="stage-slot" hidden></div>

                <div id="stage-alert-toast" class="stage-alert-toast" role="status" hidden>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" aria-hidden="true">
                        <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                    <div>
                        <div id="stage-alert-title" style="font-size: 1.25rem; font-weight: 800; color: var(--accent);"></div>
                        <div id="stage-alert-message" style="font-size: 1.1rem;"></div>
                    </div>
                </div>

                <div id="transition-sweep" class="transition-sweep" hidden></div>

                <div id="stinger-layer" class="stinger-layer" hidden>
                    <video id="stinger-video" playsinline preload="auto"></video>
                </div>
            </div>
        </div>

        <script src="/js/filament/filament/echo.js"></script>
        @php
            $stageUrls = [
                'logicTrigger' => route('obs.collection.logic-trigger', ['token' => $collection->public_token], false),
                'state' => route('obs.collection.state', ['token' => $collection->public_token], false),
            ];
        @endphp
        <script>
            (() => {
                let stageData = @json($stagePayload);
                const echoCfg = @json($echoConfig);
                const stageUrls = @json($stageUrls);
                const logicTriggerUrl = stageUrls.logicTrigger;
                const stageStateUrl = stageUrls.state;

                const frameEl = document.getElementById('stage-frame');
                const slotA = document.getElementById('slot-a');
                const slotB = document.getElementById('slot-b');
                const sweepEl = document.getElementById('transition-sweep');
                const stingerWrap = document.getElementById('stinger-layer');
                const stingerVideo = document.getElementById('stinger-video');
                const alertToast = document.getElementById('stage-alert-toast');

                let activeSlot = slotA;
                let standbySlot = slotB;
                let renderedViewId = null;
                let lastTransitionSeq = Number(stageData?.runtime_state?.last_transition?.seq || 0);
                let transitionLock = false;
                let lastEchoAt = 0;
                const preloadedAssets = new Set();
                let firedTimerEndMarker = null;

                const fitViewport = () => {
                    const w = Math.max(320, Number(stageData?.width || 1920));
                    const h = Math.max(180, Number(stageData?.height || 1080));
                    document.documentElement.style.setProperty('--stage-w', String(w));
                    document.documentElement.style.setProperty('--stage-h', String(h));
                    const scale = Math.min(window.innerWidth / w, window.innerHeight / h);
                    frameEl.style.transform = `scale(${scale})`;
                };

                window.addEventListener('resize', fitViewport);

                const preloadAssets = () => {
                    const urls = new Set();
                    if (Array.isArray(stageData?.assets)) {
                        stageData.assets.forEach((a) => {
                            if (a?.url && !preloadedAssets.has(a.url)) {
                                urls.add({ url: a.url, type: a.type });
                            }
                        });
                    }
                    urls.forEach(({ url, type }) => {
                        preloadedAssets.add(url);
                        if (type === 'image') {
                            const img = new Image();
                            img.src = url;
                        } else if (type === 'stinger' || type === 'video') {
                            const link = document.createElement('link');
                            link.rel = 'preload';
                            link.as = 'video';
                            link.href = url;
                            document.head.appendChild(link);
                        }
                    });
                };

                const findView = (viewId) => {
                    const views = Array.isArray(stageData?.views) ? stageData.views : [];
                    return views.find((v) => Number(v.id) === Number(viewId)) || views[0] || null;
                };

                const formatClock = (totalSeconds, showHours = false) => {
                    const safe = Math.max(0, Math.floor(totalSeconds));
                    const hrs = Math.floor(safe / 3600);
                    const mins = Math.floor((safe % 3600) / 60);
                    const secs = safe % 60;
                    const mm = String(mins).padStart(2, '0');
                    const ss = String(secs).padStart(2, '0');
                    return (showHours || hrs > 0) ? `${String(hrs).padStart(2, '0')}:${mm}:${ss}` : `${mm}:${ss}`;
                };

                const computeTimerSeconds = (mode = 'countdown') => {
                    const timer = stageData?.runtime_state?.timer || {};
                    const effectiveMode = mode || timer.mode || 'countdown';
                    const status = timer.status || 'stopped';
                    const nowMs = Date.now();

                    if (effectiveMode === 'clock') {
                        const d = new Date();
                        return {
                            text: `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`,
                            remaining: 0,
                        };
                    }

                    if (effectiveMode === 'countup') {
                        let secs = Number(timer.elapsed_base_seconds || 0);
                        if (status === 'running' && timer.started_at) {
                            secs += Math.max(0, Math.floor((nowMs - Date.parse(timer.started_at)) / 1000));
                        }
                        return { text: formatClock(secs, false), remaining: secs };
                    }

                    let rem = Number(timer.remaining_seconds ?? timer.duration_seconds ?? 0);
                    if (status === 'running' && timer.target_at) {
                        rem = Math.max(0, Math.ceil((Date.parse(timer.target_at) - nowMs) / 1000));
                    }
                    return { text: formatClock(rem, false), remaining: rem };
                };

                const shouldShowCondition = (tag, timerInfo) => {
                    const runtime = stageData?.runtime_state || {};
                    const tStatus = runtime.timer?.status || 'stopped';
                    switch (tag || 'always') {
                        case 'timer_running': return tStatus === 'running';
                        case 'timer_paused': return tStatus !== 'running';
                        case 'timer_under_10s': return timerInfo.remaining > 0 && timerInfo.remaining <= 10;
                        case 'lower_third_visible': return Boolean(runtime.lower_third?.visible);
                        case 'alert_visible': return Boolean(runtime.alert?.visible);
                        default: return true;
                    }
                };

                const applyViewThemeVars = (slot, view) => {
                    const base = stageData?.theme || {};
                    const ov = view?.theme_override || {};
                    const merged = { ...base, ...Object.fromEntries(Object.entries(ov).filter(([, v]) => v !== null && v !== '')) };
                    slot.style.setProperty('--v-accent', merged.accent_color || '#CDAD69');
                    slot.style.setProperty('--v-secondary', merged.secondary_color || '#F4E185');
                    slot.style.setProperty('--v-surface', merged.surface_color || '#0B0A08');
                    slot.style.setProperty('--v-text', merged.text_color || '#FAF7EE');
                    slot.style.setProperty('--v-muted', merged.muted_color || '#ABA291');
                    slot.style.setProperty('--v-blur', `${Number(merged.backdrop_blur ?? 18)}px`);
                    slot.style.setProperty('--v-opacity', String(merged.glass_opacity ?? 0.82));
                    slot.style.color = merged.text_color || '#FAF7EE';
                    return merged;
                };

                const renderViewIntoSlot = (slot, view, slotKey, animateEnter = true) => {
                    slot.innerHTML = '';
                    if (!view) return;

                    const w = Math.max(320, Number(stageData?.width || 1920));
                    const h = Math.max(180, Number(stageData?.height || 1080));
                    const hiddenSet = new Set(Array.isArray(stageData?.runtime_state?.hidden_layers) ? stageData.runtime_state.hidden_layers : []);
                    const theme = applyViewThemeVars(slot, view);
                    const layers = [...(Array.isArray(view.layers) ? view.layers : [])].sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0));

                    const visibleCutouts = layers.filter((l) => l.type === 'camera_cutout' && l.visible !== false && !hiddenSet.has(l.id));
                    const maskId = `cutout-mask-${slotKey}-${view.id}`;

                    if (visibleCutouts.length > 0 && view.background_mode !== 'transparent') {
                        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        svg.setAttribute('class', 'svg-defs-host');
                        svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
                        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                        const mask = document.createElementNS('http://www.w3.org/2000/svg', 'mask');
                        mask.setAttribute('id', maskId);
                        mask.setAttribute('maskUnits', 'userSpaceOnUse');

                        const baseRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        baseRect.setAttribute('x', '0');
                        baseRect.setAttribute('y', '0');
                        baseRect.setAttribute('width', String(w));
                        baseRect.setAttribute('height', String(h));
                        baseRect.setAttribute('fill', 'white');
                        mask.appendChild(baseRect);

                        visibleCutouts.forEach((c) => {
                            const hole = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                            const r = Number(c.radius || 0);
                            const cx = Number(c.x || 0) + Number(c.w || 0) / 2;
                            const cy = Number(c.y || 0) + Number(c.h || 0) / 2;
                            hole.setAttribute('x', String(c.x || 0));
                            hole.setAttribute('y', String(c.y || 0));
                            hole.setAttribute('width', String(c.w || 0));
                            hole.setAttribute('height', String(c.h || 0));
                            hole.setAttribute('rx', String(r));
                            hole.setAttribute('ry', String(r));
                            if (Number(c.rotation || 0) !== 0) {
                                hole.setAttribute('transform', `rotate(${Number(c.rotation)} ${cx} ${cy})`);
                            }
                            hole.setAttribute('fill', 'black');
                            mask.appendChild(hole);
                        });

                        defs.appendChild(mask);
                        svg.appendChild(defs);
                        slot.appendChild(svg);
                    }

                    // Backdrop Layer (opaque/solid/asset — punched by SVG mask if cutouts exist!)
                    if (view.background_mode !== 'transparent') {
                        const bd = document.createElement('div');
                        bd.className = 'view-backdrop-wrap';
                        if (visibleCutouts.length > 0) {
                            bd.style.mask = `url(#${maskId})`;
                            bd.style.webkitMask = `url(#${maskId})`;
                        }

                        if (view.background_mode === 'asset' && view.background_asset_url) {
                            if (view.background_asset_type === 'video' || /\.webm$|\.mp4$/i.test(view.background_asset_url)) {
                                const vid = document.createElement('video');
                                vid.className = 'view-backdrop-media';
                                vid.src = view.background_asset_url;
                                vid.autoplay = true;
                                vid.loop = true;
                                vid.muted = true;
                                vid.playsInline = true;
                                bd.appendChild(vid);
                            } else {
                                const img = document.createElement('img');
                                img.className = 'view-backdrop-media';
                                img.src = view.background_asset_url;
                                img.alt = '';
                                bd.appendChild(img);
                            }
                        } else if (view.background_mode === 'solid' && view.background_color) {
                            bd.style.background = view.background_color;
                        } else {
                            const grad = document.createElement('div');
                            grad.className = 'view-backdrop-theme';
                            bd.appendChild(grad);
                        }
                        slot.appendChild(bd);
                    }

                    // Render Content Layers
                    layers.forEach((layer, idx) => {
                        const el = document.createElement('div');
                        el.className = 'layer-node';
                        el.dataset.layerId = String(layer.id || idx);
                        el.dataset.layerType = String(layer.type || 'text');
                        el.dataset.conditionTag = String(layer.condition_tag || 'always');

                        el.style.left = `${Number(layer.x || 0)}px`;
                        el.style.top = `${Number(layer.y || 0)}px`;
                        el.style.width = `${Math.max(16, Number(layer.w || 200))}px`;
                        el.style.height = `${Math.max(16, Number(layer.h || 80))}px`;
                        el.style.zIndex = String(Number(layer.z_index || idx + 1) + 2);
                        el.style.opacity = String(layer.opacity ?? 1);
                        if (Number(layer.rotation || 0) !== 0) {
                            el.style.transform = `rotate(${Number(layer.rotation)}deg)`;
                        }

                        const p = layer.props || {};
                        const radius = Number(layer.radius ?? 0);

                        if (layer.type === 'camera_cutout') {
                            const frame = document.createElement('div');
                            frame.className = 'cutout-frame';
                            const bw = Number(p.border_width ?? 3);
                            const bc = p.border_color || theme.accent_color || '#CDAD69';
                            frame.style.borderRadius = `${radius}px`;
                            if (bw > 0) frame.style.border = `${bw}px solid ${bc}`;
                            if (p.glow) frame.style.boxShadow = `inset 0 0 0 1px rgba(255,236,179,0.22), 0 0 36px -14px ${bc}`;
                            el.appendChild(frame);
                        } else if (layer.type === 'shape') {
                            const box = document.createElement('div');
                            box.className = 'glass-box';
                            box.style.borderRadius = `${radius}px`;
                            if (p.fill_color) box.style.background = p.fill_color;
                            if (p.border_color) box.style.borderColor = p.border_color;
                            if (p.border_width !== undefined) box.style.borderWidth = `${Number(p.border_width)}px`;
                            el.appendChild(box);
                        } else if (layer.type === 'asset') {
                            if (p.url) {
                                if (p.media_kind === 'video' || /\.webm$|\.mp4$/i.test(p.url)) {
                                    const vid = document.createElement('video');
                                    vid.src = p.url;
                                    vid.autoplay = true;
                                    vid.loop = p.loop !== false;
                                    vid.muted = p.muted !== false;
                                    vid.playsInline = true;
                                    vid.style.cssText = `width:100%;height:100%;object-fit:${p.fit || 'contain'};border-radius:${radius}px;display:block;`;
                                    el.appendChild(vid);
                                } else {
                                    const img = document.createElement('img');
                                    img.src = p.url;
                                    img.alt = layer.name || '';
                                    img.style.cssText = `width:100%;height:100%;object-fit:${p.fit || 'contain'};border-radius:${radius}px;display:block;`;
                                    el.appendChild(img);
                                }
                            }
                        } else if (layer.type === 'text') {
                            const box = document.createElement('div');
                            box.style.cssText = `width:100%;height:100%;display:flex;align-items:center;padding:${Number(p.padding || 8)}px;border-radius:${radius}px;`;
                            box.style.justifyContent = p.align === 'left' ? 'flex-start' : (p.align === 'right' ? 'flex-end' : 'center');
                            box.style.textAlign = p.align || 'center';
                            if (p.bg_color) box.style.background = p.bg_color;
                            const span = document.createElement('span');
                            span.className = 'live-text-content';
                            span.textContent = p.text || '';
                            span.style.cssText = `font-size:${Number(p.font_size || 48)}px;font-weight:${Number(p.font_weight || 700)};line-height:1.12;color:${p.text_color || 'var(--v-text)'};`;
                            box.appendChild(span);
                            el.appendChild(box);
                        } else if (layer.type === 'timer') {
                            const box = document.createElement('div');
                            box.className = `timer-box${p.show_bg !== false ? ' glass-box' : ''}`;
                            box.style.borderRadius = `${radius}px`;
                            if (p.bg_color) box.style.background = p.bg_color;
                            const lbl = document.createElement('div');
                            lbl.className = 'timer-label';
                            lbl.textContent = p.label || stageData?.runtime_state?.timer?.label || 'TIMER';
                            const digits = document.createElement('div');
                            digits.className = 'timer-digits live-timer-digits';
                            digits.dataset.timerMode = String(p.mode || 'countdown');
                            digits.dataset.showHours = p.show_hours ? '1' : '0';
                            digits.style.fontSize = `${Number(p.font_size || 72)}px`;
                            if (p.text_color) digits.style.color = p.text_color;
                            box.append(lbl, digits);
                            el.appendChild(box);
                        } else if (layer.type === 'scoreboard') {
                            const sb = document.createElement('div');
                            sb.className = 'glass-box scoreboard-box live-scoreboard-widget';
                            sb.style.borderRadius = `${radius}px`;
                            sb.innerHTML = `
                                <div class="sb-team">
                                    <span class="sb-name live-sb-a-name"></span>
                                    <span class="sb-score live-sb-a-score"></span>
                                </div>
                                <div class="sb-meta">
                                    <div class="sb-match live-sb-match"></div>
                                    <div class="sb-round live-sb-round"></div>
                                </div>
                                <div class="sb-team sb-team--right">
                                    <span class="sb-name live-sb-b-name"></span>
                                    <span class="sb-score live-sb-b-score"></span>
                                </div>
                            `;
                            el.appendChild(sb);
                        } else if (layer.type === 'lower_third') {
                            const l3 = document.createElement('div');
                            l3.className = 'glass-box lower-third-box live-l3-widget';
                            l3.style.borderRadius = `${radius}px`;
                            l3.innerHTML = `
                                <span class="l3-badge live-l3-badge"></span>
                                <div>
                                    <div class="l3-title live-l3-title"></div>
                                    <div class="l3-sub live-l3-sub"></div>
                                </div>
                            `;
                            el.appendChild(l3);
                        } else if (layer.type === 'ticker') {
                            const tk = document.createElement('div');
                            tk.className = 'glass-box ticker-box live-ticker-widget';
                            tk.style.borderRadius = `${radius}px`;
                            tk.style.setProperty('--ticker-sec', `${Number(p.speed_sec || 28)}s`);
                            tk.innerHTML = `
                                <span class="ticker-pill live-ticker-label"></span>
                                <div style="overflow:hidden;flex:1;">
                                    <div class="ticker-track live-ticker-track"></div>
                                </div>
                            `;
                            el.appendChild(tk);
                        } else if (layer.type === 'embed' && p.url) {
                            const iframe = document.createElement('iframe');
                            iframe.src = p.url;
                            iframe.title = layer.name || 'Embedded overlay widget';
                            iframe.allow = 'autoplay; camera; microphone';
                            iframe.style.cssText = `width:100%;height:100%;border:0;border-radius:${radius}px;background:transparent;`;
                            el.appendChild(iframe);
                        }

                        if (layer.loop_animation && layer.loop_animation !== 'none') {
                            el.classList.add(`loop-${layer.loop_animation}`);
                        }

                        if (animateEnter && layer.enter_animation && layer.enter_animation !== 'none') {
                            const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
                            const dur = reduced ? 120 : Number(layer.enter_duration_ms || 380);
                            const delay = reduced ? 0 : Number(layer.enter_delay_ms || 0);
                            const baseTransform = Number(layer.rotation || 0) !== 0 ? `rotate(${Number(layer.rotation)}deg) ` : '';
                            const keyframeMap = {
                                fade: [{ opacity: 0 }, { opacity: Number(layer.opacity ?? 1) }],
                                slide_up: [{ opacity: 0, transform: `${baseTransform}translateY(28px)` }, { opacity: Number(layer.opacity ?? 1), transform: `${baseTransform}translateY(0)` }],
                                slide_down: [{ opacity: 0, transform: `${baseTransform}translateY(-28px)` }, { opacity: Number(layer.opacity ?? 1), transform: `${baseTransform}translateY(0)` }],
                                slide_left: [{ opacity: 0, transform: `${baseTransform}translateX(36px)` }, { opacity: Number(layer.opacity ?? 1), transform: `${baseTransform}translateX(0)` }],
                                slide_right: [{ opacity: 0, transform: `${baseTransform}translateX(-36px)` }, { opacity: Number(layer.opacity ?? 1), transform: `${baseTransform}translateX(0)` }],
                                scale_in: [{ opacity: 0, transform: `${baseTransform}scale(0.92)` }, { opacity: Number(layer.opacity ?? 1), transform: `${baseTransform}scale(1)` }],
                                blur_in: [{ opacity: 0, filter: 'blur(8px)' }, { opacity: Number(layer.opacity ?? 1), filter: 'blur(0px)' }],
                            };
                            const frames = reduced ? keyframeMap.fade : (keyframeMap[layer.enter_animation] || keyframeMap.fade);
                            el.animate(frames, { duration: dur, delay, fill: 'both', easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });
                        }

                        slot.appendChild(el);
                    });

                    syncRuntimeToSlot(slot);
                };

                const syncRuntimeToSlot = (slot) => {
                    if (!slot) return;
                    const runtime = stageData?.runtime_state || {};
                    const hiddenSet = new Set(Array.isArray(runtime.hidden_layers) ? runtime.hidden_layers : []);
                    const timerInfo = computeTimerSeconds(runtime.timer?.mode);
                    const sb = runtime.scoreboard || {};
                    const l3 = runtime.lower_third || {};
                    const tk = runtime.ticker || {};
                    const al = runtime.alert || {};

                    slot.querySelectorAll('.layer-node').forEach((node) => {
                        const lid = node.dataset.layerId;
                        const lType = node.dataset.layerType;
                        const cond = node.dataset.conditionTag;
                        const view = findView(renderedViewId || stageData?.active_view_id);
                        const layerDef = (view?.layers || []).find((l) => String(l.id) === lid);
                        let visible = (!layerDef || layerDef.visible !== false) && !hiddenSet.has(lid) && shouldShowCondition(cond, timerInfo);
                        if (lType === 'lower_third' && !l3.visible) visible = false;
                        if (lType === 'ticker' && (!tk.visible || !Array.isArray(tk.items) || tk.items.length === 0)) visible = false;
                        node.hidden = !visible;
                    });

                    slot.querySelectorAll('.live-timer-digits').forEach((d) => {
                        const t = computeTimerSeconds(d.dataset.timerMode);
                        d.textContent = t.text;
                    });

                    slot.querySelectorAll('.live-scoreboard-widget').forEach((w) => {
                        w.querySelector('.live-sb-a-name').textContent = sb.team_a_name || 'Team A';
                        const aScore = w.querySelector('.live-sb-a-score');
                        aScore.textContent = String(sb.team_a_score ?? 0);
                        if (sb.team_a_color) aScore.style.borderColor = sb.team_a_color;

                        w.querySelector('.live-sb-b-name').textContent = sb.team_b_name || 'Team B';
                        const bScore = w.querySelector('.live-sb-b-score');
                        bScore.textContent = String(sb.team_b_score ?? 0);
                        if (sb.team_b_color) bScore.style.borderColor = sb.team_b_color;

                        w.querySelector('.live-sb-match').textContent = sb.match_label || '';
                        w.querySelector('.live-sb-round').textContent = sb.round_label || '';
                    });

                    slot.querySelectorAll('.live-l3-widget').forEach((w) => {
                        w.querySelector('.live-l3-badge').textContent = l3.badge || 'LIVE';
                        w.querySelector('.live-l3-title').textContent = l3.title || '';
                        w.querySelector('.live-l3-sub').textContent = l3.subtitle || '';
                    });

                    slot.querySelectorAll('.live-ticker-widget').forEach((w) => {
                        w.querySelector('.live-ticker-label').textContent = tk.label || 'LIVE';
                        const track = w.querySelector('.live-ticker-track');
                        const items = Array.isArray(tk.items) ? tk.items : [];
                        const signature = JSON.stringify(items);
                        if (track && track.dataset.sig !== signature) {
                            track.dataset.sig = signature;
                            track.replaceChildren();
                            [...items, ...items].forEach((msg, i) => {
                                const span = document.createElement('span');
                                span.textContent = String(msg);
                                if (i >= items.length) span.setAttribute('aria-hidden', 'true');
                                track.appendChild(span);
                            });
                        }
                    });

                    document.getElementById('stage-alert-title').textContent = al.title || 'Notice';
                    document.getElementById('stage-alert-message').textContent = al.message || '';
                    alertToast.hidden = !al.visible || !al.message;
                };

                const runViewTransition = async (nextView, tx) => {
                    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
                    const type = reduced && tx.type !== 'cut' ? 'fade' : (tx?.type || 'fade');
                    const dur = type === 'cut' ? 0 : Math.max(80, Number(tx?.duration_ms || 450));
                    const swapMs = Math.max(40, Math.min(dur, Number(tx?.swap_point_ms || Math.round(dur / 2))));
                    const slotKey = standbySlot === slotA ? 'a' : 'b';

                    if (type === 'cut') {
                        renderViewIntoSlot(activeSlot, nextView, activeSlot === slotA ? 'a' : 'b', true);
                        activeSlot.hidden = false;
                        standbySlot.hidden = true;
                        renderedViewId = nextView.id;
                        return;
                    }

                    renderViewIntoSlot(standbySlot, nextView, slotKey, true);

                    if (type === 'stinger' && tx?.stinger_url) {
                        stingerVideo.src = tx.stinger_url;
                        stingerVideo.currentTime = 0;
                        stingerWrap.hidden = false;
                        standbySlot.hidden = true;
                        stingerVideo.play().catch(() => {});

                        await new Promise((r) => setTimeout(r, swapMs));
                        activeSlot.hidden = true;
                        standbySlot.hidden = false;
                        [activeSlot, standbySlot] = [standbySlot, activeSlot];
                        renderedViewId = nextView.id;

                        await new Promise((r) => setTimeout(r, Math.max(120, dur - swapMs)));
                        stingerWrap.hidden = true;
                        return;
                    }

                    if (type === 'color_sweep') {
                        sweepEl.style.background = `linear-gradient(90deg, ${tx.sweep_color || 'var(--accent)'}, var(--secondary))`;
                        sweepEl.hidden = false;
                        sweepEl.animate(
                            [
                                { transform: 'translateX(-125%) skewX(-14deg)' },
                                { transform: 'translateX(0%) skewX(-14deg)', offset: 0.45 },
                                { transform: 'translateX(0%) skewX(-14deg)', offset: 0.55 },
                                { transform: 'translateX(125%) skewX(-14deg)' },
                            ],
                            { duration: dur, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
                        );
                        await new Promise((r) => setTimeout(r, swapMs));
                        activeSlot.hidden = true;
                        standbySlot.hidden = false;
                        [activeSlot, standbySlot] = [standbySlot, activeSlot];
                        renderedViewId = nextView.id;
                        await new Promise((r) => setTimeout(r, Math.max(50, dur - swapMs)));
                        sweepEl.hidden = true;
                        return;
                    }

                    standbySlot.hidden = false;
                    activeSlot.style.zIndex = '1';
                    standbySlot.style.zIndex = '2';

                    const inMap = {
                        fade: [{ opacity: 0 }, { opacity: 1 }],
                        slide_left: [{ transform: 'translate3d(100%, 0, 0)' }, { transform: 'translate3d(0, 0, 0)' }],
                        slide_right: [{ transform: 'translate3d(-100%, 0, 0)' }, { transform: 'translate3d(0, 0, 0)' }],
                        slide_up: [{ transform: 'translate3d(0, 100%, 0)' }, { transform: 'translate3d(0, 0, 0)' }],
                        slide_down: [{ transform: 'translate3d(0, -100%, 0)' }, { transform: 'translate3d(0, 0, 0)' }],
                        wipe_left: [{ clipPath: 'inset(0 0 0 100%)' }, { clipPath: 'inset(0 0 0 0)' }],
                        wipe_right: [{ clipPath: 'inset(0 100% 0 0)' }, { clipPath: 'inset(0 0 0 0)' }],
                        zoom: [{ opacity: 0, transform: 'scale(1.06)' }, { opacity: 1, transform: 'scale(1)' }],
                    };

                    const outAnim = activeSlot.animate([{ opacity: 1 }, { opacity: type === 'fade' || type === 'zoom' ? 0 : 1 }], { duration: dur, easing: 'ease-out' });
                    const inAnim = standbySlot.animate(inMap[type] || inMap.fade, { duration: dur, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' });

                    await Promise.allSettled([outAnim.finished, inAnim.finished]);
                    activeSlot.hidden = true;
                    activeSlot.style.zIndex = '';
                    standbySlot.style.zIndex = '';
                    [activeSlot, standbySlot] = [standbySlot, activeSlot];
                    renderedViewId = nextView.id;
                };

                const applyStagePayload = async (nextPayload) => {
                    if (!nextPayload || typeof nextPayload !== 'object') return;
                    stageData = nextPayload;
                    fitViewport();
                    preloadAssets();

                    const tx = stageData?.runtime_state?.last_transition || {};
                    const txSeq = Number(tx.seq || 0);
                    const targetView = findView(stageData.active_view_id);
                    if (!targetView) return;

                    if (renderedViewId === null) {
                        renderedViewId = targetView.id;
                        lastTransitionSeq = txSeq;
                        renderViewIntoSlot(activeSlot, targetView, activeSlot === slotA ? 'a' : 'b', true);
                        activeSlot.hidden = false;
                        return;
                    }

                    if (txSeq > lastTransitionSeq || Number(targetView.id) !== Number(renderedViewId)) {
                        lastTransitionSeq = txSeq;
                        if (!transitionLock) {
                            transitionLock = true;
                            try {
                                await runViewTransition(targetView, tx);
                            } finally {
                                transitionLock = false;
                            }
                        } else {
                            renderViewIntoSlot(activeSlot, targetView, activeSlot === slotA ? 'a' : 'b', false);
                            renderedViewId = targetView.id;
                        }
                        return;
                    }

                    // View stayed the same: re-evaluate layers only if editor modified structure or non-embed content changed
                    syncRuntimeToSlot(activeSlot);
                };

                // Tick clocks, dynamic conditions, and check on_timer_end view logic
                setInterval(() => {
                    syncRuntimeToSlot(activeSlot);

                    const timer = stageData?.runtime_state?.timer || {};
                    const activeView = findView(renderedViewId || stageData?.active_view_id);
                    const action = activeView?.logic_rules?.on_timer_end_action || 'none';
                    if (
                        timer.mode === 'countdown'
                        && timer.status === 'running'
                        && timer.target_at
                        && action !== 'none'
                    ) {
                        const rem = Math.ceil((Date.parse(timer.target_at) - Date.now()) / 1000);
                        const marker = `${activeView.id}::${timer.target_at}`;
                        if (rem <= 0 && firedTimerEndMarker !== marker) {
                            firedTimerEndMarker = marker;
                            fetch(logicTriggerUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                                body: JSON.stringify({
                                    event: 'timer_end',
                                    view_id: Number(activeView.id),
                                    marker: String(timer.target_at),
                                }),
                            }).catch(() => {});
                        }
                    }
                }, 250);

                // Initial Render
                applyStagePayload(stageData);

                // Real-time WebSocket Sync via Reverb
                if (window.EchoFactory && echoCfg?.key) {
                    const runtimeCfg = { ...echoCfg };
                    if (['ob', 'localhost', '127.0.0.1'].includes(runtimeCfg.wsHost) && window.location.hostname) {
                        runtimeCfg.wsHost = window.location.hostname;
                        runtimeCfg.forceTLS = window.location.protocol === 'https:';
                    }
                    window.Echo = new window.EchoFactory(runtimeCfg);
                    window.Echo.channel(@json("obs.collection.{$collection->public_token}"))
                        .listen('.obs.collection.updated', (payload) => {
                            lastEchoAt = Date.now();
                            // If non-switch edit happened in builder (views updated), force fresh render on same view
                            const prevViewJson = JSON.stringify(findView(renderedViewId));
                            const nextView = (payload?.views || []).find((v) => Number(v.id) === Number(renderedViewId));
                            if (nextView && JSON.stringify(nextView) !== prevViewJson && Number(payload?.runtime_state?.last_transition?.seq || 0) === lastTransitionSeq) {
                                stageData = payload;
                                renderViewIntoSlot(activeSlot, nextView, activeSlot === slotA ? 'a' : 'b', false);
                                return;
                            }
                            applyStagePayload(payload);
                        });
                } else {
                    // Only poll lightly when Echo is unconfigured
                    setInterval(async () => {
                        try {
                            const res = await fetch(stageStateUrl, { headers: { Accept: 'application/json' } });
                            if (res.ok) applyStagePayload(await res.json());
                        } catch {}
                    }, 3000);
                }
            })();
        </script>
    </body>
</html>
