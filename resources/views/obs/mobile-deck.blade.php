@php
    $deckUrls = [
        'cmd' => route('obs.deck.command', ['studio' => $studio->slug], false),
        'macroTpl' => route('obs.deck.macro', ['studio' => $studio->slug, 'macro' => '__MACRO__'], false),
        'screenLiveTpl' => route('obs.deck.screen-live', ['screen' => '__SCREEN__'], false),
        'snapshot' => route('studio.snapshot', ['studio' => $studio->slug], false),
        'cmdResultTpl' => route('studio.command-result', ['studio' => $studio->slug, 'commandUuid' => '__UUID__'], false),
        'controlMode' => route('studio.control-mode', ['studio' => $studio->slug], false),
        'collectionsStore' => route('studio.collections.store', ['studio' => $studio->slug], false),
        'builderTpl' => route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
        'switchTpl' => route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
        'runtimeTpl' => route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
        'deckLayoutTpl' => route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
        'pushTpl' => route('studio.collections.push', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
    ];
    $initialLiveOutputs = $studio->getOutputs();
    $initialObsScenes = $studio->getScenes();
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $studio->name }} · Mobile Control Deck</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;
                --bg: #070706;
                --surface: #11110F;
                --surface-raised: #171613;
                --surface-well: #0B0B09;
                --hairline: rgba(255, 255, 255, 0.085);
                --border-gold: rgba(205, 173, 105, 0.34);
                --gold: #F4E185;
                --gold-strong: #CDAD69;
                --positive: #4CC38A;
                --danger: #E5484D;
                --cobalt: #60A5FA;
                --violet: #C084FC;
                --text: #FAF7EE;
                --muted: #9E9686;
                --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
            }

            *, *::before, *::after { box-sizing: border-box; }
            html, body {
                margin: 0;
                min-block-size: 100dvb;
                background:
                    radial-gradient(circle at 12% 0%, rgba(205, 173, 105, 0.11), transparent 42%),
                    radial-gradient(circle at 88% 12%, rgba(76, 195, 138, 0.07), transparent 44%),
                    #070706;
                color: var(--text);
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
                overflow-x: clip;
            }

            :is(a, button, input, select, textarea):focus-visible {
                outline: 2px solid var(--gold);
                outline-offset: 2px;
            }

            [hidden] { display: none !important; }

            .shell {
                inline-size: min(100%, 72rem);
                margin-inline: auto;
                padding: max(0.75rem, env(safe-area-inset-top)) clamp(0.75rem, 2.5vw, 1.5rem) max(3rem, env(safe-area-inset-bottom));
                display: grid;
                gap: 0.875rem;
            }

            .glass {
                background: linear-gradient(180deg, rgba(22, 21, 18, 0.92) 0%, rgba(13, 13, 11, 0.96) 100%);
                border: 1px solid var(--hairline);
                border-radius: 1.1rem;
                padding: 0.9rem 1rem;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 14px 36px -20px rgba(0, 0, 0, 0.85);
            }

            .topbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem 1rem;
            }

            .obs-deck-brand-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
            }

            .obs-deck-brand-link {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                color: var(--text);
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 600;
            }

            .obs-deck-brand-link:hover { color: var(--gold); }

            .obs-brand-version-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.1rem 0.48rem;
                border-radius: 9999px;
                border: 1px solid var(--border-gold);
                background: rgba(205, 173, 105, 0.12);
                color: var(--gold);
                font-variant-numeric: tabular-nums;
                font-size: 0.6875rem;
                font-weight: 500;
                text-decoration: none;
            }

            .obs-reverb-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.22rem 0.6rem;
                border-radius: 9999px;
                font-size: 0.72rem;
                font-weight: 500;
                white-space: nowrap;
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.04);
                color: #C8C0AF;
            }

            .obs-reverb-dot {
                inline-size: 0.42rem;
                block-size: 0.42rem;
                flex-shrink: 0;
                border-radius: 50%;
                background-color: #8E8777;
            }

            .obs-reverb-pill.is-active {
                border-color: rgba(76, 195, 138, 0.34);
                background-color: rgba(76, 195, 138, 0.08);
                color: #9EE6C3;
            }

            .obs-reverb-pill.is-active .obs-reverb-dot { background-color: var(--positive); }

            .obs-reverb-pill.is-offline {
                border-color: rgba(229, 72, 77, 0.32);
                background-color: rgba(229, 72, 77, 0.08);
                color: #F4A3A6;
            }

            .obs-reverb-pill.is-offline .obs-reverb-dot { background-color: var(--danger); }

            .status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.22rem 0.65rem;
                border-radius: 9999px;
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                border: 1px solid var(--hairline);
                background: var(--surface-raised);
                color: var(--muted);
            }

            .status-pill--online { color: #9EE6C3; border-color: rgba(76, 195, 138, 0.38); background: rgba(76, 195, 138, 0.08); }
            .status-pill--streaming { color: #FCA5A5; border-color: rgba(229, 72, 77, 0.5); background: rgba(229, 72, 77, 0.12); }

            .mode-switch {
                display: inline-flex;
                align-items: center;
                gap: 0.2rem;
                padding: 0.2rem;
                border-radius: 0.65rem;
                background: var(--surface-well);
                border: 1px solid var(--hairline);
            }

            .deck-pill-btn {
                min-block-size: 2rem;
                padding: 0.2rem 0.65rem;
                border-radius: 0.5rem;
                border: 1px solid transparent;
                background: transparent;
                color: var(--muted);
                font: inherit;
                font-size: 0.74rem;
                font-weight: 500;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                white-space: nowrap;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out), color 140ms var(--ease-out);
            }

            .deck-pill-btn:hover { color: var(--text); background: rgba(255, 255, 255, 0.05); }
            .deck-pill-btn.is-active {
                background: rgba(205, 173, 105, 0.16);
                border-color: var(--border-gold);
                color: var(--gold);
            }
            .deck-pill-btn.is-gold {
                background: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
            }
            .deck-pill-btn.is-outline {
                border-color: var(--hairline);
                background: var(--surface-raised);
                color: var(--text);
            }
            .deck-pill-btn.is-outline:hover { border-color: var(--border-gold); }
            .deck-pill-btn.is-danger {
                border-color: rgba(229, 72, 77, 0.35);
                background: rgba(229, 72, 77, 0.12);
                color: #FCA5A5;
            }

            .deck-select, .deck-input {
                min-block-size: 2.25rem;
                padding: 0.35rem 0.65rem;
                border-radius: 0.6rem;
                border: 1px solid var(--hairline);
                background: var(--surface-well);
                color: var(--text);
                font: inherit;
                font-size: 0.8rem;
            }

            .deck-input { inline-size: 100%; }

            .deck-offline-banner {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                border-radius: 0.9rem;
                border: 1px solid rgba(205, 173, 105, 0.36);
                background: rgba(205, 173, 105, 0.09);
            }

            .section-title {
                margin: 0;
                font-size: 0.74rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--gold);
                font-weight: 600;
            }

            /* Configurable Stream Deck Toolbar & Edit Bar */
            .stage-deck-wrap {
                display: grid;
                gap: 0.9rem;
            }

            .deck-controls-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.6rem;
            }

            .deck-controls-group {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
            }

            .edit-mode-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding: 0.65rem 0.8rem;
                border-radius: 0.8rem;
                border: 1px solid var(--border-gold);
                background: rgba(205, 173, 105, 0.08);
            }

            /* Configurable Tile Grid */
            .tile-board {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.7rem;
            }

            @media (min-width: 600px) {
                .tile-board {
                    grid-template-columns: repeat(var(--deck-cols-sm, 3), minmax(0, 1fr));
                }
            }

            @media (min-width: 920px) {
                .tile-board {
                    grid-template-columns: repeat(var(--deck-cols-lg, 4), minmax(0, 1fr));
                }
            }

            .tile-board[data-fixed-cols="2"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tile-board[data-fixed-cols="3"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tile-board[data-fixed-cols="4"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tile-board[data-fixed-cols="5"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }

            @media (min-width: 560px) {
                .tile-board[data-fixed-cols="3"] { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .tile-board[data-fixed-cols="4"] { grid-template-columns: repeat(4, minmax(0, 1fr)); }
                .tile-board[data-fixed-cols="5"] { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            }

            .deck-tile {
                position: relative;
                min-block-size: 7.75rem;
                border-radius: 0.95rem;
                border: 1px solid var(--hairline);
                background: linear-gradient(180deg, #191815 0%, #12110F 100%);
                color: var(--text);
                padding: 0.65rem;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 0.5rem;
                text-align: start;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 8px 20px -14px rgba(0, 0, 0, 0.9);
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out), transform 110ms var(--ease-out), box-shadow 140ms var(--ease-out);
                user-select: none;
                -webkit-tap-highlight-color: transparent;
            }

            .deck-tile--tap {
                cursor: pointer;
                font: inherit;
                inline-size: 100%;
            }

            .deck-tile--tap:hover {
                border-color: var(--border-gold);
                background: linear-gradient(180deg, #1E1D19 0%, #151411 100%);
            }

            @media (prefers-reduced-motion: no-preference) {
                .deck-tile--tap:active {
                    transform: scale(0.975);
                }
            }

            @media (min-width: 520px) {
                .deck-tile.is-wide { grid-column: span 2; }
            }

            /* Tile Color Accents */
            .deck-tile[data-tile-color="gold"] { border-color: rgba(205, 173, 105, 0.36); background: linear-gradient(180deg, rgba(205, 173, 105, 0.14) 0%, #13120F 100%); }
            .deck-tile[data-tile-color="emerald"] { border-color: rgba(76, 195, 138, 0.34); background: linear-gradient(180deg, rgba(76, 195, 138, 0.12) 0%, #111412 100%); }
            .deck-tile[data-tile-color="crimson"] { border-color: rgba(229, 72, 77, 0.36); background: linear-gradient(180deg, rgba(229, 72, 77, 0.14) 0%, #151010 100%); }
            .deck-tile[data-tile-color="cobalt"] { border-color: rgba(96, 165, 250, 0.34); background: linear-gradient(180deg, rgba(96, 165, 250, 0.13) 0%, #101318 100%); }
            .deck-tile[data-tile-color="violet"] { border-color: rgba(192, 132, 252, 0.34); background: linear-gradient(180deg, rgba(192, 132, 252, 0.13) 0%, #151118 100%); }

            /* Live Active / On-Air Highlight */
            .deck-tile.is-on-air {
                border-color: var(--positive) !important;
                background: linear-gradient(180deg, rgba(76, 195, 138, 0.2) 0%, rgba(16, 26, 21, 0.96) 100%) !important;
                box-shadow: 0 0 0 1px rgba(76, 195, 138, 0.35), 0 14px 28px -16px rgba(76, 195, 138, 0.45);
            }

            .deck-tile.is-live-danger {
                border-color: var(--danger) !important;
                background: linear-gradient(180deg, rgba(229, 72, 77, 0.22) 0%, rgba(28, 13, 14, 0.96) 100%) !important;
            }

            .deck-tile.is-dragging {
                opacity: 0.45;
                border-style: dashed;
            }

            /* 16:9 Mini Wireframe inside Switch View Tiles */
            .tile-thumb {
                position: relative;
                inline-size: 100%;
                aspect-ratio: 16 / 9;
                border-radius: 0.55rem;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.08);
                background: #0A0908;
            }

            .tile-thumb.is-transparent {
                background-color: #131210;
                background-image:
                    linear-gradient(45deg, #1d1c19 25%, transparent 25%),
                    linear-gradient(-45deg, #1d1c19 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #1d1c19 75%),
                    linear-gradient(-45deg, transparent 75%, #1d1c19 75%);
                background-size: 10px 10px;
                background-position: 0 0, 0 5px, 5px -5px, -5px 0;
            }

            .mini-layer { position: absolute; border-radius: 2px; pointer-events: none; }
            .mini-layer--cutout {
                background-image:
                    linear-gradient(45deg, rgba(255,255,255,0.14) 25%, transparent 25%),
                    linear-gradient(-45deg, rgba(255,255,255,0.14) 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.14) 75%),
                    linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.14) 75%);
                background-size: 7px 7px;
                background-color: rgba(0, 0, 0, 0.4);
                border: 1px dashed rgba(205, 173, 105, 0.72);
            }
            .mini-layer--accent { background: rgba(205, 173, 105, 0.28); border: 1px solid rgba(205, 173, 105, 0.48); }
            .mini-layer--block { background: rgba(250, 247, 238, 0.14); border: 1px solid rgba(250, 247, 238, 0.2); }

            .tile-badge-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.4rem;
            }

            .tile-type-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.12rem 0.45rem;
                border-radius: 9999px;
                font-size: 0.625rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.09);
                color: var(--muted);
            }

            .tile-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.12rem 0.48rem;
                border-radius: 9999px;
                font-size: 0.625rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                background: rgba(7, 7, 6, 0.84);
                border: 1px solid rgba(76, 195, 138, 0.55);
                color: #9EE6C3;
            }

            .tile-status-pill--thumb {
                position: absolute;
                inset-block-start: 0.35rem;
                inset-inline-end: 0.35rem;
            }

            .tile-status-dot {
                inline-size: 0.38rem;
                block-size: 0.38rem;
                border-radius: 50%;
                background: var(--positive);
            }

            .tile-metric-big {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 1.5rem;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                color: var(--gold);
                line-height: 1.1;
            }

            /* Interactive Multi-Control Timer Tile */
            .timer-progress-track {
                inline-size: 100%;
                block-size: 0.25rem;
                border-radius: 9999px;
                background: rgba(255, 255, 255, 0.08);
                overflow: hidden;
            }

            .timer-progress-fill {
                block-size: 100%;
                inline-size: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--gold-strong), var(--gold));
                transition: inline-size 220ms linear;
            }

            .deck-tile.is-on-air .timer-progress-fill {
                background: linear-gradient(90deg, #34A874, var(--positive));
            }

            .tile-timer-actions {
                display: grid;
                gap: 0.3rem;
            }

            .tile-timer-row {
                display: grid;
                gap: 0.28rem;
            }

            .tile-timer-row--primary {
                grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            }

            .tile-timer-row--nudge {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .tile-ctrl-btn {
                min-block-size: 1.85rem;
                padding: 0.15rem 0.4rem;
                border-radius: 0.45rem;
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(7, 7, 6, 0.68);
                color: var(--text);
                font: inherit;
                font-size: 0.7rem;
                font-weight: 600;
                cursor: pointer;
                white-space: nowrap;
                transition: border-color 130ms var(--ease-out), background-color 130ms var(--ease-out), color 130ms var(--ease-out);
            }

            .tile-ctrl-btn:hover { border-color: var(--border-gold); color: var(--gold); }
            .tile-ctrl-btn--play {
                background: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                border-color: rgba(244, 225, 133, 0.5);
                color: #070706;
            }
            .tile-ctrl-btn--play:hover { color: #070706; background: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%); }
            .tile-ctrl-btn--pause {
                background: rgba(76, 195, 138, 0.18);
                border-color: rgba(76, 195, 138, 0.55);
                color: #9EE6C3;
            }
            .tile-ctrl-btn--pause:hover { color: #D1FAE5; border-color: var(--positive); }

            /* Broadcast Timer Controls Modal */
            .timer-hero-box {
                padding: 0.9rem 1rem;
                border-radius: 0.85rem;
                background: var(--surface-well);
                border: 1px solid var(--hairline);
                display: grid;
                gap: 0.5rem;
            }

            .timer-hero-digits {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: clamp(2rem, 1.5rem + 2vw, 2.75rem);
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                line-height: 1;
                color: var(--gold);
                text-align: center;
            }

            .timer-presets-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.4rem;
            }

            .timer-mode-bar {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.25rem;
                padding: 0.2rem;
                border-radius: 0.6rem;
                background: var(--surface-well);
                border: 1px solid var(--hairline);
            }

            .tile-label {
                font-size: 0.875rem;
                font-weight: 600;
                line-height: 1.25;
                color: var(--text);
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .tile-sub {
                font-size: 0.6875rem;
                color: var(--muted);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Edit Overlay Bar on Tiles */
            .tile-edit-controls {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.25rem;
                padding-block-start: 0.45rem;
                border-block-start: 1px solid rgba(255, 255, 255, 0.09);
            }

            .tile-mini-btn {
                min-block-size: 1.7rem;
                min-inline-size: 1.7rem;
                padding: 0.1rem 0.42rem;
                border-radius: 0.4rem;
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(7, 7, 6, 0.72);
                color: var(--text);
                font: inherit;
                font-size: 0.6875rem;
                font-weight: 600;
                cursor: pointer;
            }

            .tile-mini-btn:hover { border-color: var(--gold); color: var(--gold); }
            .tile-mini-btn--danger { color: #FCA5A5; border-color: rgba(229, 72, 77, 0.38); }
            .tile-mini-btn--danger:hover { border-color: var(--danger); color: #FECACA; }

            .tile-add-ghost {
                min-block-size: 7.75rem;
                border-radius: 0.95rem;
                border: 1.5px dashed var(--border-gold);
                background: rgba(205, 173, 105, 0.04);
                color: var(--gold);
                font: inherit;
                font-size: 0.85rem;
                font-weight: 600;
                display: grid;
                place-items: center;
                gap: 0.25rem;
                cursor: pointer;
                padding: 1rem;
                text-align: center;
            }

            .tile-add-ghost:hover { background: rgba(205, 173, 105, 0.1); }

            /* Quick Live Text Drawer */
            .quick-drawer {
                display: grid;
                gap: 0.85rem;
                padding-block-start: 0.85rem;
                border-block-start: 1px solid var(--hairline);
            }

            @media (min-width: 720px) {
                .quick-drawer-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 0.85rem;
                }
            }

            .quick-card {
                display: grid;
                gap: 0.55rem;
                padding: 0.75rem;
                border-radius: 0.8rem;
                background: var(--surface-well);
                border: 1px solid var(--hairline);
            }

            /* Classic OBS Fallback Pads */
            .deck-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
            }

            @media (min-width: 640px) {
                .deck-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            }

            .deck-btn {
                min-block-size: 3.1rem;
                padding: 0.65rem 0.75rem;
                border-radius: 0.85rem;
                border: 1px solid var(--hairline);
                background: var(--surface-raised);
                color: var(--text);
                font: inherit;
                font-size: 0.85rem;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                text-align: center;
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out);
            }

            .deck-btn:hover { border-color: var(--border-gold); }
            .deck-btn--accent { border-color: var(--border-gold); color: var(--gold); background: rgba(205, 173, 105, 0.08); }
            .deck-btn--active {
                background: linear-gradient(135deg, rgba(76, 195, 138, 0.24), rgba(20, 62, 42, 0.36));
                border-color: rgba(76, 195, 138, 0.65);
                color: #9EE6C3;
            }

            /* Native Top-Layer Tile Configurator Dialog */
            dialog.tile-dialog {
                margin: auto;
                inline-size: min(94dvw, 32rem);
                max-block-size: 88dvh;
                padding: 0;
                border-radius: 1.05rem;
                border: 1px solid var(--border-gold);
                background: #12110F;
                color: var(--text);
                box-shadow: 0 28px 70px rgba(0, 0, 0, 0.85);
                opacity: 0;
                transition-property: opacity, transform, display, overlay;
                transition-duration: 180ms;
                transition-timing-function: var(--ease-out);
                transition-behavior: allow-discrete;
            }

            @media (prefers-reduced-motion: no-preference) {
                dialog.tile-dialog { transform: scale(0.96); }
            }

            dialog.tile-dialog[open] {
                opacity: 1;
                @starting-style { opacity: 0; }
                @media (prefers-reduced-motion: no-preference) {
                    transform: scale(1);
                    @starting-style { transform: scale(0.96); }
                }
            }

            dialog.tile-dialog::backdrop {
                background: rgba(7, 7, 6, 0.72);
            }

            .dialog-inner {
                padding: 1.1rem;
                display: grid;
                gap: 0.85rem;
            }

            .dialog-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding-block-end: 0.6rem;
                border-block-end: 1px solid var(--hairline);
            }

            .field-stack { display: grid; gap: 0.28rem; }
            .field-label { font-size: 0.7rem; font-weight: 500; color: var(--muted); }

            .swatch-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
            }

            .swatch-btn {
                inline-size: 2rem;
                block-size: 2rem;
                border-radius: 9999px;
                border: 2px solid rgba(255, 255, 255, 0.16);
                cursor: pointer;
                position: relative;
            }

            .swatch-btn.is-selected {
                border-color: var(--text);
                box-shadow: 0 0 0 2px rgba(244, 225, 133, 0.55);
            }

            .toast {
                position: fixed;
                inset-block-end: 1rem;
                inset-inline: 1rem;
                max-inline-size: 26rem;
                margin-inline: auto;
                padding: 0.7rem 1rem;
                border-radius: 9999px;
                background: rgba(18, 17, 14, 0.96);
                border: 1px solid var(--border-gold);
                color: var(--gold);
                text-align: center;
                font-size: 0.82rem;
                font-weight: 600;
                z-index: 70;
            }

            .toast--error {
                border-color: rgba(248, 113, 113, 0.7);
                color: #FECACA;
                background: rgba(40, 14, 14, 0.96);
            }
        </style>
    </head>
    <body>
        <div class="shell">
            {{-- Compact Deck Header --}}
            <header class="glass topbar">
                <div style="display: grid; gap: 0.18rem;">
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
                        <div style="font-size: clamp(1.05rem, 0.9rem + 0.65vw, 1.35rem); font-weight: 600; line-height: 1.15; letter-spacing: -0.02em; color: var(--gold);">{{ $studio->name }}</div>
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
                    <div style="font-size: 0.72rem; color: var(--muted);">Mobile Wireless Stream Deck</div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <x-reverb-status-pill />
                    <span id="studio-status" class="status-pill {{ $studio->status === 'streaming' ? 'status-pill--streaming' : ($studio->isOnline() ? 'status-pill--online' : '') }}">
                        {{ strtoupper($studio->status) }}
                    </span>
                    <div class="mode-switch" role="group" aria-label="Deck Mode">
                        <button type="button" class="deck-pill-btn{{ ($controlMode ?? 'screens') === 'screens' ? ' is-active' : '' }}" data-deck-mode="screens">Stage Tiles</button>
                        <button type="button" class="deck-pill-btn{{ ($controlMode ?? 'screens') !== 'screens' ? ' is-active' : '' }}" data-deck-mode="obs">Classic OBS</button>
                    </div>
                    <a href="{{ route('studio.show', ['studio' => $studio->slug], false) }}" class="deck-pill-btn is-outline">Studio</a>
                    <a href="{{ url('/admin') }}" class="deck-pill-btn">Admin</a>
                </div>
            </header>

            <section id="deck-offline-banner" class="deck-offline-banner" @if ($studio->isOnline()) hidden @endif>
                <div style="display: grid; gap: 0.15rem;">
                    <strong style="font-size: 0.875rem; color: var(--gold);">
                        {{ $studio->name }} OBS Agent is offline
                    </strong>
                    <span style="font-size: 0.76rem; color: var(--muted);">
                        App Screen View tiles still update your live Browser Source immediately. Start <code>obs-agent</code> only when controlling OBS outputs or legacy scenes.
                    </span>
                </div>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem;">
                    <a href="{{ route('studio.show', ['studio' => $studio->slug], false) }}" class="deck-pill-btn is-gold">
                        Connect / Install Agent
                    </a>
                    <button type="button" id="deck-offline-dismiss-btn" class="deck-pill-btn is-outline" aria-label="Dismiss OBS offline banner">
                        Dismiss
                    </button>
                </div>
            </section>

            {{-- PRIMARY MODE: Configurable Single-Browser Stage Tile Deck --}}
            <section id="deck-unified-screen-section" class="glass stage-deck-wrap" @if (($controlMode ?? 'screens') !== 'screens') hidden @endif>
                <div class="deck-controls-bar">
                    <div class="deck-controls-group">
                        <h2 class="section-title" style="margin-inline-end: 0.35rem;">Unified Screen Stage</h2>
                        <select id="deck-uni-collection-select" class="deck-select" aria-label="Select Event Screen Collection"></select>
                        <select id="deck-uni-tx-select" class="deck-select" aria-label="Global Transition Override">
                            <option value="default">Tile / View Transition</option>
                            @foreach (($transitionTypes ?? []) as $txKey => $txLabel)
                                <option value="{{ $txKey }}">{{ $txLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="deck-controls-group">
                        <button type="button" id="deck-open-timer-btn" class="deck-pill-btn is-outline" title="Open full Timer controls">
                            <span>Timer</span>
                            <strong id="deck-bar-timer-digits" data-live-tile-clock="1" style="font-family: ui-monospace, monospace; font-variant-numeric: tabular-nums; color: var(--gold);">05:00</strong>
                        </button>
                        <button type="button" id="deck-quick-panel-btn" class="deck-pill-btn is-outline">Live Text</button>
                        <a id="deck-uni-builder-link" href="#" class="deck-pill-btn is-outline">Canvas Builder</a>
                        <button type="button" id="deck-edit-mode-btn" class="deck-pill-btn is-gold">Edit Tiles</button>
                    </div>
                </div>

                {{-- Edit Mode Toolbar (Shown when customizing tile layout) --}}
                <div id="deck-edit-toolbar" class="edit-mode-bar" hidden>
                    <div class="deck-controls-group">
                        <button type="button" id="deck-add-tile-btn" class="deck-pill-btn is-gold">+ Add Tile</button>
                        <button type="button" id="deck-sync-views-btn" class="deck-pill-btn is-outline">+ Sync App Views</button>
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.74rem; color: var(--muted);">
                            <span>Columns</span>
                            <select id="deck-columns-select" class="deck-select" style="min-block-size: 1.95rem; font-size: 0.75rem;">
                                <option value="0">Auto</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </label>
                    </div>
                    <div class="deck-controls-group">
                        <button type="button" id="deck-reset-tiles-btn" class="deck-pill-btn is-outline">Reset Defaults</button>
                        <button type="button" id="deck-done-edit-btn" class="deck-pill-btn is-active">Done Editing</button>
                    </div>
                </div>

                {{-- Empty State when Studio has no Screen Collections yet --}}
                <div id="deck-no-collection-card" style="padding: 2rem 1rem; border-radius: 0.9rem; border: 1px dashed var(--border-gold); text-align: center; display: grid; gap: 0.6rem; justify-items: center;" hidden>
                    <strong style="font-size: 0.95rem; color: var(--text);">Create your first Event Screen Collection</strong>
                    <span style="font-size: 0.8rem; color: var(--muted); max-inline-size: 32rem;">Build reusable app scenes (Starting Soon, Camera Cutout, Gameplay, Scoreboard) once and switch them instantly from configurable tiles inside a single OBS Browser Source.</span>
                    <button type="button" id="deck-create-first-collection-btn" class="deck-pill-btn is-gold">+ Create Starter Screen Collection</button>
                </div>

                {{-- Configurable Hardware-Style Tile Grid --}}
                <div id="deck-tiles-grid" class="tile-board" aria-label="Configurable Stage Tiles"></div>

                {{-- Collapsible Quick Live Text, Timer & Scoreboard Controls --}}
                <div id="deck-quick-drawer" class="quick-drawer" hidden>
                    <div class="quick-drawer-grid">
                        <div class="quick-card">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <span class="section-title">Live Timer &amp; Scoreboard</span>
                                <div style="display: inline-flex; align-items: center; gap: 0.45rem;">
                                    <strong id="deck-uni-timer-display" style="font-family: ui-monospace, monospace; font-size: 1.05rem; color: var(--gold);">05:00</strong>
                                    <button type="button" id="deck-uni-t-more" class="deck-pill-btn is-outline" style="min-block-size: 1.7rem; padding: 0.1rem 0.5rem; font-size: 0.7rem;">Presets &amp; Mode</button>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.3rem;">
                                <button type="button" id="deck-uni-t-start" class="deck-pill-btn is-active">Start</button>
                                <button type="button" id="deck-uni-t-pause" class="deck-pill-btn is-outline">Pause</button>
                                <button type="button" id="deck-uni-t-reset" class="deck-pill-btn is-outline">Reset</button>
                                <button type="button" id="deck-uni-t-minus" class="deck-pill-btn is-outline">-1m</button>
                                <button type="button" id="deck-uni-t-plus" class="deck-pill-btn is-outline">+1m</button>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.3rem;">
                                <button type="button" class="deck-pill-btn is-outline" data-quick-timer-preset="60">1m</button>
                                <button type="button" class="deck-pill-btn is-outline" data-quick-timer-preset="180">3m</button>
                                <button type="button" class="deck-pill-btn is-outline" data-quick-timer-preset="300">5m</button>
                                <button type="button" class="deck-pill-btn is-outline" data-quick-timer-preset="600">10m</button>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem; padding-block-start: 0.35rem;">
                                <div style="display: grid; gap: 0.25rem; text-align: center;">
                                    <span id="deck-uni-sb-a-name" style="font-size: 0.74rem; color: var(--muted);">Team Alpha</span>
                                    <div style="display: flex; gap: 0.3rem; align-items: center;">
                                        <button type="button" id="deck-uni-sb-a-minus" class="deck-pill-btn is-outline" style="flex: 1;">-</button>
                                        <strong id="deck-uni-sb-a-score" style="min-inline-size: 1.8rem;">0</strong>
                                        <button type="button" id="deck-uni-sb-a-plus" class="deck-pill-btn is-active" style="flex: 1;">+</button>
                                    </div>
                                </div>
                                <div style="display: grid; gap: 0.25rem; text-align: center;">
                                    <span id="deck-uni-sb-b-name" style="font-size: 0.74rem; color: var(--muted);">Team Omega</span>
                                    <div style="display: flex; gap: 0.3rem; align-items: center;">
                                        <button type="button" id="deck-uni-sb-b-minus" class="deck-pill-btn is-outline" style="flex: 1;">-</button>
                                        <strong id="deck-uni-sb-b-score" style="min-inline-size: 1.8rem;">0</strong>
                                        <button type="button" id="deck-uni-sb-b-plus" class="deck-pill-btn is-active" style="flex: 1;">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-card">
                            <span class="section-title">Lower Third &amp; Alert Banner</span>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.4rem;">
                                <input id="deck-uni-l3-title" type="text" class="deck-input" placeholder="Lower third title" aria-label="Lower third title">
                                <input id="deck-uni-l3-sub" type="text" class="deck-input" placeholder="Subtitle / role" aria-label="Lower third subtitle">
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.4rem;">
                                <button type="button" id="deck-uni-l3-show" class="deck-pill-btn is-active">Show Lower Third</button>
                                <button type="button" id="deck-uni-l3-hide" class="deck-pill-btn is-outline">Hide</button>
                            </div>
                            <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 0.35rem;">
                                <input id="deck-uni-alert-msg" type="text" class="deck-input" placeholder="Pop-up alert message..." aria-label="Pop-up alert message">
                                <button type="button" id="deck-uni-alert-show" class="deck-pill-btn is-active">Alert</button>
                                <button type="button" id="deck-uni-alert-hide" class="deck-pill-btn is-outline">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- SECONDARY FALLBACK MODE: Classic OBS Scenes & Outputs --}}
            <div id="deck-classic-obs-wrapper" style="display: grid; gap: 0.875rem;" @if (($controlMode ?? 'screens') === 'screens') hidden @endif>
                <section id="deck-classic-scenes-section" class="glass" style="display: grid; gap: 0.75rem;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                            <h2 class="section-title">OBS Scenes</h2>
                            <span id="deck-action-feedback" style="font-size: 0.72rem; color: var(--gold);"></span>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <span id="deck-studio-mode-note" style="font-size: 0.72rem; color: var(--muted);">
                                {{ $studio->isStudioModeEnabled() ? 'Studio Mode ON' : 'Direct Cut' }}
                            </span>
                            <button type="button" id="deck-sync-btn" class="deck-pill-btn is-outline">Sync</button>
                        </div>
                    </div>
                    <div id="scenes-grid" class="deck-grid">
                        @forelse ($initialObsScenes as $scene)
                            @php $sceneName = $scene['sceneName'] ?? ''; @endphp
                            <button
                                type="button"
                                class="deck-btn {{ $studio->getCurrentProgramScene() === $sceneName ? 'deck-btn--active' : '' }}"
                                data-scene="{{ $sceneName }}"
                            >
                                {{ $sceneName }}
                            </button>
                        @empty
                            <p style="grid-column: 1 / -1; margin: 0; color: var(--muted); font-size: 0.85rem;">
                                Connect obs-agent to populate live OBS scenes.
                            </p>
                        @endforelse
                    </div>
                </section>

                <section class="glass" style="display: grid; gap: 0.75rem;">
                    <h2 class="section-title">OBS Outputs &amp; Studio Mode</h2>
                    <div class="deck-grid">
                        <button type="button" class="deck-btn {{ ! empty($initialLiveOutputs['streaming']) ? 'deck-btn--active' : '' }}" id="deck-btn-stream" data-cmd="ToggleStream">Toggle Stream</button>
                        <button type="button" class="deck-btn {{ ! empty($initialLiveOutputs['recording']) ? 'deck-btn--active' : '' }}" id="deck-btn-record" data-cmd="ToggleRecord">Toggle Record</button>
                        <button type="button" class="deck-btn {{ ! empty($initialLiveOutputs['virtualCam']) ? 'deck-btn--active' : '' }}" id="deck-btn-vcam" data-cmd="ToggleVirtualCam">Virtual Cam</button>
                        <button type="button" class="deck-btn" data-cmd="ToggleRecordPause">Pause Record</button>
                        <button type="button" class="deck-btn" data-cmd="SaveReplayBuffer">Save Replay</button>
                        <button type="button" class="deck-btn" data-cmd="TriggerStudioModeTransition">Transition</button>
                    </div>
                </section>

                @if ($macros->isNotEmpty())
                    <section class="glass" style="display: grid; gap: 0.75rem;">
                        <h2 class="section-title">1-Click Broadcast Macros</h2>
                        <div class="deck-grid">
                            @foreach ($macros as $macro)
                                <button type="button" class="deck-btn deck-btn--accent" data-macro="{{ $macro->id }}">
                                    {{ $macro->name }}
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($screens->isNotEmpty())
                    <section id="deck-legacy-overlay-section" class="glass" style="display: grid; gap: 0.65rem;">
                        <h2 class="section-title">Legacy Overlay Push</h2>
                        <select id="live-screen-id" class="deck-select">
                            @foreach ($screens as $screen)
                                <option value="{{ $screen->id }}">{{ $screen->name }} ({{ $screen->template_type }})</option>
                            @endforeach
                        </select>
                        <input id="live-guest-name" type="text" class="deck-input" placeholder="Lower-third name / headline...">
                        <button type="button" id="push-live-text" class="deck-btn deck-btn--accent">Broadcast Live Update</button>
                    </section>
                @endif
            </div>
        </div>

        {{-- Native Top-Layer Modal: Configure Tile --}}
        <dialog id="tile-editor-dialog" class="tile-dialog" aria-labelledby="tile-editor-heading">
            <form id="tile-editor-form" class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="tile-editor-heading" style="margin: 0; font-size: 0.95rem; font-weight: 600;">Add Configurable Tile</h2>
                    <button type="button" id="tile-dialog-close" class="deck-pill-btn">Close</button>
                </div>

                <label class="field-stack">
                    <span class="field-label">Tile Action Type</span>
                    <select id="tile-field-type" class="deck-select">
                        <option value="switch_view">App Screen View (Single Browser Source)</option>
                        <option value="timer">Timer Control (Start / Pause / Preset)</option>
                        <option value="scoreboard">Scoreboard Control (+1 / -1 / Swap / Reset)</option>
                        <option value="lower_third">Lower Third Banner (Toggle / Preset Speaker)</option>
                        <option value="alert">Pop-Up Alert Banner</option>
                        <option value="ticker">Scrolling Ticker Toggle</option>
                        <option value="layer_toggle">Toggle Single Layer On/Off</option>
                        <option value="obs_action">OBS Action (Push Stage / Stream / Record / Macro)</option>
                    </select>
                </label>

                {{-- Dynamic Config Host --}}
                <div id="tile-dynamic-config" style="display: grid; gap: 0.65rem;"></div>

                <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0.55rem;">
                    <label class="field-stack">
                        <span class="field-label">Tile Title</span>
                        <input type="text" id="tile-field-label" class="deck-input" maxlength="80" required placeholder="e.g. Starting Soon">
                    </label>
                    <label class="field-stack">
                        <span class="field-label">Subtitle (optional)</span>
                        <input type="text" id="tile-field-subtitle" class="deck-input" maxlength="120" placeholder="Auto or custom">
                    </label>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <div class="field-stack">
                        <span class="field-label">Tile Accent Color</span>
                        <div id="tile-color-swatches" class="swatch-row">
                            <button type="button" class="swatch-btn is-selected" data-swatch="neutral" title="Obsidian Neutral" style="background: #2A2925;"></button>
                            <button type="button" class="swatch-btn" data-swatch="gold" title="Studio Gold" style="background: #CDAD69;"></button>
                            <button type="button" class="swatch-btn" data-swatch="emerald" title="Emerald" style="background: #4CC38A;"></button>
                            <button type="button" class="swatch-btn" data-swatch="crimson" title="Broadcast Red" style="background: #E5484D;"></button>
                            <button type="button" class="swatch-btn" data-swatch="cobalt" title="Cobalt Blue" style="background: #60A5FA;"></button>
                            <button type="button" class="swatch-btn" data-swatch="violet" title="Violet" style="background: #C084FC;"></button>
                        </div>
                    </div>

                    <label class="field-stack">
                        <span class="field-label">Tile Width</span>
                        <select id="tile-field-span" class="deck-select">
                            <option value="1">1×1 Standard</option>
                            <option value="2">2×1 Wide</option>
                        </select>
                    </label>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);">
                    <button type="button" id="tile-dialog-delete" class="deck-pill-btn is-danger" hidden>Remove Tile</button>
                    <div style="display: flex; gap: 0.45rem; margin-inline-start: auto;">
                        <button type="button" id="tile-dialog-cancel" class="deck-pill-btn is-outline">Cancel</button>
                        <button type="submit" class="deck-pill-btn is-gold">Save Tile</button>
                    </div>
                </div>
            </form>
        </dialog>

        {{-- Native Top-Layer Modal: Full Broadcast Timer Controls --}}
        <dialog id="timer-controls-dialog" class="tile-dialog" aria-labelledby="timer-modal-heading">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <div>
                        <h2 id="timer-modal-heading" style="margin: 0; font-size: 0.95rem; font-weight: 600;">Stage Timer Controls</h2>
                        <div style="font-size: 0.7rem; color: var(--muted);">Updates your live Browser Source immediately</div>
                    </div>
                    <button type="button" id="timer-modal-close" class="deck-pill-btn">Close</button>
                </div>

                <div class="timer-hero-box">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span id="timer-modal-mode-badge" class="tile-type-chip">Countdown</span>
                        <span id="timer-modal-status" class="tile-status-pill">Stopped</span>
                    </div>
                    <div id="timer-modal-digits" class="timer-hero-digits">05:00</div>
                    <div class="timer-progress-track">
                        <div id="timer-modal-progress-bar" class="timer-progress-fill" style="inline-size: 100%;"></div>
                    </div>
                    <div id="timer-modal-rule-hint" style="font-size: 0.7rem; color: var(--muted); text-align: center;"></div>
                </div>

                {{-- Transport & Quick Time Nudges --}}
                <div style="display: grid; gap: 0.4rem;">
                    <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 0.4rem;">
                        <button type="button" id="tm-toggle-btn" class="deck-pill-btn is-gold" style="min-block-size: 2.6rem; font-size: 0.85rem;">Start Timer</button>
                        <button type="button" id="tm-reset-btn" class="deck-pill-btn is-outline" style="min-block-size: 2.6rem; font-size: 0.85rem;">Reset</button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.35rem;">
                        <button type="button" class="deck-pill-btn is-outline" data-tm-add="-60">-1m</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-add="-30">-30s</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-add="30">+30s</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-add="60">+1m</button>
                    </div>
                </div>

                {{-- Quick Presets --}}
                <div style="display: grid; gap: 0.4rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span class="field-label">Quick Presets</span>
                        <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: var(--muted); cursor: pointer;">
                            <input type="checkbox" id="tm-preset-autostart" style="accent-color: var(--gold-strong);">
                            <span>Auto-start</span>
                        </label>
                    </div>
                    <div class="timer-presets-grid">
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="60">1:00</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="120">2:00</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="180">3:00</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="300">5:00</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="600">10:00</button>
                        <button type="button" class="deck-pill-btn is-outline" data-tm-preset="900">15:00</button>
                    </div>
                </div>

                {{-- Custom Minutes : Seconds Setter --}}
                <div style="display: grid; gap: 0.4rem;">
                    <span class="field-label">Custom Duration (Min : Sec)</span>
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto auto; gap: 0.35rem; align-items: center;">
                        <input type="number" id="tm-custom-min" class="deck-input" min="0" max="180" value="5" aria-label="Minutes" placeholder="Min">
                        <input type="number" id="tm-custom-sec" class="deck-input" min="0" max="59" value="0" aria-label="Seconds" placeholder="Sec">
                        <button type="button" id="tm-set-btn" class="deck-pill-btn is-outline">Set</button>
                        <button type="button" id="tm-set-start-btn" class="deck-pill-btn is-gold">Set &amp; Start</button>
                    </div>
                </div>

                {{-- Timer Mode & On-Screen Label --}}
                <div style="display: grid; gap: 0.5rem; padding-block-start: 0.45rem; border-block-start: 1px solid var(--hairline);">
                    <div class="field-stack">
                        <span class="field-label">Timer Mode</span>
                        <div class="timer-mode-bar" role="group" aria-label="Timer Mode">
                            <button type="button" class="deck-pill-btn is-active" data-tm-mode="countdown">Countdown</button>
                            <button type="button" class="deck-pill-btn" data-tm-mode="countup">Count-Up</button>
                            <button type="button" class="deck-pill-btn" data-tm-mode="clock">Live Clock</button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 0.35rem;">
                        <input type="text" id="tm-label-input" class="deck-input" maxlength="60" placeholder="On-screen label (e.g. LIVE IN, HALFTIME)" aria-label="Timer on-screen label">
                        <button type="button" id="tm-label-save" class="deck-pill-btn is-outline">Set Label</button>
                    </div>
                </div>
            </div>
        </dialog>

        <div id="toast" class="toast" role="status" hidden></div>

        <script src="/js/filament/filament/echo.js"></script>
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const studioId = @json($studio->id);
                const studioSlug = @json($studio->slug);
                const deckUrls = @json($deckUrls);
                const transitionOptions = @json($transitionTypes ?? []);
                const macrosList = @json($macrosPayload ?? []);
                const toast = document.getElementById('toast');
                const feedbackEl = document.getElementById('deck-action-feedback');

                let toastTimer = null;
                let studioOnline = @json($studio->isOnline());
                let liveOutputs = @json($initialLiveOutputs);
                let obsScenes = @json($initialObsScenes);
                let currentProgramScene = @json($studio->getCurrentProgramScene());
                let lastEchoAt = 0;
                let studioChannel = null;
                let latestSceneTapSeq = 0;
                const pendingResolvers = new Map();

                // Unified Stage Tile Deck State
                const stampCollectionPerf = (col) => {
                    if (col && typeof col === 'object') {
                        col._receivedAtPerf = performance.now();
                    }
                    return col;
                };
                let controlMode = @json($controlMode ?? 'screens');
                let collectionsData = (@json($collectionsPayload ?? [])).map(stampCollectionPerf);
                let activeCollectionId = @json($activeCollectionId);
                let subscribedCollectionToken = null;
                let subscribedCollectionChannel = null;
                let isEditMode = false;
                let editingTileIndex = null;
                let selectedSwatch = 'neutral';
                let dragTileIndex = null;

                const createCommandUuid = () => {
                    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
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
                        feedbackEl.style.color = isError ? '#FCA5A5' : 'var(--gold)';
                    }
                    if (!toast) return;
                    clearTimeout(toastTimer);
                    toast.textContent = msg;
                    toast.classList.toggle('toast--error', Boolean(isError));
                    toast.hidden = false;
                    toastTimer = setTimeout(() => { toast.hidden = true; }, isError ? 3400 : 2100);
                };

                const requestJson = async (method, url, body = null) => {
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: body !== null ? JSON.stringify(body) : undefined,
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => null);
                        throw new Error(err?.message || (res.status === 419 ? 'Session expired — reload page' : `HTTP ${res.status}`));
                    }
                    return res.json();
                };

                const postJson = (url, body) => requestJson('POST', url, body);
                const putJson = (url, body) => requestJson('PUT', url, body);

                const waitForCommandResult = (commandUuid, timeoutMs = 4000) => new Promise((resolve) => {
                    if (!commandUuid) { resolve(null); return; }
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
                            const res = await fetch(deckUrls.cmdResultTpl.replace('__UUID__', commandUuid), {
                                headers: { Accept: 'application/json' },
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data.status === 'succeeded' || data.status === 'failed') {
                                    finish(data);
                                    return;
                                }
                            }
                        } catch {}
                        if (Date.now() - started >= timeoutMs) { finish(null); return; }
                        setTimeout(poll, 350);
                    };
                    setTimeout(poll, isEchoReady() ? 1150 : 250);
                });

                const deckOfflineDismissKey = `obs_deck_offline_dismissed_v2_${studioSlug}`;
                const isDeckOfflineDismissed = () => {
                    try {
                        return localStorage.getItem(deckOfflineDismissKey) === '1';
                    } catch {
                        return false;
                    }
                };
                const syncDeckOfflineBanner = () => {
                    const offlineBanner = document.getElementById('deck-offline-banner');
                    if (!offlineBanner) return;
                    offlineBanner.hidden = studioOnline || controlMode === 'screens' || isDeckOfflineDismissed();
                };
                document.getElementById('deck-offline-dismiss-btn')?.addEventListener('click', () => {
                    try {
                        localStorage.setItem(deckOfflineDismissKey, '1');
                    } catch {}
                    syncDeckOfflineBanner();
                });

                const applyLiveState = (status, isOnlineFlag, liveState) => {
                    const statusEl = document.getElementById('studio-status');
                    const normalizedStatus = String(status || '').toLowerCase();

                    if (typeof isOnlineFlag === 'boolean') studioOnline = isOnlineFlag;
                    else if (normalizedStatus) studioOnline = normalizedStatus !== 'offline';

                    if (statusEl && status) {
                        statusEl.textContent = String(status).toUpperCase();
                        statusEl.className = 'status-pill ' + (normalizedStatus === 'streaming'
                            ? 'status-pill--streaming'
                            : (studioOnline ? 'status-pill--online' : ''));
                    }
                    syncDeckOfflineBanner();

                    if (!liveState || typeof liveState !== 'object') return;
                    if (liveState.currentProgramScene) currentProgramScene = liveState.currentProgramScene;
                    if (liveState.outputs) liveOutputs = liveState.outputs;
                    if (Array.isArray(liveState.scenes)) obsScenes = liveState.scenes;

                    const modeNote = document.getElementById('deck-studio-mode-note');
                    if (modeNote && liveState.studioModeEnabled !== undefined) {
                        modeNote.textContent = liveState.studioModeEnabled ? 'Studio Mode ON' : 'Direct Cut';
                    }

                    document.getElementById('deck-btn-stream')?.classList.toggle('deck-btn--active', Boolean(liveOutputs.streaming));
                    document.getElementById('deck-btn-record')?.classList.toggle('deck-btn--active', Boolean(liveOutputs.recording));
                    document.getElementById('deck-btn-vcam')?.classList.toggle('deck-btn--active', Boolean(liveOutputs.virtualCam));

                    const grid = document.getElementById('scenes-grid');
                    if (grid && Array.isArray(liveState.scenes)) {
                        grid.innerHTML = '';
                        if (liveState.scenes.length === 0) {
                            grid.innerHTML = '<p style="grid-column:1/-1;margin:0;color:var(--muted);font-size:0.85rem;">No scenes found in OBS Studio yet.</p>';
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
                    }

                    if (controlMode === 'screens') renderTilesBoard();
                };

                const refreshSnapshot = async () => {
                    try {
                        const res = await fetch(deckUrls.snapshot, { headers: { Accept: 'application/json' } });
                        if (!res.ok) return null;
                        const data = await res.json();
                        applyLiveState(data.status, data.is_online, data.live_state);
                        return data;
                    } catch {
                        return null;
                    }
                };

                const rollbackSceneSelection = (requestType, previousScene) => {
                    if (requestType !== 'SetCurrentProgramScene') return;
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

                    const httpPromise = postJson(deckUrls.cmd, {
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
                            if (!usedWhisper && Date.now() - lastEchoAt > 600) refreshSnapshot();
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

                // Helper: Active Collection & Layout Accessors
                const getActiveCollection = () => collectionsData.find((c) => Number(c.collection_id) === Number(activeCollectionId)) || collectionsData[0] || null;

                const upsertCollection = (payload) => {
                    if (!payload?.collection_id) return;
                    stampCollectionPerf(payload);
                    const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(payload.collection_id));
                    if (idx >= 0) collectionsData[idx] = payload;
                    else collectionsData.push(payload);
                    if (!activeCollectionId) activeCollectionId = payload.collection_id;
                };

                const fmtDeckClock = (secs) => {
                    const s = Math.max(0, Math.floor(secs));
                    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
                };

                const getCurrentTimerSeconds = (col) => {
                    const t = col?.runtime_state?.timer || {};
                    const mode = t.mode || 'countdown';
                    const status = t.status || 'stopped';
                    const duration = Math.max(1, Number(t.duration_seconds ?? 300));
                    const elapsedLocalMs = Math.max(0, performance.now() - Number(col?._receivedAtPerf ?? performance.now()));

                    if (mode === 'clock') {
                        const d = new Date();
                        const text = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
                        return { secs: 0, duration, mode, status: 'running', label: t.label || 'LOCAL TIME', text, pct: 100 };
                    }

                    if (mode === 'countup') {
                        let elapsedMs = Number.isFinite(Number(t.elapsed_ms))
                            ? Number(t.elapsed_ms)
                            : Number(t.elapsed_base_seconds || 0) * 1000;
                        if (status === 'running') {
                            elapsedMs += elapsedLocalMs;
                        }
                        const secs = Math.max(0, Math.floor(elapsedMs / 1000));
                        return { secs, duration, mode, status, label: t.label || 'ELAPSED', text: fmtDeckClock(secs), pct: 100 };
                    }

                    let remMs = Number.isFinite(Number(t.remaining_ms))
                        ? Number(t.remaining_ms)
                        : Number(t.remaining_seconds ?? t.duration_seconds ?? 0) * 1000;
                    if (status === 'running') {
                        remMs = Math.max(0, remMs - elapsedLocalMs);
                    }
                    const secs = Math.max(0, Math.ceil(remMs / 1000));
                    const pct = Math.max(0, Math.min(100, Math.round((remMs / (duration * 1000)) * 100)));
                    return { secs, duration, mode, status, label: t.label || 'LIVE IN', text: fmtDeckClock(secs), pct };
                };

                const subscribeDeckCollectionEcho = (col) => {
                    if (!window.Echo || !col?.public_token || col.public_token === subscribedCollectionToken) return;
                    if (subscribedCollectionChannel && subscribedCollectionToken) {
                        try { window.Echo.leave(`obs.collection.${subscribedCollectionToken}`); } catch {}
                    }
                    subscribedCollectionToken = col.public_token;
                    subscribedCollectionChannel = window.Echo.channel(`obs.collection.${col.public_token}`)
                        .listen('.obs.collection.updated', (payload) => {
                            upsertCollection(payload);
                            if (Number(payload.collection_id) === Number(activeCollectionId)) renderUnifiedDeck();
                        });
                };

                const persistDeckLayout = async (nextLayout, toastMsg = null) => {
                    const col = getActiveCollection();
                    if (!col) return;
                    try {
                        const res = await putJson(deckUrls.deckLayoutTpl.replace('__COL__', col.collection_id), nextLayout);
                        if (res.collection) {
                            upsertCollection(res.collection);
                            renderUnifiedDeck();
                            if (toastMsg) showToast(toastMsg);
                        }
                    } catch (err) {
                        showToast(`Save failed: ${err.message}`, true);
                    }
                };

                const sendDeckRuntime = async (patch, toastMsg = null) => {
                    const col = getActiveCollection();
                    if (!col) return;
                    try {
                        const res = await postJson(deckUrls.runtimeTpl.replace('__COL__', col.collection_id), patch);
                        if (res.collection) {
                            upsertCollection(res.collection);
                            renderUnifiedDeck();
                            if (toastMsg) showToast(toastMsg);
                        }
                    } catch (err) {
                        showToast(`Update failed: ${err.message}`, true);
                    }
                };

                const executeTileAction = async (tile) => {
                    const col = getActiveCollection();
                    if (!col || !tile) return;
                    const cfg = tile.config || {};

                    if (tile.type === 'switch_view') {
                        const viewId = Number(cfg.view_id);
                        const targetView = (col.views || []).find((v) => Number(v.id) === viewId);
                        if (!targetView) {
                            showToast('Target Screen View was removed — edit this tile', true);
                            return;
                        }
                        const globalTx = document.getElementById('deck-uni-tx-select')?.value || 'default';
                        const txType = globalTx !== 'default' ? globalTx : (cfg.transition_type || 'default');
                        try {
                            const res = await postJson(deckUrls.switchTpl.replace('__COL__', col.collection_id), {
                                view_id: targetView.id,
                                transition: txType === 'default' ? null : { type: txType },
                            });
                            if (res.collection) {
                                upsertCollection(res.collection);
                                renderUnifiedDeck();
                                showToast(`Live View: ${targetView.name}`);
                            }
                        } catch (err) {
                            showToast(`Switch failed: ${err.message}`, true);
                        }
                        return;
                    }

                    if (tile.type === 'timer') {
                        const act = cfg.action || 'toggle';
                        if (act === 'toggle') {
                            const isRunning = col.runtime_state?.timer?.status === 'running';
                            await sendDeckRuntime({ timer: { action: isRunning ? 'pause' : 'start' } }, isRunning ? 'Timer paused' : 'Timer started');
                        } else if (act === 'add_seconds') {
                            const secs = Number(cfg.seconds ?? 60);
                            await sendDeckRuntime({ timer: { action: 'add_seconds', seconds: secs } }, `${secs >= 0 ? '+' : ''}${secs}s`);
                        } else if (act === 'set') {
                            const secs = Math.max(0, Number(cfg.duration_seconds ?? 300));
                            await sendDeckRuntime({ timer: { action: 'set', duration_seconds: secs, auto_start: Boolean(cfg.auto_start) } }, `Timer set to ${fmtDeckClock(secs)}`);
                        } else {
                            await sendDeckRuntime({ timer: { action: act } }, `Timer: ${act}`);
                        }
                        return;
                    }

                    if (tile.type === 'scoreboard') {
                        const act = cfg.action || 'delta_a';
                        if (act === 'delta_a') await sendDeckRuntime({ scoreboard: { delta_a: Number(cfg.delta ?? 1) } });
                        else if (act === 'delta_b') await sendDeckRuntime({ scoreboard: { delta_b: Number(cfg.delta ?? 1) } });
                        else if (act === 'swap') await sendDeckRuntime({ scoreboard: { swap: true } }, 'Teams swapped');
                        else if (act === 'reset_scores') await sendDeckRuntime({ scoreboard: { reset_scores: true } }, 'Scores reset 0 - 0');
                        return;
                    }

                    if (tile.type === 'lower_third') {
                        const act = cfg.action || 'toggle';
                        const isVis = Boolean(col.runtime_state?.lower_third?.visible);
                        if (act === 'hide') {
                            await sendDeckRuntime({ lower_third: { visible: false } }, 'Lower Third hidden');
                        } else if (act === 'show') {
                            const payload = { visible: true };
                            if (cfg.title) payload.title = cfg.title;
                            if (cfg.subtitle !== undefined && cfg.subtitle !== '') payload.subtitle = cfg.subtitle;
                            await sendDeckRuntime({ lower_third: payload }, `Lower Third: ${cfg.title || 'Shown'}`);
                        } else {
                            await sendDeckRuntime({ lower_third: { visible: !isVis } }, !isVis ? 'Lower Third On Air' : 'Lower Third hidden');
                        }
                        return;
                    }

                    if (tile.type === 'alert') {
                        const act = cfg.action || 'show';
                        if (act === 'hide') {
                            await sendDeckRuntime({ alert: { visible: false } }, 'Alert cleared');
                        } else {
                            await sendDeckRuntime({
                                alert: {
                                    visible: true,
                                    title: cfg.title || tile.label || 'Live Update',
                                    message: cfg.message || tile.subtitle || '',
                                },
                            }, 'Alert pushed live');
                        }
                        return;
                    }

                    if (tile.type === 'ticker') {
                        const isOn = col.runtime_state?.ticker?.visible !== false;
                        const nextVis = cfg.action === 'show' ? true : (cfg.action === 'hide' ? false : !isOn);
                        await sendDeckRuntime({ ticker: { visible: nextVis } }, nextVis ? 'Ticker On' : 'Ticker Off');
                        return;
                    }

                    if (tile.type === 'layer_toggle') {
                        if (!cfg.layer_id) return;
                        const hiddenSet = new Set(Array.isArray(col.runtime_state?.hidden_layers) ? col.runtime_state.hidden_layers : []);
                        const nextVis = hiddenSet.has(cfg.layer_id);
                        await sendDeckRuntime({ toggle_layer: { layer_id: cfg.layer_id, visible: nextVis } }, `${tile.label}: ${nextVis ? 'Shown' : 'Hidden'}`);
                        return;
                    }

                    if (tile.type === 'obs_action') {
                        const act = cfg.action || 'push_stage';
                        if (act === 'push_stage') {
                            await postJson(deckUrls.pushTpl.replace('__COL__', col.collection_id), {});
                            showToast('Pushing Single Browser Stage to OBS');
                        } else if (act === 'macro' && cfg.macro_id) {
                            await postJson(deckUrls.macroTpl.replace('__MACRO__', cfg.macro_id), {});
                            showToast(`Macro triggered: ${tile.label}`);
                            setTimeout(refreshSnapshot, 450);
                        } else if (act === 'obs_scene' && cfg.scene_name) {
                            await sendDeckCommand('SetCurrentProgramScene', { sceneName: cfg.scene_name }, `OBS Scene: ${cfg.scene_name}`);
                        } else {
                            await sendDeckCommand(act, {}, `OBS: ${tile.label}`);
                        }
                    }
                };

                const buildMiniViewThumb = (col, view, isActive) => {
                    const thumb = document.createElement('div');
                    thumb.className = `tile-thumb${view?.background_mode === 'transparent' ? ' is-transparent' : ''}`;
                    if (view?.background_mode === 'solid' && view.background_color) {
                        thumb.style.background = view.background_color;
                    } else if (view?.background_mode === 'theme' && col.theme?.background_color) {
                        thumb.style.background = col.theme.background_color;
                    }

                    const stageW = Math.max(320, Number(col.width || 1920));
                    const stageH = Math.max(180, Number(col.height || 1080));
                    const layers = Array.isArray(view?.layers) ? view.layers : [];
                    layers
                        .filter((l) => l && l.visible !== false)
                        .slice()
                        .sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0))
                        .forEach((layer) => {
                            const box = document.createElement('span');
                            const lType = String(layer.type || 'shape');
                            const cls = lType === 'camera_cutout'
                                ? 'mini-layer--cutout'
                                : (['timer', 'scoreboard', 'lower_third', 'ticker'].includes(lType) ? 'mini-layer--accent' : 'mini-layer--block');
                            box.className = `mini-layer ${cls}`;
                            const leftPct = Math.max(0, Math.min(98, (Number(layer.x || 0) / stageW) * 100));
                            const topPct = Math.max(0, Math.min(98, (Number(layer.y || 0) / stageH) * 100));
                            const wPct = Math.max(4, Math.min(100 - leftPct, (Number(layer.w || 240) / stageW) * 100));
                            const hPct = Math.max(4, Math.min(100 - topPct, (Number(layer.h || 120) / stageH) * 100));
                            box.style.left = `${leftPct}%`;
                            box.style.top = `${topPct}%`;
                            box.style.width = `${wPct}%`;
                            box.style.height = `${hPct}%`;
                            thumb.appendChild(box);
                        });

                    if (isActive) {
                        const pill = document.createElement('span');
                        pill.className = 'tile-status-pill tile-status-pill--thumb';
                        pill.innerHTML = '<span class="tile-status-dot" aria-hidden="true"></span>ON AIR';
                        thumb.appendChild(pill);
                    }
                    return thumb;
                };

                const renderTilesBoard = () => {
                    const col = getActiveCollection();
                    const grid = document.getElementById('deck-tiles-grid');
                    if (!grid || !col) return;

                    const layout = col.deck_layout || { columns: 0, tiles: [] };
                    const tiles = Array.isArray(layout.tiles) ? layout.tiles : [];
                    const fixedCols = Number(layout.columns || 0);

                    if (fixedCols >= 2 && fixedCols <= 5) grid.setAttribute('data-fixed-cols', String(fixedCols));
                    else grid.removeAttribute('data-fixed-cols');

                    const colSel = document.getElementById('deck-columns-select');
                    if (colSel) colSel.value = String(fixedCols);

                    grid.innerHTML = '';
                    grid.classList.toggle('is-editing', isEditMode);

                    const timerInfo = getCurrentTimerSeconds(col);
                    const sb = col.runtime_state?.scoreboard || {};
                    const l3 = col.runtime_state?.lower_third || {};
                    const tickerOn = col.runtime_state?.ticker?.visible !== false;
                    const alertOn = Boolean(col.runtime_state?.alert?.visible);
                    const hiddenLayers = new Set(Array.isArray(col.runtime_state?.hidden_layers) ? col.runtime_state.hidden_layers : []);

                    tiles.forEach((tile, idx) => {
                        const cfg = tile.config || {};
                        const isRichTimerWidget = tile.type === 'timer' && ['toggle', 'console'].includes(cfg.action || 'toggle');
                        const card = document.createElement((isEditMode || isRichTimerWidget) ? 'div' : 'button');
                        if (!isEditMode && !isRichTimerWidget) card.type = 'button';

                        let isOnAir = false;
                        let isLiveDanger = false;
                        let topBadge = '';
                        let liveStatusPill = null;
                        let metricText = null;
                        let autoSub = '';
                        let viewObj = null;

                        if (tile.type === 'switch_view') {
                            viewObj = (col.views || []).find((v) => Number(v.id) === Number(cfg.view_id)) || null;
                            isOnAir = Boolean(viewObj && Number(viewObj.id) === Number(col.active_view_id));
                            const txKey = (cfg.transition_type && cfg.transition_type !== 'default')
                                ? cfg.transition_type
                                : (viewObj?.transition_override?.type || col.default_transition?.type || 'fade');
                            autoSub = viewObj
                                ? `${(viewObj.layers || []).length} layers · ${String(txKey).replaceAll('_', ' ')}`
                                : 'Missing view — edit tile';
                        } else if (tile.type === 'timer') {
                            topBadge = timerInfo.mode === 'countup' ? 'Stopwatch' : (timerInfo.mode === 'clock' ? 'Clock' : 'Timer');
                            if (isRichTimerWidget) {
                                metricText = timerInfo.text;
                                isOnAir = timerInfo.status === 'running';
                                liveStatusPill = timerInfo.status === 'running' ? 'RUNNING' : (timerInfo.status === 'paused' ? 'PAUSED' : 'READY');
                                autoSub = timerInfo.label || 'Live stage timer';
                            } else {
                                autoSub = cfg.action === 'set' ? `Set ${fmtDeckClock(cfg.duration_seconds || 300)}` : (cfg.action === 'add_seconds' ? `${Number(cfg.seconds || 60) >= 0 ? '+' : ''}${cfg.seconds || 60}s` : cfg.action);
                            }
                        } else if (tile.type === 'scoreboard') {
                            topBadge = 'Scoreboard';
                            if (cfg.action === 'delta_a') {
                                metricText = String(sb.team_a_score ?? 0);
                                autoSub = `${sb.team_a_name || 'Team Alpha'} (${Number(cfg.delta ?? 1) >= 0 ? '+' : ''}${cfg.delta ?? 1})`;
                            } else if (cfg.action === 'delta_b') {
                                metricText = String(sb.team_b_score ?? 0);
                                autoSub = `${sb.team_b_name || 'Team Omega'} (${Number(cfg.delta ?? 1) >= 0 ? '+' : ''}${cfg.delta ?? 1})`;
                            } else {
                                autoSub = cfg.action === 'swap' ? 'Swap Team A & B' : 'Reset scores to 0 - 0';
                            }
                        } else if (tile.type === 'lower_third') {
                            topBadge = 'Lower Third';
                            isOnAir = Boolean(l3.visible && (cfg.action !== 'show' || !cfg.title || l3.title === cfg.title));
                            liveStatusPill = isOnAir ? 'ON AIR' : 'OFF';
                            autoSub = cfg.title ? `${cfg.title}${cfg.subtitle ? ' · ' + cfg.subtitle : ''}` : 'Toggle lower third banner';
                        } else if (tile.type === 'alert') {
                            topBadge = 'Alert';
                            isOnAir = cfg.action !== 'hide' && alertOn;
                            liveStatusPill = alertOn ? 'LIVE' : 'IDLE';
                            autoSub = cfg.action === 'hide' ? 'Dismiss live alert' : (cfg.message || 'Push alert banner');
                        } else if (tile.type === 'ticker') {
                            topBadge = 'Ticker';
                            isOnAir = tickerOn;
                            liveStatusPill = tickerOn ? 'ON' : 'OFF';
                            autoSub = 'Scrolling stage ticker';
                        } else if (tile.type === 'layer_toggle') {
                            topBadge = 'Layer';
                            isOnAir = Boolean(cfg.layer_id && !hiddenLayers.has(cfg.layer_id));
                            liveStatusPill = isOnAir ? 'SHOWN' : 'HIDDEN';
                            autoSub = 'Toggle single view layer';
                        } else if (tile.type === 'obs_action') {
                            topBadge = 'OBS';
                            const act = cfg.action || 'push_stage';
                            if (act === 'ToggleStream' && liveOutputs.streaming) { isLiveDanger = true; liveStatusPill = 'LIVE'; }
                            if (act === 'ToggleRecord' && liveOutputs.recording) { isLiveDanger = true; liveStatusPill = 'REC'; }
                            if (act === 'ToggleVirtualCam' && liveOutputs.virtualCam) { isOnAir = true; liveStatusPill = 'ON'; }
                            if (act === 'obs_scene' && cfg.scene_name === currentProgramScene) { isOnAir = true; liveStatusPill = 'PGM'; }
                            autoSub = act === 'push_stage' ? 'Sync single browser source' : act;
                        }

                        card.className = `deck-tile${(!isEditMode && !isRichTimerWidget) ? ' deck-tile--tap' : ''}${Number(tile.span) === 2 ? ' is-wide' : ''}${isOnAir ? ' is-on-air' : ''}${isLiveDanger ? ' is-live-danger' : ''}`;
                        card.dataset.tileColor = tile.color || 'neutral';
                        card.dataset.tileId = tile.id || String(idx);
                        card.dataset.tileType = tile.type;

                        if (tile.type === 'switch_view') {
                            card.appendChild(buildMiniViewThumb(col, viewObj, isOnAir));
                        } else {
                            const badgeRow = document.createElement('div');
                            badgeRow.className = 'tile-badge-row';
                            const chip = document.createElement('span');
                            chip.className = 'tile-type-chip';
                            chip.textContent = topBadge;
                            badgeRow.appendChild(chip);

                            if (liveStatusPill) {
                                const st = document.createElement('span');
                                st.className = 'tile-status-pill';
                                if (!isOnAir && !isLiveDanger) {
                                    st.style.borderColor = 'rgba(255,255,255,0.14)';
                                    st.style.color = 'var(--muted)';
                                } else if (isLiveDanger) {
                                    st.style.borderColor = 'rgba(229,72,77,0.6)';
                                    st.style.color = '#FCA5A5';
                                }
                                st.textContent = liveStatusPill;
                                badgeRow.appendChild(st);
                            }
                            card.appendChild(badgeRow);

                            if (metricText !== null) {
                                const m = document.createElement('div');
                                m.className = 'tile-metric-big';
                                if (tile.type === 'timer') m.setAttribute('data-live-tile-clock', '1');
                                m.textContent = metricText;
                                card.appendChild(m);

                                if (isRichTimerWidget) {
                                    const track = document.createElement('div');
                                    track.className = 'timer-progress-track';
                                    const fill = document.createElement('div');
                                    fill.className = 'timer-progress-fill';
                                    fill.setAttribute('data-live-tile-progress', '1');
                                    fill.style.inlineSize = `${timerInfo.pct}%`;
                                    track.appendChild(fill);
                                    card.appendChild(track);
                                }
                            }
                        }

                        if (!isRichTimerWidget || isEditMode) {
                            const footer = document.createElement('div');
                            footer.style.cssText = 'display:grid;gap:0.12rem;min-inline-size:0;';
                            const lbl = document.createElement('div');
                            lbl.className = 'tile-label';
                            lbl.textContent = tile.label || (viewObj?.name || 'Untitled Tile');
                            const sub = document.createElement('div');
                            sub.className = 'tile-sub';
                            sub.textContent = tile.subtitle || autoSub;
                            footer.append(lbl, sub);
                            card.appendChild(footer);
                        } else {
                            const controls = document.createElement('div');
                            controls.className = 'tile-timer-actions';

                            const primaryRow = document.createElement('div');
                            primaryRow.className = 'tile-timer-row tile-timer-row--primary';

                            const toggleBtn = document.createElement('button');
                            toggleBtn.type = 'button';
                            toggleBtn.className = `tile-ctrl-btn ${timerInfo.status === 'running' ? 'tile-ctrl-btn--pause' : 'tile-ctrl-btn--play'}`;
                            toggleBtn.textContent = timerInfo.status === 'running' ? 'Pause' : 'Start';
                            toggleBtn.addEventListener('click', () => {
                                const running = col.runtime_state?.timer?.status === 'running';
                                sendDeckRuntime({ timer: { action: running ? 'pause' : 'start' } }, running ? 'Timer paused' : 'Timer started');
                            });

                            const resetBtn = document.createElement('button');
                            resetBtn.type = 'button';
                            resetBtn.className = 'tile-ctrl-btn';
                            resetBtn.textContent = 'Reset';
                            resetBtn.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'reset' } }, 'Timer reset'));

                            primaryRow.append(toggleBtn, resetBtn);

                            const nudgeRow = document.createElement('div');
                            nudgeRow.className = 'tile-timer-row tile-timer-row--nudge';

                            const minusBtn = document.createElement('button');
                            minusBtn.type = 'button';
                            minusBtn.className = 'tile-ctrl-btn';
                            minusBtn.textContent = '-1m';
                            minusBtn.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'add_seconds', seconds: -60 } }, '-1m'));

                            const plusBtn = document.createElement('button');
                            plusBtn.type = 'button';
                            plusBtn.className = 'tile-ctrl-btn';
                            plusBtn.textContent = '+1m';
                            plusBtn.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'add_seconds', seconds: 60 } }, '+1m'));

                            const moreBtn = document.createElement('button');
                            moreBtn.type = 'button';
                            moreBtn.className = 'tile-ctrl-btn';
                            moreBtn.textContent = 'Set';
                            moreBtn.title = 'Presets, custom time & timer mode';
                            moreBtn.addEventListener('click', () => openTimerModal());

                            nudgeRow.append(minusBtn, plusBtn, moreBtn);
                            controls.append(primaryRow, nudgeRow);
                            card.appendChild(controls);
                        }

                        if (isEditMode) {
                            card.draggable = true;
                            card.addEventListener('dragstart', () => { dragTileIndex = idx; card.classList.add('is-dragging'); });
                            card.addEventListener('dragend', () => { dragTileIndex = null; card.classList.remove('is-dragging'); });
                            card.addEventListener('dragover', (ev) => { ev.preventDefault(); });
                            card.addEventListener('drop', (ev) => {
                                ev.preventDefault();
                                if (dragTileIndex === null || dragTileIndex === idx) return;
                                const reordered = [...tiles];
                                const [moved] = reordered.splice(dragTileIndex, 1);
                                reordered.splice(idx, 0, moved);
                                persistDeckLayout({ columns: fixedCols, tiles: reordered }, 'Tile order saved');
                            });

                            const editBar = document.createElement('div');
                            editBar.className = 'tile-edit-controls';

                            const moveGroup = document.createElement('div');
                            moveGroup.style.cssText = 'display:inline-flex;gap:0.25rem;';

                            const leftBtn = document.createElement('button');
                            leftBtn.type = 'button';
                            leftBtn.className = 'tile-mini-btn';
                            leftBtn.textContent = '←';
                            leftBtn.title = 'Move earlier';
                            leftBtn.disabled = idx === 0;
                            leftBtn.addEventListener('click', () => {
                                if (idx === 0) return;
                                const next = [...tiles];
                                [next[idx - 1], next[idx]] = [next[idx], next[idx - 1]];
                                persistDeckLayout({ columns: fixedCols, tiles: next });
                            });

                            const rightBtn = document.createElement('button');
                            rightBtn.type = 'button';
                            rightBtn.className = 'tile-mini-btn';
                            rightBtn.textContent = '→';
                            rightBtn.title = 'Move later';
                            rightBtn.disabled = idx === tiles.length - 1;
                            rightBtn.addEventListener('click', () => {
                                if (idx >= tiles.length - 1) return;
                                const next = [...tiles];
                                [next[idx], next[idx + 1]] = [next[idx + 1], next[idx]];
                                persistDeckLayout({ columns: fixedCols, tiles: next });
                            });

                            const spanBtn = document.createElement('button');
                            spanBtn.type = 'button';
                            spanBtn.className = 'tile-mini-btn';
                            spanBtn.textContent = Number(tile.span) === 2 ? '2×' : '1×';
                            spanBtn.title = 'Toggle 1×1 / 2×1 width';
                            spanBtn.addEventListener('click', () => {
                                const next = tiles.map((t, i) => i === idx ? { ...t, span: Number(t.span) === 2 ? 1 : 2 } : t);
                                persistDeckLayout({ columns: fixedCols, tiles: next });
                            });

                            moveGroup.append(leftBtn, rightBtn, spanBtn);

                            const actGroup = document.createElement('div');
                            actGroup.style.cssText = 'display:inline-flex;gap:0.25rem;';

                            const editBtn = document.createElement('button');
                            editBtn.type = 'button';
                            editBtn.className = 'tile-mini-btn';
                            editBtn.textContent = 'Edit';
                            editBtn.addEventListener('click', () => openTileDialog(idx));

                            const delBtn = document.createElement('button');
                            delBtn.type = 'button';
                            delBtn.className = 'tile-mini-btn tile-mini-btn--danger';
                            delBtn.textContent = '×';
                            delBtn.title = 'Remove tile';
                            delBtn.addEventListener('click', () => {
                                const next = tiles.filter((_, i) => i !== idx);
                                persistDeckLayout({ columns: fixedCols, tiles: next }, 'Tile removed');
                            });

                            actGroup.append(editBtn, delBtn);
                            editBar.append(moveGroup, actGroup);
                            card.appendChild(editBar);
                        } else if (!isRichTimerWidget) {
                            card.addEventListener('click', () => executeTileAction(tile));
                        }

                        grid.appendChild(card);
                    });

                    if (isEditMode) {
                        const addGhost = document.createElement('button');
                        addGhost.type = 'button';
                        addGhost.className = 'tile-add-ghost';
                        addGhost.innerHTML = '<span style="font-size:1.35rem;line-height:1;">+</span><span>Add Tile</span>';
                        addGhost.addEventListener('click', () => openTileDialog(null));
                        grid.appendChild(addGhost);
                    }
                };

                const renderUnifiedDeck = () => {
                    const isScreens = controlMode === 'screens';
                    document.querySelectorAll('[data-deck-mode]').forEach((b) => {
                        b.classList.toggle('is-active', b.dataset.deckMode === controlMode);
                    });

                    const classicWrap = document.getElementById('deck-classic-obs-wrapper');
                    const uniSec = document.getElementById('deck-unified-screen-section');
                    if (classicWrap) classicWrap.hidden = isScreens;
                    if (uniSec) uniSec.hidden = !isScreens;
                    syncDeckOfflineBanner();

                    const colSel = document.getElementById('deck-uni-collection-select');
                    const col = getActiveCollection();
                    if (col) activeCollectionId = col.collection_id;

                    if (colSel) {
                        colSel.innerHTML = collectionsData.length === 0 ? '<option value="">No collections yet</option>' : '';
                        collectionsData.forEach((c) => {
                            const o = document.createElement('option');
                            o.value = String(c.collection_id);
                            o.textContent = c.name;
                            o.selected = Number(c.collection_id) === Number(activeCollectionId);
                            colSel.appendChild(o);
                        });
                    }

                    const builderLink = document.getElementById('deck-uni-builder-link');
                    if (builderLink) builderLink.href = col ? deckUrls.builderTpl.replace('__COL__', col.collection_id) : '#';

                    const emptyCard = document.getElementById('deck-no-collection-card');
                    const tilesGrid = document.getElementById('deck-tiles-grid');
                    const editToolbar = document.getElementById('deck-edit-toolbar');
                    const editBtn = document.getElementById('deck-edit-mode-btn');

                    if (!col) {
                        if (emptyCard) emptyCard.hidden = false;
                        if (tilesGrid) tilesGrid.hidden = true;
                        if (editToolbar) editToolbar.hidden = true;
                        if (editBtn) editBtn.hidden = true;
                        return;
                    }

                    if (emptyCard) emptyCard.hidden = true;
                    if (tilesGrid) tilesGrid.hidden = false;
                    if (editBtn) {
                        editBtn.hidden = false;
                        editBtn.textContent = isEditMode ? 'Editing...' : 'Edit Tiles';
                        editBtn.classList.toggle('is-active', isEditMode);
                        editBtn.classList.toggle('is-gold', !isEditMode);
                    }
                    if (editToolbar) editToolbar.hidden = !isEditMode;

                    subscribeDeckCollectionEcho(col);
                    renderTilesBoard();

                    // Keep quick drawer readouts in sync
                    const sb = col.runtime_state?.scoreboard || {};
                    const aName = document.getElementById('deck-uni-sb-a-name');
                    const bName = document.getElementById('deck-uni-sb-b-name');
                    const aScore = document.getElementById('deck-uni-sb-a-score');
                    const bScore = document.getElementById('deck-uni-sb-b-score');
                    if (aName) aName.textContent = sb.team_a_name || 'Team Alpha';
                    if (bName) bName.textContent = sb.team_b_name || 'Team Omega';
                    if (aScore) aScore.textContent = String(sb.team_a_score ?? 0);
                    if (bScore) bScore.textContent = String(sb.team_b_score ?? 0);

                    const l3 = col.runtime_state?.lower_third || {};
                    const l3Title = document.getElementById('deck-uni-l3-title');
                    const l3Sub = document.getElementById('deck-uni-l3-sub');
                    if (l3Title && document.activeElement !== l3Title) l3Title.value = l3.title || '';
                    if (l3Sub && document.activeElement !== l3Sub) l3Sub.value = l3.subtitle || '';

                    syncTimerModalUI();
                };

                // Full Broadcast Timer Controls Dialog
                const timerModal = document.getElementById('timer-controls-dialog');

                const syncTimerModalUI = () => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const info = getCurrentTimerSeconds(col);
                    const digitsEl = document.getElementById('timer-modal-digits');
                    const statusEl = document.getElementById('timer-modal-status');
                    const modeBadge = document.getElementById('timer-modal-mode-badge');
                    const progBar = document.getElementById('timer-modal-progress-bar');
                    const toggleBtn = document.getElementById('tm-toggle-btn');
                    const ruleHint = document.getElementById('timer-modal-rule-hint');
                    const labelInput = document.getElementById('tm-label-input');

                    if (digitsEl) digitsEl.textContent = info.text;
                    if (progBar) progBar.style.inlineSize = `${info.pct}%`;
                    if (modeBadge) {
                        modeBadge.textContent = info.mode === 'countup' ? 'Stopwatch' : (info.mode === 'clock' ? 'Live Clock' : 'Countdown');
                    }
                    if (statusEl) {
                        const isRun = info.status === 'running';
                        statusEl.textContent = isRun ? 'Running' : (info.status === 'paused' ? 'Paused' : 'Stopped');
                        statusEl.style.borderColor = isRun ? 'rgba(76,195,138,0.55)' : 'rgba(255,255,255,0.14)';
                        statusEl.style.color = isRun ? '#9EE6C3' : (info.status === 'paused' ? 'var(--gold)' : 'var(--muted)');
                    }
                    if (toggleBtn) {
                        const isRun = info.status === 'running';
                        toggleBtn.textContent = isRun ? 'Pause Timer' : 'Start Timer';
                        toggleBtn.classList.toggle('is-active', isRun);
                        toggleBtn.classList.toggle('is-gold', !isRun);
                    }

                    document.querySelectorAll('[data-tm-mode]').forEach((b) => {
                        b.classList.toggle('is-active', b.dataset.tmMode === info.mode);
                    });

                    if (labelInput && document.activeElement !== labelInput) {
                        labelInput.value = col.runtime_state?.timer?.label || '';
                    }

                    if (ruleHint) {
                        const activeView = (col.views || []).find((v) => Number(v.id) === Number(col.active_view_id));
                        const rule = activeView?.logic_rules || {};
                        if (rule.on_timer_end_action === 'switch_view' && rule.on_timer_end_target_view_id) {
                            const target = (col.views || []).find((v) => Number(v.id) === Number(rule.on_timer_end_target_view_id));
                            ruleHint.textContent = target ? `Auto-rule: switches to "${target.name}" at 00:00` : 'Auto-rule: switches view at 00:00';
                        } else if (rule.on_timer_end_action === 'show_alert') {
                            ruleHint.textContent = 'Auto-rule: fires alert banner at 00:00';
                        } else {
                            ruleHint.textContent = '';
                        }
                    }
                };

                const openTimerModal = () => {
                    const col = getActiveCollection();
                    if (!col || !timerModal) return;
                    const dur = Math.max(0, Number(col.runtime_state?.timer?.duration_seconds ?? 300));
                    const minInput = document.getElementById('tm-custom-min');
                    const secInput = document.getElementById('tm-custom-sec');
                    if (minInput) minInput.value = String(Math.floor(dur / 60));
                    if (secInput) secInput.value = String(dur % 60);
                    syncTimerModalUI();
                    timerModal.showModal();
                };

                // Tile Configurator Dialog Logic
                const tileDialog = document.getElementById('tile-editor-dialog');
                const tileTypeSelect = document.getElementById('tile-field-type');
                const tileDynamicHost = document.getElementById('tile-dynamic-config');
                const tileLabelInput = document.getElementById('tile-field-label');
                const tileSubInput = document.getElementById('tile-field-subtitle');
                const tileSpanSelect = document.getElementById('tile-field-span');
                const tileDeleteBtn = document.getElementById('tile-dialog-delete');

                const renderTileConfigFields = (type, existingCfg = {}, autoFillLabel = false) => {
                    const col = getActiveCollection();
                    const views = Array.isArray(col?.views) ? col.views : [];
                    tileDynamicHost.innerHTML = '';

                    if (type === 'switch_view') {
                        const viewWrap = document.createElement('label');
                        viewWrap.className = 'field-stack';
                        viewWrap.innerHTML = '<span class="field-label">Target App Screen View</span>';
                        const sel = document.createElement('select');
                        sel.id = 'cfg-view-id';
                        sel.className = 'deck-select';
                        views.forEach((v) => {
                            const o = document.createElement('option');
                            o.value = String(v.id);
                            o.textContent = v.name;
                            o.selected = Number(v.id) === Number(existingCfg.view_id);
                            sel.appendChild(o);
                        });
                        sel.addEventListener('change', () => {
                            const found = views.find((v) => Number(v.id) === Number(sel.value));
                            if (found) tileLabelInput.value = found.name;
                        });
                        if (autoFillLabel && views.length > 0) {
                            const picked = views.find((v) => Number(v.id) === Number(sel.value)) || views[0];
                            tileLabelInput.value = picked.name;
                        }
                        viewWrap.appendChild(sel);

                        const txWrap = document.createElement('label');
                        txWrap.className = 'field-stack';
                        txWrap.innerHTML = '<span class="field-label">Tile Transition Effect</span>';
                        const txSel = document.createElement('select');
                        txSel.id = 'cfg-tx-type';
                        txSel.className = 'deck-select';
                        const defOpt = document.createElement('option');
                        defOpt.value = 'default';
                        defOpt.textContent = 'View Default Transition';
                        txSel.appendChild(defOpt);
                        Object.entries(transitionOptions).forEach(([k, label]) => {
                            const o = document.createElement('option');
                            o.value = k;
                            o.textContent = label;
                            o.selected = k === (existingCfg.transition_type || 'default');
                            txSel.appendChild(o);
                        });
                        txWrap.appendChild(txSel);
                        tileDynamicHost.append(viewWrap, txWrap);
                        return;
                    }

                    if (type === 'timer') {
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Timer Action</span>
                                <select id="cfg-timer-action" class="deck-select">
                                    <option value="toggle">Full Timer Controls Pad (Play/Pause, Reset, ±1m, Set)</option>
                                    <option value="start">Start Timer Only</option>
                                    <option value="pause">Pause Timer</option>
                                    <option value="reset">Reset Timer</option>
                                    <option value="add_seconds">Add / Subtract Seconds</option>
                                    <option value="set">Set Preset Duration</option>
                                </select>
                            </label>
                            <label class="field-stack" id="cfg-timer-secs-wrap">
                                <span class="field-label">Seconds (e.g. 300 = 5m, -60 = -1m)</span>
                                <input type="number" id="cfg-timer-secs" class="deck-input" value="${existingCfg.duration_seconds ?? existingCfg.seconds ?? 300}">
                            </label>
                        `;
                        const actSel = document.getElementById('cfg-timer-action');
                        const secsWrap = document.getElementById('cfg-timer-secs-wrap');
                        actSel.value = existingCfg.action || 'toggle';
                        const syncSecs = () => { secsWrap.hidden = !['set', 'add_seconds'].includes(actSel.value); };
                        actSel.addEventListener('change', syncSecs);
                        syncSecs();
                        if (autoFillLabel) tileLabelInput.value = 'Countdown Timer';
                        return;
                    }

                    if (type === 'scoreboard') {
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Scoreboard Action</span>
                                <select id="cfg-sb-action" class="deck-select">
                                    <option value="delta_a">Team A +1 Point</option>
                                    <option value="delta_a_minus">Team A -1 Point</option>
                                    <option value="delta_b">Team B +1 Point</option>
                                    <option value="delta_b_minus">Team B -1 Point</option>
                                    <option value="swap">Swap Team Sides</option>
                                    <option value="reset_scores">Reset Scores (0 - 0)</option>
                                </select>
                            </label>
                        `;
                        const sel = document.getElementById('cfg-sb-action');
                        if (existingCfg.action === 'delta_a' && Number(existingCfg.delta) < 0) sel.value = 'delta_a_minus';
                        else if (existingCfg.action === 'delta_b' && Number(existingCfg.delta) < 0) sel.value = 'delta_b_minus';
                        else sel.value = existingCfg.action || 'delta_a';
                        if (autoFillLabel) tileLabelInput.value = 'Team A +1';
                        return;
                    }

                    if (type === 'lower_third') {
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Lower Third Action</span>
                                <select id="cfg-l3-action" class="deck-select">
                                    <option value="toggle">Toggle On / Off</option>
                                    <option value="show">Show Preset Speaker / Title</option>
                                    <option value="hide">Hide Lower Third</option>
                                </select>
                            </label>
                            <div id="cfg-l3-preset-wrap" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.45rem;">
                                <label class="field-stack">
                                    <span class="field-label">Preset Headline</span>
                                    <input type="text" id="cfg-l3-title" class="deck-input" value="${String(existingCfg.title || '').replaceAll('"', '&quot;')}" placeholder="Speaker name">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Preset Subtitle</span>
                                    <input type="text" id="cfg-l3-sub" class="deck-input" value="${String(existingCfg.subtitle || '').replaceAll('"', '&quot;')}" placeholder="Role / title">
                                </label>
                            </div>
                        `;
                        const sel = document.getElementById('cfg-l3-action');
                        const wrap = document.getElementById('cfg-l3-preset-wrap');
                        sel.value = existingCfg.action || 'toggle';
                        const sync = () => { wrap.hidden = sel.value !== 'show'; };
                        sel.addEventListener('change', sync);
                        sync();
                        if (autoFillLabel) tileLabelInput.value = 'Lower Third';
                        return;
                    }

                    if (type === 'alert') {
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Alert Action</span>
                                <select id="cfg-alert-action" class="deck-select">
                                    <option value="show">Push Preset Alert</option>
                                    <option value="hide">Clear Active Alert</option>
                                </select>
                            </label>
                            <div id="cfg-alert-fields" style="display:grid;gap:0.45rem;">
                                <input type="text" id="cfg-alert-title" class="deck-input" value="${String(existingCfg.title || '').replaceAll('"', '&quot;')}" placeholder="Alert headline">
                                <input type="text" id="cfg-alert-msg" class="deck-input" value="${String(existingCfg.message || '').replaceAll('"', '&quot;')}" placeholder="Alert message text">
                            </div>
                        `;
                        const sel = document.getElementById('cfg-alert-action');
                        const wrap = document.getElementById('cfg-alert-fields');
                        sel.value = existingCfg.action || 'show';
                        const sync = () => { wrap.hidden = sel.value === 'hide'; };
                        sel.addEventListener('change', sync);
                        sync();
                        if (autoFillLabel) tileLabelInput.value = 'Live Alert';
                        return;
                    }

                    if (type === 'ticker') {
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Ticker Action</span>
                                <select id="cfg-ticker-action" class="deck-select">
                                    <option value="toggle">Toggle Ticker On / Off</option>
                                    <option value="show">Show Ticker</option>
                                    <option value="hide">Hide Ticker</option>
                                </select>
                            </label>
                        `;
                        document.getElementById('cfg-ticker-action').value = existingCfg.action || 'toggle';
                        if (autoFillLabel) tileLabelInput.value = 'Bottom Ticker';
                        return;
                    }

                    if (type === 'layer_toggle') {
                        const allLayers = [];
                        views.forEach((v) => {
                            (v.layers || []).forEach((l) => {
                                allLayers.push({ id: l.id, label: `${v.name}: ${l.name || l.type}` });
                            });
                        });
                        const wrap = document.createElement('label');
                        wrap.className = 'field-stack';
                        wrap.innerHTML = '<span class="field-label">Target Layer</span>';
                        const sel = document.createElement('select');
                        sel.id = 'cfg-layer-id';
                        sel.className = 'deck-select';
                        allLayers.forEach((item) => {
                            const o = document.createElement('option');
                            o.value = item.id;
                            o.textContent = item.label;
                            o.selected = item.id === existingCfg.layer_id;
                            sel.appendChild(o);
                        });
                        wrap.appendChild(sel);
                        tileDynamicHost.appendChild(wrap);
                        if (autoFillLabel && allLayers[0]) tileLabelInput.value = allLayers[0].label.split(': ').pop();
                        return;
                    }

                    if (type === 'obs_action') {
                        const macroOpts = macrosList.map((m) => `<option value="${m.id}">${m.name}</option>`).join('');
                        const sceneOpts = obsScenes.map((s) => `<option value="${String(s.sceneName || '').replaceAll('"', '&quot;')}">${s.sceneName}</option>`).join('');
                        tileDynamicHost.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">OBS Action</span>
                                <select id="cfg-obs-action" class="deck-select">
                                    <option value="push_stage">Push Single Stage to OBS</option>
                                    <option value="ToggleStream">Toggle Stream</option>
                                    <option value="ToggleRecord">Toggle Record</option>
                                    <option value="ToggleRecordPause">Pause / Resume Record</option>
                                    <option value="SaveReplayBuffer">Save Replay Buffer</option>
                                    <option value="ToggleVirtualCam">Toggle Virtual Cam</option>
                                    <option value="TriggerStudioModeTransition">Studio Mode Transition</option>
                                    ${macrosList.length ? '<option value="macro">Run OBS Macro</option>' : ''}
                                    ${obsScenes.length ? '<option value="obs_scene">Switch Legacy OBS Scene</option>' : ''}
                                </select>
                            </label>
                            <label class="field-stack" id="cfg-obs-macro-wrap" hidden>
                                <span class="field-label">Select Macro</span>
                                <select id="cfg-obs-macro" class="deck-select">${macroOpts}</select>
                            </label>
                            <label class="field-stack" id="cfg-obs-scene-wrap" hidden>
                                <span class="field-label">Select OBS Scene</span>
                                <select id="cfg-obs-scene" class="deck-select">${sceneOpts}</select>
                            </label>
                        `;
                        const actSel = document.getElementById('cfg-obs-action');
                        const mWrap = document.getElementById('cfg-obs-macro-wrap');
                        const sWrap = document.getElementById('cfg-obs-scene-wrap');
                        actSel.value = existingCfg.action || 'push_stage';
                        if (existingCfg.macro_id && document.getElementById('cfg-obs-macro')) document.getElementById('cfg-obs-macro').value = String(existingCfg.macro_id);
                        if (existingCfg.scene_name && document.getElementById('cfg-obs-scene')) document.getElementById('cfg-obs-scene').value = existingCfg.scene_name;
                        const sync = () => {
                            mWrap.hidden = actSel.value !== 'macro';
                            sWrap.hidden = actSel.value !== 'obs_scene';
                        };
                        actSel.addEventListener('change', sync);
                        sync();
                        if (autoFillLabel) tileLabelInput.value = 'Push to OBS';
                    }
                };

                const setSelectedSwatch = (color) => {
                    selectedSwatch = color || 'neutral';
                    document.querySelectorAll('#tile-color-swatches .swatch-btn').forEach((btn) => {
                        btn.classList.toggle('is-selected', btn.dataset.swatch === selectedSwatch);
                    });
                };

                const openTileDialog = (tileIdx = null) => {
                    const col = getActiveCollection();
                    if (!col || !tileDialog) return;
                    const tiles = col.deck_layout?.tiles || [];
                    editingTileIndex = tileIdx;
                    const existing = tileIdx !== null ? tiles[tileIdx] : null;

                    document.getElementById('tile-editor-heading').textContent = existing ? 'Edit Tile' : 'Add Configurable Tile';
                    tileTypeSelect.value = existing?.type || 'switch_view';
                    tileLabelInput.value = existing?.label || '';
                    tileSubInput.value = existing?.subtitle || '';
                    tileSpanSelect.value = String(existing?.span || 1);
                    setSelectedSwatch(existing?.color || 'neutral');
                    tileDeleteBtn.hidden = existing === null;

                    renderTileConfigFields(tileTypeSelect.value, existing?.config || {}, existing === null);
                    tileDialog.showModal();
                };

                tileTypeSelect?.addEventListener('change', () => {
                    renderTileConfigFields(tileTypeSelect.value, {}, editingTileIndex === null);
                });

                document.querySelectorAll('#tile-color-swatches .swatch-btn').forEach((btn) => {
                    btn.addEventListener('click', () => setSelectedSwatch(btn.dataset.swatch));
                });

                document.getElementById('tile-dialog-close')?.addEventListener('click', () => tileDialog?.close());
                document.getElementById('tile-dialog-cancel')?.addEventListener('click', () => tileDialog?.close());
                tileDeleteBtn?.addEventListener('click', async () => {
                    const col = getActiveCollection();
                    if (!col || editingTileIndex === null) return;
                    const layout = col.deck_layout || { columns: 0, tiles: [] };
                    const nextTiles = (layout.tiles || []).filter((_, i) => i !== editingTileIndex);
                    tileDialog?.close();
                    await persistDeckLayout({ columns: layout.columns || 0, tiles: nextTiles }, 'Tile removed');
                });

                document.getElementById('tile-editor-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const col = getActiveCollection();
                    if (!col) return;
                    const type = tileTypeSelect.value;
                    const config = {};

                    if (type === 'switch_view') {
                        config.view_id = Number(document.getElementById('cfg-view-id')?.value || 0);
                        config.transition_type = document.getElementById('cfg-tx-type')?.value || 'default';
                    } else if (type === 'timer') {
                        const act = document.getElementById('cfg-timer-action')?.value || 'toggle';
                        config.action = act;
                        const secs = Number(document.getElementById('cfg-timer-secs')?.value || 300);
                        if (act === 'set') config.duration_seconds = Math.max(0, secs);
                        if (act === 'add_seconds') config.seconds = secs;
                    } else if (type === 'scoreboard') {
                        const raw = document.getElementById('cfg-sb-action')?.value || 'delta_a';
                        if (raw === 'delta_a') { config.action = 'delta_a'; config.delta = 1; }
                        else if (raw === 'delta_a_minus') { config.action = 'delta_a'; config.delta = -1; }
                        else if (raw === 'delta_b') { config.action = 'delta_b'; config.delta = 1; }
                        else if (raw === 'delta_b_minus') { config.action = 'delta_b'; config.delta = -1; }
                        else { config.action = raw; }
                    } else if (type === 'lower_third') {
                        config.action = document.getElementById('cfg-l3-action')?.value || 'toggle';
                        if (config.action === 'show') {
                            config.title = document.getElementById('cfg-l3-title')?.value?.trim() || '';
                            config.subtitle = document.getElementById('cfg-l3-sub')?.value?.trim() || '';
                        }
                    } else if (type === 'alert') {
                        config.action = document.getElementById('cfg-alert-action')?.value || 'show';
                        if (config.action === 'show') {
                            config.title = document.getElementById('cfg-alert-title')?.value?.trim() || 'Live Update';
                            config.message = document.getElementById('cfg-alert-msg')?.value?.trim() || '';
                        }
                    } else if (type === 'ticker') {
                        config.action = document.getElementById('cfg-ticker-action')?.value || 'toggle';
                    } else if (type === 'layer_toggle') {
                        config.layer_id = document.getElementById('cfg-layer-id')?.value || '';
                    } else if (type === 'obs_action') {
                        const act = document.getElementById('cfg-obs-action')?.value || 'push_stage';
                        config.action = act;
                        if (act === 'macro') config.macro_id = Number(document.getElementById('cfg-obs-macro')?.value || 0);
                        if (act === 'obs_scene') config.scene_name = document.getElementById('cfg-obs-scene')?.value || '';
                    }

                    const layout = col.deck_layout || { columns: 0, tiles: [] };
                    const existingTiles = Array.isArray(layout.tiles) ? [...layout.tiles] : [];
                    const tileObj = {
                        id: editingTileIndex !== null && existingTiles[editingTileIndex]?.id
                            ? existingTiles[editingTileIndex].id
                            : `tile_${Date.now().toString(36)}`,
                        type,
                        label: tileLabelInput.value.trim() || 'Tile',
                        subtitle: tileSubInput.value.trim(),
                        color: selectedSwatch,
                        span: Number(tileSpanSelect.value) === 2 ? 2 : 1,
                        config,
                    };

                    if (editingTileIndex !== null) existingTiles[editingTileIndex] = tileObj;
                    else existingTiles.push(tileObj);

                    tileDialog?.close();
                    await persistDeckLayout({ columns: layout.columns || 0, tiles: existingTiles }, editingTileIndex !== null ? 'Tile updated' : 'Tile added');
                });

                // Deck Header & Edit Mode Listeners
                document.querySelectorAll('[data-deck-mode]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const nextMode = btn.dataset.deckMode;
                        controlMode = nextMode;
                        renderUnifiedDeck();
                        try {
                            const res = await postJson(deckUrls.controlMode, { control_mode: nextMode });
                            if (res.collection) {
                                upsertCollection(res.collection);
                                activeCollectionId = res.collection.collection_id;
                            }
                            renderUnifiedDeck();
                        } catch {}
                    });
                });

                document.getElementById('deck-uni-collection-select')?.addEventListener('change', async (ev) => {
                    if (!ev.target.value) return;
                    activeCollectionId = Number(ev.target.value);
                    renderUnifiedDeck();
                    try {
                        await postJson(deckUrls.controlMode, { active_screen_collection_id: activeCollectionId });
                    } catch {}
                });

                document.getElementById('deck-create-first-collection-btn')?.addEventListener('click', async () => {
                    try {
                        const res = await postJson(deckUrls.collectionsStore, {
                            name: `${@json($studio->name)} Stage`,
                            with_starter_views: true,
                            activate: true,
                        });
                        if (res.collection) {
                            upsertCollection(res.collection);
                            activeCollectionId = res.collection.collection_id;
                            renderUnifiedDeck();
                            showToast('Starter Screen Collection created');
                        }
                    } catch (err) {
                        showToast(`Failed: ${err.message}`, true);
                    }
                });

                document.getElementById('deck-edit-mode-btn')?.addEventListener('click', () => {
                    isEditMode = !isEditMode;
                    renderUnifiedDeck();
                });

                document.getElementById('deck-done-edit-btn')?.addEventListener('click', () => {
                    isEditMode = false;
                    renderUnifiedDeck();
                });

                document.getElementById('deck-add-tile-btn')?.addEventListener('click', () => openTileDialog(null));

                document.getElementById('deck-sync-views-btn')?.addEventListener('click', async () => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const layout = col.deck_layout || { columns: 0, tiles: [] };
                    const existing = Array.isArray(layout.tiles) ? [...layout.tiles] : [];
                    const existingViewIds = new Set(
                        existing
                            .filter((t) => t.type === 'switch_view')
                            .map((t) => Number(t.config?.view_id || 0))
                    );
                    let added = 0;
                    (col.views || []).forEach((v) => {
                        if (!existingViewIds.has(Number(v.id))) {
                            existing.unshift({
                                id: `tile_view_${v.id}_${Date.now().toString(36)}`,
                                type: 'switch_view',
                                label: v.name,
                                subtitle: '',
                                color: 'neutral',
                                span: 1,
                                config: { view_id: v.id, transition_type: 'default' },
                            });
                            added++;
                        }
                    });
                    if (added === 0) {
                        showToast('All App Screen Views are already on this deck');
                        return;
                    }
                    await persistDeckLayout({ columns: layout.columns || 0, tiles: existing }, `Added ${added} missing view tile${added === 1 ? '' : 's'}`);
                });

                document.getElementById('deck-columns-select')?.addEventListener('change', (ev) => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const layout = col.deck_layout || { columns: 0, tiles: [] };
                    persistDeckLayout({ columns: Number(ev.target.value || 0), tiles: layout.tiles || [] }, 'Grid columns saved');
                });

                document.getElementById('deck-reset-tiles-btn')?.addEventListener('click', async () => {
                    await persistDeckLayout({ reset: true }, 'Reset tiles to collection defaults');
                });

                document.getElementById('deck-quick-panel-btn')?.addEventListener('click', (ev) => {
                    const drawer = document.getElementById('deck-quick-drawer');
                    if (!drawer) return;
                    drawer.hidden = !drawer.hidden;
                    ev.currentTarget.classList.toggle('is-active', !drawer.hidden);
                });

                // Quick Drawer & Full Timer Controls Modal Listeners
                document.getElementById('deck-open-timer-btn')?.addEventListener('click', openTimerModal);
                document.getElementById('deck-uni-t-more')?.addEventListener('click', openTimerModal);
                document.getElementById('timer-modal-close')?.addEventListener('click', () => timerModal?.close());

                document.getElementById('tm-toggle-btn')?.addEventListener('click', () => {
                    const col = getActiveCollection();
                    const isRunning = col?.runtime_state?.timer?.status === 'running';
                    sendDeckRuntime({ timer: { action: isRunning ? 'pause' : 'start' } }, isRunning ? 'Timer paused' : 'Timer started');
                });
                document.getElementById('tm-reset-btn')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'reset' } }, 'Timer reset'));

                document.querySelectorAll('[data-tm-add]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const delta = Number(btn.dataset.tmAdd || 0);
                        sendDeckRuntime({ timer: { action: 'add_seconds', seconds: delta } }, `${delta >= 0 ? '+' : ''}${delta}s`);
                    });
                });

                document.querySelectorAll('[data-tm-preset]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const secs = Math.max(0, Number(btn.dataset.tmPreset || 300));
                        const autoStart = Boolean(document.getElementById('tm-preset-autostart')?.checked);
                        const minInput = document.getElementById('tm-custom-min');
                        const secInput = document.getElementById('tm-custom-sec');
                        if (minInput) minInput.value = String(Math.floor(secs / 60));
                        if (secInput) secInput.value = String(secs % 60);
                        sendDeckRuntime({
                            timer: { action: 'set', duration_seconds: secs, auto_start: autoStart },
                        }, autoStart ? `Started ${fmtDeckClock(secs)}` : `Set ${fmtDeckClock(secs)}`);
                    });
                });

                const applyCustomTimerDuration = (autoStart = false) => {
                    const mins = Math.max(0, Math.min(180, Number(document.getElementById('tm-custom-min')?.value || 0)));
                    const secs = Math.max(0, Math.min(59, Number(document.getElementById('tm-custom-sec')?.value || 0)));
                    const total = (mins * 60) + secs;
                    sendDeckRuntime({
                        timer: { action: 'set', duration_seconds: total, auto_start: autoStart },
                    }, autoStart ? `Started ${fmtDeckClock(total)}` : `Timer set to ${fmtDeckClock(total)}`);
                };
                document.getElementById('tm-set-btn')?.addEventListener('click', () => applyCustomTimerDuration(false));
                document.getElementById('tm-set-start-btn')?.addEventListener('click', () => applyCustomTimerDuration(true));

                document.querySelectorAll('[data-tm-mode]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const mode = btn.dataset.tmMode || 'countdown';
                        sendDeckRuntime({ timer: { action: 'update', mode } }, `Mode: ${mode}`);
                    });
                });

                document.getElementById('tm-label-save')?.addEventListener('click', () => {
                    const label = document.getElementById('tm-label-input')?.value?.trim() || '';
                    sendDeckRuntime({ timer: { action: 'update', label } }, 'Timer label updated');
                });

                document.querySelectorAll('[data-quick-timer-preset]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const secs = Math.max(0, Number(btn.dataset.quickTimerPreset || 300));
                        sendDeckRuntime({ timer: { action: 'set', duration_seconds: secs } }, `Timer set to ${fmtDeckClock(secs)}`);
                    });
                });

                document.getElementById('deck-uni-t-start')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'start' } }));
                document.getElementById('deck-uni-t-pause')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'pause' } }));
                document.getElementById('deck-uni-t-reset')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'reset' } }));
                document.getElementById('deck-uni-t-minus')?.addEventListener('click', () => sendDeckRuntime({ timer: { action: 'add_seconds', seconds: -60 } }));
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
                }, 'Lower Third On Air'));
                document.getElementById('deck-uni-l3-hide')?.addEventListener('click', () => sendDeckRuntime({ lower_third: { visible: false } }, 'Lower Third hidden'));
                document.getElementById('deck-uni-alert-show')?.addEventListener('click', () => sendDeckRuntime({
                    alert: {
                        visible: true,
                        title: 'Live Update',
                        message: document.getElementById('deck-uni-alert-msg')?.value?.trim() || '',
                    },
                }, 'Alert pushed'));
                document.getElementById('deck-uni-alert-hide')?.addEventListener('click', () => sendDeckRuntime({ alert: { visible: false } }, 'Alert cleared'));

                // Classic OBS Fallback Listeners
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
                            await postJson(deckUrls.macroTpl.replace('__MACRO__', macroId), {});
                            showToast(`Macro triggered: ${btn.textContent.trim()}`);
                            setTimeout(refreshSnapshot, 450);
                        } catch (err) {
                            showToast(`Macro failed: ${err.message}`, true);
                        }
                    });
                });

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
                    if (!screenId || !text) { showToast('Enter text before broadcasting', true); return; }
                    try {
                        await postJson(deckUrls.screenLiveTpl.replace('__SCREEN__', screenId), {
                            state: { guest_name: text, headline: text, show_lower_third: true },
                        });
                        showToast('Overlay updated via Reverb');
                    } catch (err) {
                        showToast(`Overlay update failed: ${err.message}`, true);
                    }
                });

                // Real-time Echo
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
                        const isWhisperPayload = payload && !('response_data' in payload);
                        if (
                            isWhisperPayload
                            && payload?.status === 'failed'
                            && studioOnline
                            && String(payload?.error_message || '').toLowerCase().includes('not currently connected')
                        ) {
                            return;
                        }
                        const resolver = pendingResolvers.get(payload?.command_uuid);
                        if (resolver) resolver(payload);
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

                // Live Clock Tick for Timer Tiles, Toolbar Pill, Quick Drawer & Timer Modal
                setInterval(() => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const info = getCurrentTimerSeconds(col);
                    const drawerClock = document.getElementById('deck-uni-timer-display');
                    if (drawerClock) drawerClock.textContent = info.text;
                    const modalClock = document.getElementById('timer-modal-digits');
                    if (modalClock) modalClock.textContent = info.text;
                    const modalProg = document.getElementById('timer-modal-progress-bar');
                    if (modalProg) modalProg.style.inlineSize = `${info.pct}%`;
                    document.querySelectorAll('[data-live-tile-clock]').forEach((el) => {
                        el.textContent = info.text;
                    });
                    document.querySelectorAll('[data-live-tile-progress]').forEach((el) => {
                        el.style.inlineSize = `${info.pct}%`;
                    });
                }, 250);

                renderUnifiedDeck();

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) refreshSnapshot();
                });

                setInterval(() => {
                    if (!document.hidden && Date.now() - lastEchoAt > 14000) refreshSnapshot();
                }, 15000);
            })();
        </script>
    </body>
</html>
