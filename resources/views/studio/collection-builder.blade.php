<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $collection->name }} · Screen Canvas Builder · Obsisstant</title>
        <x-app-favicon />
        <x-dev-hot-reload />
        @vite('resources/css/app.css')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=barlow-condensed:500,600,700,800|bebas-neue:400|fira-code:400,500,600,700|instrument-sans:400,500,600,700,800|inter:400,500,600,700,800|jetbrains-mono:400,500,600,700|montserrat:400,500,600,700,800|oswald:400,500,600,700|playfair-display:500,600,700,800|poppins:400,500,600,700,800|space-grotesk:400,500,600,700|syne:500,600,700,800&display=swap" rel="stylesheet">
        <style>
            :root {
                color-scheme: dark;
                --bg-canvas: #090908;
                --bg-chrome: #121210;
                --bg-card: #181714;
                --bg-raised: #201F1B;
                --gold-accent: #CDAD69;
                --gold-light: #F4E185;
                --positive: #4CC38A;
                --danger: #E5484D;
                --ink-primary: #FAF7EE;
                --ink-secondary: #C8C0AF;
                --ink-muted: #8E8777;
                --hairline: rgba(255, 255, 255, 0.085);
                --hairline-strong: rgba(205, 173, 105, 0.3);
            }

            *, *::before, *::after { box-sizing: border-box; }
            html, body {
                margin: 0;
                min-block-size: 100dvb;
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }

            .topbar {
                position: sticky;
                inset-block-start: 0;
                z-index: 30;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.625rem 1rem;
                background-color: var(--bg-chrome);
                border-block-end: 1px solid var(--hairline);
            }

            .bar-group {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .s-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                min-block-size: 2rem;
                padding: 0.25rem 0.7rem;
                border-radius: 0.5rem;
                border: 1px solid var(--hairline);
                background: var(--bg-raised);
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                text-decoration: none;
                white-space: nowrap;
                flex-shrink: 0;
                cursor: pointer;
            }

            .s-btn:hover { border-color: var(--hairline-strong); background: #282722; }
            .s-btn:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
            .live-content-box {
                display: grid;
                gap: 0.45rem;
                margin: 0;
                padding: 0.55rem;
                border-radius: 0.5rem;
                border: 1px solid rgba(205, 173, 105, 0.28);
                background: rgba(205, 173, 105, 0.06);
            }
            fieldset.live-content-box > legend {
                padding: 0 0.25rem;
                font-size: 0.7rem;
                font-weight: 700;
                color: var(--gold-light);
                letter-spacing: 0.02em;
            }
            .live-content-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.4rem;
                font-size: 0.72rem;
                font-weight: 600;
                color: var(--gold-light);
            }
            .s-btn-gold {
                background: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border-color: rgba(244, 225, 133, 0.45);
            }
            .s-btn-gold:hover { background: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%); color: #070706; }
            .s-btn-danger {
                border-color: rgba(229, 72, 77, 0.36);
                background: rgba(229, 72, 77, 0.12);
                color: #F4A3A6;
            }
            .s-btn-xs { min-block-size: 1.7rem; padding: 0.125rem 0.5rem; font-size: 0.6875rem; }
            .s-btn.is-active { border-color: var(--gold-accent); background: rgba(205, 173, 105, 0.14); color: var(--gold-light); }

            .s-input, .s-select, .s-textarea {
                background: #100F0D;
                color: var(--ink-primary);
                border: 1px solid var(--hairline);
                border-radius: 0.45rem;
                padding: 0.35rem 0.55rem;
                font: inherit;
                font-size: 0.75rem;
                inline-size: 100%;
                min-block-size: 2rem;
            }

            .s-input:focus, .s-select:focus, .s-textarea:focus {
                outline: 2px solid var(--gold-accent);
                outline-offset: 1px;
            }

            .pill-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.18rem 0.55rem;
                border-radius: 9999px;
                background: var(--bg-raised);
                border: 1px solid var(--hairline);
                font-size: 0.6875rem;
                color: var(--ink-secondary);
            }

            .pill-badge.is-dirty {
                border-color: rgba(205, 173, 105, 0.45);
                color: var(--gold-light);
            }

            .builder-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                padding: 0.875rem;
                align-items: start;
            }

            @media (min-width: 72rem) {
                .builder-grid {
                    grid-template-columns: 19rem minmax(0, 1fr) 21.5rem;
                }
            }

            .panel {
                background: var(--bg-card);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.75rem;
                position: sticky;
                top: 4rem;
            }

            .panel-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-block-end: 0.5rem;
                border-block-end: 1px solid var(--hairline);
            }

            .panel-title {
                margin: 0;
                font-size: 0.8125rem;
                font-weight: 600;
            }

            .sub-label {
                font-size: 0.6875rem;
                color: var(--ink-muted);
                font-weight: 500;
            }

            .stack { display: grid; gap: 0.5rem; }

            .tab-row {
                display: grid;
                gap: 0.25rem;
                padding: 0.2rem;
                border-radius: 0.5rem;
                background: var(--bg-canvas);
                border: 1px solid var(--hairline);
            }

            .tab-row--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .tab-row--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }

            .tab-btn {
                min-block-size: 1.9rem;
                border: 0;
                border-radius: 0.375rem;
                background: transparent;
                color: var(--ink-muted);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
            }

            .tab-btn[aria-selected="true"] {
                background: var(--bg-raised);
                color: var(--ink-primary);
                box-shadow: inset 0 -2px 0 var(--gold-accent);
            }

            .list-scroll {
                display: grid;
                gap: 0.4rem;
                max-block-size: 32rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
            }

            .item-row {
                display: grid;
                gap: 0.35rem;
                padding: 0.5rem;
                border-radius: 0.5rem;
                background: var(--bg-raised);
                border: 1px solid transparent;
            }

            .item-row.is-selected {
                border-color: var(--gold-accent);
                background: rgba(205, 173, 105, 0.12);
            }

            .row-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.4rem;
                min-inline-size: 0;
            }

            .row-btn {
                background: transparent;
                border: 0;
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.78rem;
                font-weight: 500;
                text-align: start;
                cursor: pointer;
                padding: 0;
                min-inline-size: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                flex: 1;
            }

            .asset-thumb {
                inline-size: 2.5rem;
                block-size: 1.75rem;
                border-radius: 0.3rem;
                object-fit: cover;
                background: #0B0A08;
                border: 1px solid var(--hairline);
                flex-shrink: 0;
                display: grid;
                place-items: center;
                font-size: 0.625rem;
                color: var(--gold-light);
            }

            .element-palette {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
            }

            /* Editable Scaled 16:9 Canvas */
            .canvas-stage-wrap {
                position: relative;
                inline-size: 100%;
                aspect-ratio: 16 / 9;
                border-radius: 0.75rem;
                border: 1px solid var(--hairline-strong);
                overflow: hidden;
                background-color: #111110;
                background-image:
                    linear-gradient(45deg, #1b1a17 25%, transparent 25%),
                    linear-gradient(-45deg, #1b1a17 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #1b1a17 75%),
                    linear-gradient(-45deg, transparent 75%, #1b1a17 75%);
                background-size: 24px 24px;
                background-position: 0 0, 0 12px, 12px -12px, -12px 0;
                user-select: none;
                touch-action: none;
            }

            .canvas-logical {
                position: absolute;
                inset-inline-start: 0;
                inset-block-start: 0;
                transform-origin: 0 0;
                overflow: hidden;
            }

            .canvas-backdrop {
                position: absolute;
                inset: 0;
                z-index: 0;
            }

            .canvas-layer {
                position: absolute;
                border: 1.5px solid rgba(255, 255, 255, 0.22);
                cursor: grab;
                transition: border-color 110ms ease;
            }

            .canvas-layer:hover { border-color: var(--gold-light); }
            .canvas-layer.is-selected { border: 2px solid var(--gold-light); box-shadow: 0 0 0 2px rgba(244, 225, 133, 0.22); }
            .canvas-layer.is-locked { cursor: default; border-color: rgba(142, 135, 119, 0.5); }
            .canvas-layer.is-hidden { opacity: 0.28; border-style: dashed; }

            .canvas-tag {
                position: absolute;
                inset-inline-start: 6px;
                inset-block-start: -24px;
                padding: 2px 8px;
                border-radius: 5px;
                background: rgba(7, 7, 6, 0.9);
                border: 1px solid rgba(205, 173, 105, 0.35);
                color: var(--gold-light);
                font-size: 12px;
                font-weight: 600;
                pointer-events: none;
                white-space: nowrap;
                z-index: 5;
                opacity: 0;
                transition: opacity 110ms ease;
            }

            .canvas-layer:hover .canvas-tag,
            .canvas-layer.is-selected .canvas-tag {
                opacity: 1;
            }

            .resize-handle {
                position: absolute;
                inset-inline-end: -8px;
                inset-block-end: -8px;
                inline-size: 18px;
                block-size: 18px;
                border-radius: 4px;
                background: var(--gold-light);
                border: 2px solid #070706;
                cursor: nwse-resize;
                z-index: 6;
            }

            .canvas-layer.is-locked .resize-handle { display: none; }

            .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.4rem; }

            .color-field-stack {
                display: grid;
                gap: 0.3rem;
            }
            .color-field-row {
                display: grid;
                grid-template-columns: 2.25rem minmax(0, 1fr) auto;
                gap: 0.3rem;
                align-items: center;
            }
            .color-field-row--no-clear {
                grid-template-columns: 2.25rem minmax(0, 1fr);
            }
            .color-swatch-box {
                position: relative;
                inline-size: 2.25rem;
                block-size: 2rem;
                border-radius: 0.45rem;
                border: 1px solid var(--hairline-strong);
                overflow: hidden;
                background-image:
                    linear-gradient(45deg, rgba(255, 255, 255, 0.16) 25%, transparent 25%),
                    linear-gradient(-45deg, rgba(255, 255, 255, 0.16) 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, rgba(255, 255, 255, 0.16) 75%),
                    linear-gradient(-45deg, transparent 75%, rgba(255, 255, 255, 0.16) 75%);
                background-size: 10px 10px;
                background-position: 0 0, 0 5px, 5px -5px, -5px 0;
                background-color: #161512;
                forced-color-adjust: none;
            }
            .color-swatch-preview {
                position: absolute;
                inset: 0;
                pointer-events: none;
                forced-color-adjust: none;
            }
            .color-swatch-input {
                position: absolute;
                inset: 0;
                inline-size: 100%;
                block-size: 100%;
                opacity: 0;
                padding: 0;
                margin: 0;
                border: 0;
                cursor: pointer;
            }
            .color-swatch-box:has(.color-swatch-input:focus-visible) {
                outline: 2px solid var(--gold-light);
                outline-offset: 2px;
            }
            .color-alpha-row {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) 3.6rem auto;
                gap: 0.35rem;
                align-items: center;
            }
            .color-alpha-label {
                font-size: 0.65rem;
                color: var(--ink-muted);
                white-space: nowrap;
            }
            .color-alpha-pct {
                padding: 0.2rem 0.32rem !important;
                font-size: 0.72rem !important;
                text-align: end;
                font-variant-numeric: tabular-nums;
            }
            .alpha-slider {
                --slider-track-size: 0.5rem;
                --slider-thumb-size: 0.95rem;
                --alpha-hex: #CDAD69;
                --control-value: 100;
                appearance: none;
                -webkit-appearance: none;
                inline-size: 100%;
                block-size: 1.45rem;
                margin: 0;
                padding: 0;
                border: 0;
                background: transparent;
                cursor: pointer;
                forced-color-adjust: none;
            }
            .alpha-slider::-webkit-slider-runnable-track {
                block-size: var(--slider-track-size);
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.18);
                background:
                    linear-gradient(to right, transparent 0%, var(--alpha-hex) 100%),
                    linear-gradient(45deg, rgba(255, 255, 255, 0.16) 25%, transparent 25%) 0 0 / 8px 8px,
                    linear-gradient(-45deg, rgba(255, 255, 255, 0.16) 25%, transparent 25%) 0 4px / 8px 8px,
                    #161512;
            }
            .alpha-slider::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                inline-size: var(--slider-thumb-size);
                block-size: var(--slider-thumb-size);
                margin-block-start: calc((var(--slider-track-size) - var(--slider-thumb-size)) / 2 - 1px);
                border-radius: 50%;
                border: 2px solid #0B0A08;
                background: #FAF7EE;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.65);
            }
            .alpha-slider::-moz-range-track {
                block-size: var(--slider-track-size);
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.18);
                background:
                    linear-gradient(to right, transparent 0%, var(--alpha-hex) 100%),
                    #161512;
            }
            .alpha-slider::-moz-range-thumb {
                inline-size: var(--slider-thumb-size);
                block-size: var(--slider-thumb-size);
                border-radius: 50%;
                border: 2px solid #0B0A08;
                background: #FAF7EE;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.65);
            }
            .alpha-slider:focus-visible {
                outline: 2px solid var(--gold-light);
                outline-offset: 2px;
                border-radius: 0.25rem;
            }
            .toggle-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                font-size: 0.72rem;
                color: var(--ink-secondary);
                cursor: pointer;
            }
            .toggle-row input[type="checkbox"] {
                accent-color: var(--gold-accent);
                inline-size: 0.95rem;
                block-size: 0.95rem;
                cursor: pointer;
            }

            .empty-hint {
                padding: 1rem;
                border-radius: 0.55rem;
                border: 1px dashed var(--hairline);
                background: var(--bg-chrome);
                color: var(--ink-muted);
                font-size: 0.75rem;
                text-align: center;
            }

            .unused-tag {
                font-size: 0.625rem;
                font-weight: 500;
                padding: 0.1rem 0.35rem;
                border-radius: 999px;
                border: 1px solid rgba(205, 173, 105, 0.32);
                background: rgba(205, 173, 105, 0.1);
                color: var(--gold-light);
                flex-shrink: 0;
            }

            .obs-dialog {
                inline-size: min(26rem, calc(100vw - 2rem));
                padding: 0;
                border: 1px solid var(--hairline-strong);
                border-radius: 0.85rem;
                background: var(--bg-card);
                color: var(--ink-primary);
                box-shadow: 0 24px 64px rgba(0, 0, 0, 0.72);
            }
            .obs-dialog::backdrop {
                background: rgba(5, 5, 7, 0.72);
                backdrop-filter: blur(4px);
            }
            .obs-dialog-inner {
                padding: 1rem 1.15rem;
                display: grid;
                gap: 0.85rem;
            }
            .obs-dialog-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }
            .obs-dialog-title {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 600;
                color: var(--ink-primary);
            }
            .obs-dialog-copy {
                margin: 0;
                font-size: 0.8rem;
                line-height: 1.5;
                color: var(--ink-secondary);
            }
            .obs-dialog-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
            }

            .toast {
                position: fixed;
                inset-block-end: 1rem;
                inset-inline-end: 1rem;
                z-index: 60;
                padding: 0.6rem 0.95rem;
                border-radius: 0.6rem;
                border: 1px solid var(--hairline-strong);
                background: rgba(18, 18, 16, 0.96);
                color: var(--gold-light);
                font-size: 0.8rem;
                font-weight: 500;
            }
            [hidden] { display: none !important; }
        </style>
    </head>
    <body>
        <header class="topbar">
            <div class="bar-group">
                <a href="{{ route('studio.show', ['studio' => $studio->slug]) }}" class="s-btn">&larr; Studio</a>
                <strong style="font-size: 0.9375rem; color: var(--ink-primary);" id="collection-title-badge">{{ $collection->name }}</strong>
                <span class="pill-badge">{{ $collection->width }} &times; {{ $collection->height }}</span>
                <span id="builder-save-state" class="pill-badge">Saved</span>
            </div>

            <div class="bar-group">
                <a href="{{ $collection->getStageUrl() }}" target="_blank" class="s-btn">Preview Stage</a>
                <button type="button" id="builder-copy-stage-btn" class="s-btn">Copy URL</button>
                <button type="button" id="builder-push-obs-btn" class="s-btn">Push to OBS</button>
                <button type="button" id="builder-save-view-btn" class="s-btn is-active">Save View</button>
                <button type="button" id="builder-go-live-btn" class="s-btn s-btn-gold">Go Live</button>
            </div>
        </header>

        <main class="builder-grid">
            {{-- Left Column: Tabbed Views / Collection Assets / Theme & Transition --}}
            <aside class="panel" style="overscroll-behavior-y: scroll; max-height: 80vh">
                <div class="tab-row tab-row--3" role="tablist" aria-label="Collection sections">
                    <button type="button" class="tab-btn" role="tab" aria-selected="true" data-left-tab="views">Views</button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-left-tab="assets">Assets</button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-left-tab="theme">Theme</button>
                </div>

                {{-- Tab 1: Screen Views --}}
                <div id="left-tab-views" class="stack">
                    <div class="panel-head">
                        <h2 class="panel-title">Screen Views</h2>
                        <span class="sub-label" id="views-count-meta">0 views</span>
                    </div>
                    <div style="display: flex; gap: 0.35rem;">
                        <input type="text" id="new-view-name" class="s-input" placeholder="New view name" aria-label="New view name">
                        <button type="button" id="btn-add-view" class="s-btn">Add</button>
                    </div>
                    <div id="views-list" class="list-scroll"></div>
                </div>

                {{-- Tab 2: Collection Assets Library --}}
                <div id="left-tab-assets" class="stack" hidden>
                    <div class="panel-head">
                        <h2 class="panel-title">Collection Assets</h2>
                        <button type="button" id="btn-clean-unused-assets" class="s-btn s-btn-xs s-btn-danger" title="Delete unused files and orphaned uploads from server storage">Clean Unused</button>
                    </div>
                    <div class="stack">
                        <select id="asset-upload-type" class="s-select" aria-label="Asset type">
                            <option value="image">Image / Frame / Logo</option>
                            <option value="video">Background / Loop Video</option>
                            <option value="stinger">Transition Stinger (.webm/.mp4)</option>
                            <option value="audio">Alert Sound Effect (.mp3/.wav)</option>
                        </select>
                        <input type="file" id="asset-file-input" class="s-input" accept="image/*,video/mp4,video/webm,audio/*">
                    </div>
                    <div id="assets-list" class="list-scroll"></div>
                </div>

                {{-- Tab 3: Collection Theme & Default Transition --}}
                <div id="left-tab-theme" class="stack" hidden>
                    <div class="panel-head">
                        <h2 class="panel-title">Collection Theme &amp; Transition</h2>
                    </div>
                    <div class="stack">
                        <div class="stack">
                            <span class="sub-label">Accent Color</span>
                            <input type="text" id="col-accent-color" class="s-input" data-color-picker data-fallback="#CDAD69" aria-label="Accent Color">
                        </div>
                        <div class="stack">
                            <span class="sub-label">Surface Color</span>
                            <input type="text" id="col-surface-color" class="s-input" data-color-picker data-fallback="#0B0A08" aria-label="Surface Color">
                        </div>
                        <div class="stack">
                            <span class="sub-label">Text Color</span>
                            <input type="text" id="col-text-color" class="s-input" data-color-picker data-fallback="#FAF7EE" aria-label="Text Color">
                        </div>
                        <div class="stack">
                            <span class="sub-label">Sweep Color</span>
                            <input type="text" id="col-sweep-color" class="s-input" data-color-picker data-fallback="#CDAD69" aria-label="Sweep Color">
                        </div>
                        <label class="stack">
                            <span class="sub-label">Collection Font Family</span>
                            <select id="col-font-family" class="s-select">
                                @foreach ($fontFamilies as $fontKey => $fontLabel)
                                    <option value="{{ $fontKey }}">{{ $fontLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="stack" style="padding: 0.55rem; border-radius: 0.5rem; background: var(--bg-chrome); border: 1px solid var(--hairline);">
                        <label class="stack">
                            <span class="sub-label">Default Background</span>
                            <select id="col-bg-mode" class="s-select">
                                <option value="gradient">Theme Gradient</option>
                                <option value="solid">Solid Color</option>
                                <option value="transparent">Transparent (OBS Alpha)</option>
                                <option value="asset">Animated Video / Image Asset</option>
                            </select>
                        </label>

                        <div class="stack" id="col-bg-color-wrap" hidden>
                            <span class="sub-label">Default Background Color</span>
                            <input type="text" id="col-bg-color" class="s-input" data-color-picker data-fallback="#0B0A08" aria-label="Default Background Color">
                        </div>

                        <div class="stack" id="col-bg-asset-wrap" hidden>
                            <label class="stack">
                                <span class="sub-label">Default Bg Video / Image</span>
                                <select id="col-bg-asset" class="s-select">
                                    <option value="">Select uploaded video or image...</option>
                                </select>
                            </label>
                            <label class="stack">
                                <span class="sub-label">Upload Animated BG Video (.mp4/.webm) or Image</span>
                                <input type="file" id="col-bg-upload-input" class="s-input" accept="video/mp4,video/webm,image/*">
                            </label>
                        </div>

                        <div id="col-bg-override-hint" class="stack" style="padding: 0.45rem; border-radius: 0.4rem; background: rgba(205, 173, 105, 0.1); border: 1px solid rgba(205, 173, 105, 0.28);" hidden>
                            <span id="col-bg-override-text" class="sub-label" style="color: var(--gold-light);"></span>
                            <button type="button" id="btn-view-inherit-bg" class="s-btn s-btn-xs">Use Collection Default BG on this View</button>
                        </div>
                    </div>

                    <label class="stack">
                        <span class="sub-label">Default Transition</span>
                        <select id="col-default-tx" class="s-select">
                            @foreach ($transitionTypes as $txKey => $txLabel)
                                <option value="{{ $txKey }}">{{ $txLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid-2">
                        <label class="stack">
                            <span class="sub-label">Duration (ms, max 3000)</span>
                            <input type="number" id="col-tx-duration" class="s-input" min="0" max="3000" step="50">
                        </label>
                        <label class="stack">
                            <span class="sub-label">Swap Point (ms)</span>
                            <input type="number" id="col-tx-swap" class="s-input" min="0" max="3000" step="25">
                        </label>
                    </div>
                    <label class="stack">
                        <span class="sub-label">Default Stinger Video Asset</span>
                        <select id="col-tx-stinger" class="s-select">
                            <option value="">None</option>
                        </select>
                    </label>
                    <button type="button" id="btn-save-collection-settings" class="s-btn s-btn-gold">Save Collection Settings</button>
                </div>
            </aside>

            {{-- Center: Interactive Canvas Editor + Direct Element Palette --}}
            <section class="panel" style="border-color: var(--hairline-strong);">
                <div class="panel-head">
                    <div>
                        <h1 class="panel-title" style="font-size: 0.95rem;">
                            Canvas Editor · <span id="editing-view-title" style="color: var(--gold-light);">View</span>
                        </h1>
                        <span class="sub-label">Drag elements to arrange · Arrow keys nudge · Checkered cutouts reveal OBS cameras underneath</span>
                    </div>
                    <div class="bar-group">
                        <button type="button" id="btn-undo-canvas" class="s-btn s-btn-xs" title="Undo (Cmd/Ctrl + Z)" disabled>↶ Undo</button>
                        <button type="button" id="btn-redo-canvas" class="s-btn s-btn-xs" title="Redo (Cmd/Ctrl + Shift + Z)" disabled>↷ Redo</button>
                        <button type="button" id="btn-center-layer" class="s-btn s-btn-xs">Center</button>
                        <button type="button" id="btn-fit-layer" class="s-btn s-btn-xs">Fit 16:9</button>
                    </div>
                </div>

                <div class="element-palette" role="group" aria-label="Add element to canvas">
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="camera_cutout">+ Camera Cutout</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="text">+ Text</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="timer">+ Timer</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="color_box">+ Color Box</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="scoreboard">+ Scoreboard</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="lower_third">+ Lower Third</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="ticker">+ Ticker</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="shape">+ Glass Card</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="asset">+ Image / Video</button>
                    <button type="button" class="s-btn s-btn-xs" data-quick-add="embed">+ Web Embed</button>
                </div>

                <div id="canvas-stage-wrap" class="canvas-stage-wrap">
                    <div id="canvas-logical" class="canvas-logical"></div>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span class="sub-label" id="canvas-meta-readout">Canvas: 1920 × 1080 px</span>
                    <span class="sub-label">Shortcuts: Cmd/Ctrl+Z Undo · Delete removes selected layer · Cmd/Ctrl+S saves live</span>
                </div>
            </section>

            {{-- Right Column: Layer Inspector & View Automation Logic --}}
            <aside class="panel">
                <div class="tab-row tab-row--2" role="tablist" aria-label="Inspector sections">
                    <button type="button" class="tab-btn" role="tab" aria-selected="true" data-inspector-tab="layer">Layer &amp; Motion</button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-inspector-tab="view">View &amp; Logic</button>
                </div>

                <div id="inspector-tab-layer" class="stack">
                    <div class="panel-head">
                        <h2 class="panel-title">View Layers</h2>
                        <span class="sub-label" id="layer-stack-count">0 layers</span>
                    </div>
                    <div id="view-layers-list" class="list-scroll" style="max-block-size: 11.5rem;"></div>

                    <div id="no-layer-hint" class="empty-hint">
                        Click a layer on the canvas or in the list above to edit position, animation, and content.
                    </div>

                    <div id="layer-props-card" class="stack" style="padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);" hidden>
                        <div class="grid-2">
                            <label class="stack">
                                <span class="sub-label">Layer Name</span>
                                <input type="text" id="lp-name" class="s-input">
                            </label>
                            <label class="stack">
                                <span class="sub-label">Opacity (0 - 1)</span>
                                <input type="number" id="lp-opacity" class="s-input" min="0" max="1" step="0.05">
                            </label>
                        </div>
                        <div class="grid-3">
                            <label class="stack"><span class="sub-label">X (px)</span><input type="number" id="lp-x" class="s-input"></label>
                            <label class="stack"><span class="sub-label">Y (px)</span><input type="number" id="lp-y" class="s-input"></label>
                            <label class="stack"><span class="sub-label">Rotation (°)</span><input type="number" id="lp-rot" class="s-input"></label>
                            <label class="stack"><span class="sub-label">Width (px)</span><input type="number" id="lp-w" class="s-input" min="16"></label>
                            <label class="stack"><span class="sub-label">Height (px)</span><input type="number" id="lp-h" class="s-input" min="16"></label>
                            <label class="stack"><span class="sub-label">Radius (px)</span><input type="number" id="lp-radius" class="s-input" min="0"></label>
                        </div>

                        <div class="grid-2">
                            <label class="stack">
                                <span class="sub-label">Enter Animation</span>
                                <select id="lp-enter-anim" class="s-select">
                                    <option value="none">None</option>
                                    <option value="fade">Fade In</option>
                                    <option value="slide_up">Slide Up</option>
                                    <option value="slide_down">Slide Down</option>
                                    <option value="slide_left">Slide Left</option>
                                    <option value="slide_right">Slide Right</option>
                                    <option value="scale_in">Scale Zoom</option>
                                    <option value="blur_in">Blur Reveal</option>
                                </select>
                            </label>
                            <label class="stack">
                                <span class="sub-label">Loop Effect</span>
                                <select id="lp-loop-anim" class="s-select">
                                    <option value="none">None</option>
                                    <option value="pulse">Pulse</option>
                                    <option value="float">Float</option>
                                    <option value="glow">Accent Glow</option>
                                </select>
                            </label>
                        </div>

                        <label class="stack">
                            <span class="sub-label">Visibility Logic Condition</span>
                            <select id="lp-condition" class="s-select">
                                <option value="always">Always Visible</option>
                                <option value="timer_running">Only When Timer Running</option>
                                <option value="timer_paused">Only When Timer Stopped/Paused</option>
                                <option value="timer_under_10s">Only Under 10 Seconds Remaining</option>
                                <option value="lower_third_visible">Only When Lower Third Active</option>
                                <option value="alert_visible">Only When Alert Active</option>
                            </select>
                        </label>

                        <div id="layer-type-fields" class="stack" style="padding: 0.55rem; border-radius: 0.5rem; background: var(--bg-chrome); border: 1px solid var(--hairline);"></div>

                        <div class="grid-2">
                            <button type="button" id="lp-delete-btn" class="s-btn s-btn-danger">Delete Layer</button>
                            <button type="button" id="lp-save-btn" class="s-btn is-active">Save View</button>
                        </div>
                    </div>
                </div>

                <div id="inspector-tab-view" class="stack" hidden>
                    <label class="stack">
                        <span class="sub-label">View Name</span>
                        <input type="text" id="vp-name" class="s-input">
                    </label>
                    <label class="stack">
                        <span class="sub-label">Background Mode</span>
                        <select id="vp-bg-mode" class="s-select">
                            <option value="theme">Inherit Collection Default Theme</option>
                            <option value="transparent">Transparent (OBS Alpha Overlay)</option>
                            <option value="solid">Solid Color (Cutouts Punch Alpha)</option>
                            <option value="asset">Animated Video / Image Backdrop</option>
                        </select>
                    </label>
                    <div class="stack" id="vp-bg-color-wrap" hidden>
                        <span class="sub-label">Solid Bg Color</span>
                        <input type="text" id="vp-bg-color" class="s-input" data-color-picker data-fallback="#0B0A08" aria-label="View Solid Background Color">
                    </div>
                    <div class="stack" id="vp-bg-asset-wrap" hidden>
                        <label class="stack">
                            <span class="sub-label">Bg Media Asset</span>
                            <select id="vp-bg-asset" class="s-select">
                                <option value="">None</option>
                            </select>
                        </label>
                        <label class="stack">
                            <span class="sub-label">Upload View BG Video (.mp4/.webm) or Image</span>
                            <input type="file" id="vp-bg-upload-input" class="s-input" accept="video/mp4,video/webm,image/*">
                        </label>
                    </div>

                    <div class="stack">
                        <div class="stack">
                            <span class="sub-label">View Accent Override</span>
                            <input type="text" id="vp-theme-accent" class="s-input" data-color-picker data-fallback="#CDAD69" data-allow-clear="1" placeholder="Inherit Collection" aria-label="View Accent Override">
                        </div>
                        <div class="stack">
                            <span class="sub-label">View Surface Override</span>
                            <input type="text" id="vp-theme-surface" class="s-input" data-color-picker data-fallback="#0B0A08" data-allow-clear="1" placeholder="Inherit Collection" aria-label="View Surface Override">
                        </div>
                    </div>

                    <div class="grid-2">
                        <label class="stack">
                            <span class="sub-label">Transition Override</span>
                            <select id="vp-tx-type" class="s-select">
                                <option value="">Inherit Default</option>
                                @foreach ($transitionTypes as $txKey => $txLabel)
                                    <option value="{{ $txKey }}">{{ $txLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="stack">
                            <span class="sub-label">Transition ms (max 3000)</span>
                            <input type="number" id="vp-tx-dur" class="s-input" min="0" max="3000" step="50" placeholder="450">
                        </label>
                    </div>

                    <fieldset class="live-content-box" id="vp-logic-fieldset">
                        <legend>When Countdown Timer Ends (00:00)</legend>
                        <div id="vp-logic-editor-host" class="stack"></div>
                    </fieldset>
                    <datalist id="builder-obs-scenes-list">
                        @foreach (($obsScenes ?? []) as $obsSceneName)
                            <option value="{{ $obsSceneName }}"></option>
                        @endforeach
                    </datalist>

                    <button type="button" id="vp-save-btn" class="s-btn is-active">Save View &amp; Logic</button>
                </div>
            </aside>
        </main>

        {{-- Native Confirmation Dialog for all delete / remove / bulk cleanup actions --}}
        <dialog id="builder-confirm-dialog" class="obs-dialog" aria-labelledby="builder-confirm-title">
            <div class="obs-dialog-inner">
                <div class="obs-dialog-head">
                    <h2 id="builder-confirm-title" class="obs-dialog-title">Confirm Action</h2>
                    <button type="button" id="builder-confirm-close" class="s-btn s-btn-xs">Close</button>
                </div>
                <p id="builder-confirm-copy" class="obs-dialog-copy"></p>
                <div class="obs-dialog-actions">
                    <button type="button" id="builder-confirm-cancel" class="s-btn">Cancel</button>
                    <button type="button" id="builder-confirm-ok" class="s-btn s-btn-danger">Delete</button>
                </div>
            </div>
        </dialog>

        <div id="builder-toast" class="toast" role="status" hidden></div>

        @php
            $builderEndpoints = [
                'updateCollection' => route('studio.collections.update', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'updateRuntime' => route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'switchView' => route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'pushObs' => route('studio.collections.push', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'storeView' => route('studio.collections.views.store', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'reorderViews' => route('studio.collections.views.reorder', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'updateViewTpl' => route('studio.collections.views.update', ['studio' => $studio->slug, 'collection' => $collection->id, 'view' => '__VIEW__'], false),
                'duplicateViewTpl' => route('studio.collections.views.duplicate', ['studio' => $studio->slug, 'collection' => $collection->id, 'view' => '__VIEW__'], false),
                'deleteViewTpl' => route('studio.collections.views.destroy', ['studio' => $studio->slug, 'collection' => $collection->id, 'view' => '__VIEW__'], false),
                'uploadAsset' => route('studio.collections.assets.store', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'cleanupAssets' => route('studio.collections.assets.cleanup', ['studio' => $studio->slug, 'collection' => $collection->id], false),
                'deleteAssetTpl' => route('studio.collections.assets.destroy', ['studio' => $studio->slug, 'asset' => '__ASSET__'], false),
            ];
        @endphp
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                let colData = @json($collectionPayload);
                const requestedViewId = @json($initialViewId ?? null);
                const FONT_FAMILY_MAP = @json($fontFamilies ?? []);
                const FONT_FAMILY_OPTIONS = [['', 'Inherit Collection Font'], ...Object.entries(FONT_FAMILY_MAP)];
                const TIMER_END_ACTION_GROUPS = @json($timerEndActionGroups ?? []);
                const OBS_SCENES_LIST = @json($obsScenes ?? []);
                const MACROS_LIST = @json($macrosPayload ?? []);
                let selectedViewId = Number(
                    (requestedViewId && (colData.views || []).some((v) => Number(v.id) === Number(requestedViewId)))
                        ? requestedViewId
                        : (colData.active_view_id || colData.views?.[0]?.id || 0)
                );
                let selectedLayerId = null;
                let dragActive = false;
                let isDirty = false;
                let themeDirty = false;
                let runtimeDirty = false;
                let lastEffectiveBgLabel = '';
                const endpoints = @json($builderEndpoints);

                const formatFontStack = (family, fallbackStack = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif") => {
                    const clean = String(family || '').trim();
                    if (!clean) return fallbackStack;
                    if (['system-ui', 'serif', 'sans-serif', 'monospace'].includes(clean.toLowerCase())) {
                        return clean;
                    }
                    return `'${clean.replace(/'/g, '')}', ${fallbackStack}`;
                };

                const applyStrokeStyle = (el, { width = 0, style = 'solid', color = '', position = 'inside', fallbackBorder = null } = {}) => {
                    const bw = Math.max(0, Number(width || 0));
                    const bs = style || 'solid';
                    const pos = position || 'inside';
                    el.style.outline = 'none';
                    el.style.outlineOffset = '0px';
                    if (bw > 0 && color) {
                        if (pos === 'outside') {
                            el.style.border = 'none';
                            el.style.outline = `${bw}px ${bs} ${color}`;
                            el.style.outlineOffset = '0px';
                        } else if (pos === 'center') {
                            el.style.border = 'none';
                            el.style.outline = `${bw}px ${bs} ${color}`;
                            el.style.outlineOffset = `${-(bw / 2)}px`;
                        } else {
                            el.style.border = `${bw}px ${bs} ${color}`;
                        }
                    } else if (fallbackBorder) {
                        el.style.border = fallbackBorder;
                    } else {
                        el.style.border = 'none';
                    }
                };

                // Per-view Undo / Redo History Engine
                const historyByView = new Map();
                const MAX_HISTORY = 60;
                let lastUndoBurstAt = 0;
                let lastUndoBurstKey = '';

                const getViewHistory = (viewId) => {
                    const key = Number(viewId || 0);
                    if (!historyByView.has(key)) {
                        historyByView.set(key, { undo: [], redo: [] });
                    }
                    return historyByView.get(key);
                };

                const deepClone = (obj) => JSON.parse(JSON.stringify(obj));

                const captureViewSnapshot = (view) => ({
                    name: view.name,
                    background_mode: view.background_mode,
                    background_color: view.background_color,
                    background_asset_id: view.background_asset_id,
                    background_asset_url: view.background_asset_url,
                    background_asset_type: view.background_asset_type,
                    theme_override: view.theme_override ? deepClone(view.theme_override) : null,
                    transition_override: view.transition_override ? deepClone(view.transition_override) : null,
                    layers: deepClone(view.layers || []),
                    logic_rules: view.logic_rules ? deepClone(view.logic_rules) : null,
                    selectedLayerId,
                });

                const applyViewSnapshot = (view, snap) => {
                    view.name = snap.name;
                    view.background_mode = snap.background_mode;
                    view.background_color = snap.background_color;
                    view.background_asset_id = snap.background_asset_id;
                    view.background_asset_url = snap.background_asset_url;
                    view.background_asset_type = snap.background_asset_type;
                    view.theme_override = snap.theme_override ? deepClone(snap.theme_override) : null;
                    view.transition_override = snap.transition_override ? deepClone(snap.transition_override) : null;
                    view.layers = deepClone(snap.layers || []);
                    view.logic_rules = snap.logic_rules ? deepClone(snap.logic_rules) : null;
                    selectedLayerId = snap.selectedLayerId && view.layers.some((l) => String(l.id) === String(snap.selectedLayerId))
                        ? snap.selectedLayerId
                        : null;
                };

                const syncHistoryButtons = () => {
                    const hist = getViewHistory(selectedViewId);
                    const undoBtn = document.getElementById('btn-undo-canvas');
                    const redoBtn = document.getElementById('btn-redo-canvas');
                    if (undoBtn) undoBtn.disabled = hist.undo.length === 0;
                    if (redoBtn) redoBtn.disabled = hist.redo.length === 0;
                };

                const pushUndoSnapshot = (explicitSnap = null) => {
                    const view = getCurrentView();
                    if (!view) return;
                    const hist = getViewHistory(view.id);
                    const snap = explicitSnap || captureViewSnapshot(view);
                    hist.undo.push(snap);
                    if (hist.undo.length > MAX_HISTORY) hist.undo.shift();
                    hist.redo = [];
                    lastUndoBurstAt = 0;
                    lastUndoBurstKey = '';
                    syncHistoryButtons();
                };

                const recordDebouncedUndo = (burstKey = 'edit') => {
                    const now = Date.now();
                    if (burstKey === lastUndoBurstKey && (now - lastUndoBurstAt) < 550) {
                        lastUndoBurstAt = now;
                        return;
                    }
                    pushUndoSnapshot();
                    lastUndoBurstKey = burstKey;
                    lastUndoBurstAt = now;
                };

                const performUndo = () => {
                    const view = getCurrentView();
                    if (!view) return;
                    const hist = getViewHistory(view.id);
                    if (hist.undo.length === 0) return;
                    hist.redo.push(captureViewSnapshot(view));
                    const prev = hist.undo.pop();
                    applyViewSnapshot(view, prev);
                    setDirty(true);
                    renderAll();
                    syncHistoryButtons();
                    showToast('Undid last change');
                };

                const performRedo = () => {
                    const view = getCurrentView();
                    if (!view) return;
                    const hist = getViewHistory(view.id);
                    if (hist.redo.length === 0) return;
                    hist.undo.push(captureViewSnapshot(view));
                    const next = hist.redo.pop();
                    applyViewSnapshot(view, next);
                    setDirty(true);
                    renderAll();
                    syncHistoryButtons();
                    showToast('Redid change');
                };

                const pushRuntimePatch = async (patch, toastMsg = 'Live content synced to stage') => {
                    const res = await apiJson(endpoints.updateRuntime, 'POST', patch);
                    if (res.collection?.runtime_state) {
                        colData.runtime_state = res.collection.runtime_state;
                    }
                    runtimeDirty = false;
                    renderCanvas();
                    if (toastMsg) showToast(toastMsg);
                    return res.collection;
                };

                const setDirty = (dirty) => {
                    isDirty = Boolean(dirty);
                    const badge = document.getElementById('builder-save-state');
                    if (badge) {
                        badge.textContent = isDirty ? 'Unsaved changes' : 'Saved';
                        badge.classList.toggle('is-dirty', isDirty);
                    }
                };

                const showToast = (msg) => {
                    const el = document.getElementById('builder-toast');
                    if (!el) return;
                    el.textContent = msg;
                    el.hidden = false;
                    clearTimeout(el._timer);
                    el._timer = setTimeout(() => { el.hidden = true; }, 2600);
                };

                const confirmBuilderAction = ({ title = 'Confirm Delete', message = 'Are you sure?', confirmLabel = 'Delete' }) => new Promise((resolve) => {
                    const dialog = document.getElementById('builder-confirm-dialog');
                    if (!dialog || typeof dialog.showModal !== 'function') {
                        resolve(window.confirm(message));
                        return;
                    }
                    const titleEl = document.getElementById('builder-confirm-title');
                    const copyEl = document.getElementById('builder-confirm-copy');
                    const okBtn = document.getElementById('builder-confirm-ok');
                    const cancelBtn = document.getElementById('builder-confirm-cancel');
                    const closeBtn = document.getElementById('builder-confirm-close');
                    if (titleEl) titleEl.textContent = title;
                    if (copyEl) copyEl.textContent = message;
                    if (okBtn) okBtn.textContent = confirmLabel;

                    let settled = false;
                    const finish = (accepted) => {
                        if (settled) return;
                        settled = true;
                        okBtn?.removeEventListener('click', onOk);
                        cancelBtn?.removeEventListener('click', onCancel);
                        closeBtn?.removeEventListener('click', onCancel);
                        dialog.removeEventListener('cancel', onCancel);
                        if (dialog.open) dialog.close();
                        resolve(accepted);
                    };
                    const onOk = () => finish(true);
                    const onCancel = (ev) => {
                        if (ev && ev.type === 'cancel') ev.preventDefault();
                        finish(false);
                    };
                    okBtn?.addEventListener('click', onOk);
                    cancelBtn?.addEventListener('click', onCancel);
                    closeBtn?.addEventListener('click', onCancel);
                    dialog.addEventListener('cancel', onCancel);
                    dialog.showModal();
                });

                const apiJson = async (url, method = 'POST', body = {}) => {
                    const res = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: method === 'GET' ? undefined : JSON.stringify(body),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
                    return data;
                };

                const getCurrentView = () => (colData.views || []).find((v) => Number(v.id) === Number(selectedViewId)) || colData.views?.[0] || null;

                // Universal CSS Color + Opacity (Alpha) parser, formatter & widget builder
                const clampNum = (n, min, max) => Math.min(max, Math.max(min, Number.isFinite(n) ? n : min));

                const hexToRgb = (hex6) => {
                    const clean = String(hex6 || '#CDAD69').replace('#', '');
                    return {
                        r: parseInt(clean.slice(0, 2), 16) || 0,
                        g: parseInt(clean.slice(2, 4), 16) || 0,
                        b: parseInt(clean.slice(4, 6), 16) || 0,
                    };
                };

                const rgbToHex6 = (r, g, b) => '#' + [r, g, b]
                    .map((v) => clampNum(Math.round(Number(v) || 0), 0, 255).toString(16).padStart(2, '0'))
                    .join('')
                    .toUpperCase();

                const parseCssColor = (raw, fallbackHex = '#CDAD69') => {
                    const fb = /^#[0-9a-f]{6}$/i.test(String(fallbackHex || '').trim())
                        ? String(fallbackHex).trim().toUpperCase()
                        : '#CDAD69';
                    const str = String(raw || '').trim();
                    if (!str) {
                        return { hex6: fb, alpha: 100, empty: true };
                    }
                    if (str.toLowerCase() === 'transparent') {
                        return { hex6: fb, alpha: 0, empty: false };
                    }
                    const shortHex = str.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])?$/i);
                    if (shortHex) {
                        const hex6 = `#${shortHex[1]}${shortHex[1]}${shortHex[2]}${shortHex[2]}${shortHex[3]}${shortHex[3]}`.toUpperCase();
                        const alpha = shortHex[4] !== undefined
                            ? Math.round((parseInt(shortHex[4] + shortHex[4], 16) / 255) * 100)
                            : 100;
                        return { hex6, alpha, empty: false };
                    }
                    const longHex = str.match(/^#([0-9a-f]{6})([0-9a-f]{2})?$/i);
                    if (longHex) {
                        const hex6 = `#${longHex[1]}`.toUpperCase();
                        const alpha = longHex[2] !== undefined
                            ? Math.round((parseInt(longHex[2], 16) / 255) * 100)
                            : 100;
                        return { hex6, alpha, empty: false };
                    }
                    const rgbaMatch = str.match(/^rgba?\(\s*([0-9.]+)\s*[,\s]\s*([0-9.]+)\s*[,\s]\s*([0-9.]+)(?:\s*[,/]\s*([0-9.]+%?))?\s*\)$/i);
                    if (rgbaMatch) {
                        const hex6 = rgbToHex6(rgbaMatch[1], rgbaMatch[2], rgbaMatch[3]);
                        let alpha = 100;
                        if (rgbaMatch[4] !== undefined) {
                            const rawA = rgbaMatch[4].trim();
                            alpha = rawA.endsWith('%')
                                ? clampNum(Math.round(parseFloat(rawA)), 0, 100)
                                : clampNum(Math.round(parseFloat(rawA) * 100), 0, 100);
                        }
                        return { hex6, alpha, empty: false };
                    }
                    return { hex6: fb, alpha: 100, empty: false, passthrough: true };
                };

                const formatCssColor = (hex6, alphaPct = 100) => {
                    const cleanHex = /^#[0-9a-f]{6}$/i.test(String(hex6 || '').trim())
                        ? String(hex6).trim().toUpperCase()
                        : '#CDAD69';
                    const pct = clampNum(Math.round(Number(alphaPct)), 0, 100);
                    if (pct >= 100) {
                        return cleanHex;
                    }
                    const { r, g, b } = hexToRgb(cleanHex);
                    const a = Number((pct / 100).toFixed(2));
                    return `rgba(${r}, ${g}, ${b}, ${a})`;
                };

                const createColorOpacityWidget = ({
                    initialValue = '',
                    fallbackHex = '#CDAD69',
                    placeholder = 'Inherit',
                    allowClear = true,
                    existingTextInput = null,
                    labelForAria = 'Color',
                    onChange = () => {},
                }) => {
                    const stack = document.createElement('div');
                    stack.className = 'color-field-stack';

                    const topRow = document.createElement('div');
                    topRow.className = allowClear ? 'color-field-row' : 'color-field-row color-field-row--no-clear';

                    const swatchBox = document.createElement('div');
                    swatchBox.className = 'color-swatch-box';

                    const preview = document.createElement('div');
                    preview.className = 'color-swatch-preview';

                    const swatch = document.createElement('input');
                    swatch.type = 'color';
                    swatch.className = 'color-swatch-input';
                    swatch.setAttribute('aria-label', `${labelForAria} picker`);

                    swatchBox.append(preview, swatch);

                    const txt = existingTextInput || document.createElement('input');
                    if (!existingTextInput) {
                        txt.type = 'text';
                        txt.className = 's-input';
                        txt.placeholder = placeholder || 'Inherit';
                        txt.setAttribute('aria-label', `${labelForAria} value`);
                    }

                    const alphaRow = document.createElement('div');
                    alphaRow.className = 'color-alpha-row';

                    const alphaLbl = document.createElement('span');
                    alphaLbl.className = 'color-alpha-label';
                    alphaLbl.textContent = 'Opacity';

                    const slider = document.createElement('input');
                    slider.type = 'range';
                    slider.className = 'alpha-slider';
                    slider.min = '0';
                    slider.max = '100';
                    slider.step = '1';
                    slider.setAttribute('aria-label', `${labelForAria} opacity`);

                    const pctInput = document.createElement('input');
                    pctInput.type = 'number';
                    pctInput.className = 's-input color-alpha-pct';
                    pctInput.min = '0';
                    pctInput.max = '100';
                    pctInput.step = '1';
                    pctInput.setAttribute('aria-label', `${labelForAria} opacity percentage`);

                    const pctSuffix = document.createElement('span');
                    pctSuffix.className = 'color-alpha-label';
                    pctSuffix.textContent = '%';

                    alphaRow.append(alphaLbl, slider, pctInput, pctSuffix);

                    const syncVisuals = (rawVal, updateText = true) => {
                        const parsed = parseCssColor(rawVal, fallbackHex);
                        swatch.value = parsed.hex6;
                        slider.value = String(parsed.alpha);
                        slider.style.setProperty('--alpha-hex', parsed.hex6);
                        slider.style.setProperty('--control-value', String(parsed.alpha));
                        slider.setAttribute('aria-valuetext', `${parsed.alpha}%`);
                        pctInput.value = String(parsed.alpha);
                        const displayColor = parsed.empty
                            ? formatCssColor(parsed.hex6, 100)
                            : (parsed.passthrough ? String(rawVal || '').trim() : formatCssColor(parsed.hex6, parsed.alpha));
                        preview.style.background = displayColor;
                        if (updateText) {
                            txt.value = parsed.empty ? '' : String(rawVal || '').trim();
                        }
                    };

                    const commitFromParts = (hex6, alphaPct) => {
                        const formatted = formatCssColor(hex6, alphaPct);
                        syncVisuals(formatted, true);
                        onChange(formatted);
                    };

                    swatch.addEventListener('input', () => {
                        commitFromParts(swatch.value, Number(slider.value));
                    });

                    slider.addEventListener('input', () => {
                        const pct = clampNum(Number(slider.value), 0, 100);
                        commitFromParts(swatch.value, pct);
                    });

                    pctInput.addEventListener('input', () => {
                        if (pctInput.value === '') return;
                        const pct = clampNum(Number(pctInput.value), 0, 100);
                        commitFromParts(swatch.value, pct);
                    });
                    pctInput.addEventListener('blur', () => {
                        const pct = clampNum(Number(pctInput.value === '' ? slider.value : pctInput.value), 0, 100);
                        commitFromParts(swatch.value, pct);
                    });

                    txt.addEventListener('input', () => {
                        const cleaned = txt.value.trim();
                        syncVisuals(cleaned, false);
                        onChange(cleaned);
                    });

                    topRow.append(swatchBox, txt);

                    if (allowClear) {
                        const clr = document.createElement('button');
                        clr.type = 'button';
                        clr.className = 's-btn s-btn-xs';
                        clr.textContent = '×';
                        clr.title = 'Reset / Clear color';
                        clr.addEventListener('click', () => {
                            syncVisuals('', true);
                            onChange('');
                        });
                        topRow.appendChild(clr);
                    }

                    stack.append(topRow, alphaRow);
                    syncVisuals(initialValue, true);

                    return { element: stack, sync: (val) => syncVisuals(val, true) };
                };

                const staticColorWidgets = new Map();
                const mountStaticColorPickers = () => {
                    document.querySelectorAll('input[data-color-picker]').forEach((inp) => {
                        if (staticColorWidgets.has(inp.id)) return;
                        const parent = inp.parentNode;
                        if (!parent) return;
                        const fallbackHex = inp.dataset.fallback || '#CDAD69';
                        const allowClear = inp.dataset.allowClear === '1';
                        const labelText = inp.getAttribute('aria-label') || 'Color';
                        const placeholderEl = document.createComment(`color-host-${inp.id}`);
                        parent.replaceChild(placeholderEl, inp);

                        const widget = createColorOpacityWidget({
                            initialValue: inp.value,
                            fallbackHex,
                            placeholder: inp.placeholder || '',
                            allowClear,
                            existingTextInput: inp,
                            labelForAria: labelText,
                            onChange: () => {
                                if (inp.id.startsWith('col-')) {
                                    applyLiveThemeFromInputs();
                                } else if (inp.id.startsWith('vp-')) {
                                    applyLiveViewSettingsFromInputs();
                                }
                            },
                        });

                        parent.replaceChild(widget.element, placeholderEl);
                        staticColorWidgets.set(inp.id, widget);
                    });
                };

                const setStaticColorValue = (id, value) => {
                    const widget = staticColorWidgets.get(id);
                    if (widget) {
                        widget.sync(value ?? '');
                    } else {
                        const el = document.getElementById(id);
                        if (el) el.value = value ?? '';
                    }
                };

                const updateCanvasScale = () => {
                    const wrap = document.getElementById('canvas-stage-wrap');
                    const logical = document.getElementById('canvas-logical');
                    if (!wrap || !logical) return;
                    const w = Math.max(320, Number(colData.width || 1920));
                    const h = Math.max(180, Number(colData.height || 1080));
                    logical.style.width = `${w}px`;
                    logical.style.height = `${h}px`;
                    const rect = wrap.getBoundingClientRect();
                    if (rect.width > 0) {
                        logical.style.transform = `scale(${rect.width / w})`;
                    }
                    document.getElementById('canvas-meta-readout').textContent = lastEffectiveBgLabel
                        ? `Canvas: ${w} × ${h} px · ${lastEffectiveBgLabel}`
                        : `Canvas: ${w} × ${h} px`;
                };

                if (window.ResizeObserver) {
                    new ResizeObserver(updateCanvasScale).observe(document.getElementById('canvas-stage-wrap'));
                }
                window.addEventListener('resize', updateCanvasScale);

                const resolveEffectiveBackground = (view, theme) => {
                    const viewMode = view?.background_mode || 'theme';
                    if (viewMode === 'transparent') {
                        return { mode: 'transparent', label: 'Transparent (OBS Alpha)' };
                    }
                    if (viewMode === 'solid') {
                        return { mode: 'solid', color: view.background_color || theme.surface_color || '#0B0A08', label: 'Solid Color' };
                    }
                    if (viewMode === 'asset' && view.background_asset_url) {
                        const isVid = view.background_asset_type === 'video'
                            || view.background_asset_type === 'stinger'
                            || /\.(webm|mp4)(\?.*)?$/i.test(view.background_asset_url);
                        return { mode: 'asset', url: view.background_asset_url, isVideo: isVid, label: isVid ? 'Animated Video BG' : 'Image Backdrop' };
                    }
                    const themeMode = theme?.background_mode || 'gradient';
                    if (themeMode === 'transparent') {
                        return { mode: 'transparent', label: 'Transparent (Collection Default)' };
                    }
                    if (themeMode === 'solid') {
                        return { mode: 'solid', color: theme.background_color || theme.surface_color || '#0B0A08', label: 'Solid Color (Collection Default)' };
                    }
                    if (themeMode === 'asset' && theme.background_asset_url) {
                        const isVid = theme.background_asset_type === 'video'
                            || theme.background_asset_type === 'stinger'
                            || /\.(webm|mp4)(\?.*)?$/i.test(theme.background_asset_url);
                        return { mode: 'asset', url: theme.background_asset_url, isVideo: isVid, label: isVid ? 'Animated Video BG (Default)' : 'Image BG (Default)' };
                    }
                    return { mode: 'gradient', label: 'Theme Gradient' };
                };

                const getMergedViewTheme = (view) => {
                    const cleanOverride = Object.fromEntries(
                        Object.entries(view?.theme_override || {}).filter(([, val]) => val !== null && val !== '')
                    );
                    return { ...(colData.theme || {}), ...cleanOverride };
                };

                const syncBackgroundFieldVisibility = () => {
                    const colBgMode = document.getElementById('col-bg-mode')?.value || 'gradient';
                    const colColorWrap = document.getElementById('col-bg-color-wrap');
                    const colAssetWrap = document.getElementById('col-bg-asset-wrap');
                    if (colColorWrap) colColorWrap.hidden = colBgMode !== 'solid';
                    if (colAssetWrap) colAssetWrap.hidden = colBgMode !== 'asset';

                    const vpBgMode = document.getElementById('vp-bg-mode')?.value || 'theme';
                    const vpColorWrap = document.getElementById('vp-bg-color-wrap');
                    const vpAssetWrap = document.getElementById('vp-bg-asset-wrap');
                    if (vpColorWrap) vpColorWrap.hidden = vpBgMode !== 'solid';
                    if (vpAssetWrap) vpAssetWrap.hidden = vpBgMode !== 'asset';

                    const view = getCurrentView();
                    const overrideHint = document.getElementById('col-bg-override-hint');
                    const overrideText = document.getElementById('col-bg-override-text');
                    if (overrideHint && overrideText && view) {
                        const isOverridden = (view.background_mode || 'theme') !== 'theme';
                        overrideHint.hidden = !isOverridden;
                        if (isOverridden) {
                            overrideText.textContent = `Current view "${view.name}" uses its own "${view.background_mode}" background instead of Collection Default.`;
                        }
                    }
                };

                const renderCanvas = () => {
                    if (dragActive) return;
                    const logical = document.getElementById('canvas-logical');
                    const view = getCurrentView();
                    if (!logical || !view) return;

                    document.getElementById('editing-view-title').textContent = view.name || 'View';
                    logical.innerHTML = '';

                    const theme = getMergedViewTheme(view);
                    const accent = theme.accent_color || '#CDAD69';
                    const surface = theme.surface_color || '#0B0A08';
                    const textColor = theme.text_color || '#FAF7EE';
                    const mutedColor = theme.muted_color || '#ABA291';
                    const themeFontStack = formatFontStack(theme.font_family || 'Instrument Sans');
                    const secondary = theme.secondary_color && theme.secondary_color !== '#F4E185'
                        ? theme.secondary_color
                        : (accent === '#CDAD69' ? '#F4E185' : accent);
                    const effectiveBg = resolveEffectiveBackground(view, theme);

                    logical.style.color = textColor;
                    logical.style.fontFamily = themeFontStack;

                    const bg = document.createElement('div');
                    bg.className = 'canvas-backdrop';
                    if (effectiveBg.mode === 'transparent') {
                        bg.style.background = 'transparent';
                    } else if (effectiveBg.mode === 'solid') {
                        bg.style.background = effectiveBg.color;
                    } else if (effectiveBg.mode === 'asset' && effectiveBg.url) {
                        if (effectiveBg.isVideo) {
                            const bgVid = document.createElement('video');
                            bgVid.src = effectiveBg.url;
                            bgVid.autoplay = true;
                            bgVid.loop = true;
                            bgVid.muted = true;
                            bgVid.playsInline = true;
                            bgVid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;';
                            bg.appendChild(bgVid);
                        } else {
                            const bgImg = document.createElement('img');
                            bgImg.src = effectiveBg.url;
                            bgImg.alt = '';
                            bgImg.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;';
                            bg.appendChild(bgImg);
                        }
                    } else {
                        bg.style.background = `radial-gradient(circle at 16% 18%, color-mix(in oklab, ${accent} 32%, transparent), transparent 46%), radial-gradient(circle at 84% 22%, color-mix(in oklab, ${secondary} 20%, transparent), transparent 46%), radial-gradient(circle at 48% 88%, color-mix(in oklab, ${accent} 16%, transparent), transparent 48%), linear-gradient(155deg, color-mix(in oklab, ${surface} 96%, #000) 0%, #050507 100%)`;
                    }
                    logical.appendChild(bg);

                    const layers = [...(view.layers || [])].sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0));
                    const wrap = document.getElementById('canvas-stage-wrap');
                    const wLogical = Math.max(320, Number(colData.width || 1920));

                    layers.forEach((layer, idx) => {
                        const box = document.createElement('div');
                        box.className = 'canvas-layer'
                            + (String(layer.id) === String(selectedLayerId) ? ' is-selected' : '')
                            + (layer.locked ? ' is-locked' : '')
                            + (layer.visible === false ? ' is-hidden' : '');
                        box.style.left = `${Number(layer.x || 0)}px`;
                        box.style.top = `${Number(layer.y || 0)}px`;
                        box.style.width = `${Math.max(20, Number(layer.w || 200))}px`;
                        box.style.height = `${Math.max(20, Number(layer.h || 80))}px`;
                        box.style.zIndex = String(idx + 2);
                        box.style.opacity = String(layer.opacity ?? 1);
                        box.style.borderRadius = `${Number(layer.radius || 0)}px`;
                        if (Number(layer.rotation || 0) !== 0) {
                            box.style.transform = `rotate(${Number(layer.rotation)}deg)`;
                        }

                        const p = layer.props || {};
                        const isSelected = String(layer.id) === String(selectedLayerId);
                        const glassBorder = `color-mix(in oklab, ${accent} 42%, transparent)`;
                        const layerRadiusCss = (layer.type === 'color_box' && p.shape_preset === 'circle')
                            ? '50%'
                            : `${Number(layer.radius || 0)}px`;
                        box.style.borderRadius = layerRadiusCss;
                        const applyInnerOutline = (el, defaultBorder = null) => {
                            const bw = Number(p.border_width ?? 0);
                            const bs = p.border_style || 'solid';
                            const pos = p.border_position || 'inside';
                            if (p.border_width !== undefined && p.border_width !== '' && bw > 0) {
                                applyStrokeStyle(el, {
                                    width: bw,
                                    style: bs,
                                    color: p.border_color || accent,
                                    position: pos,
                                });
                            } else if (p.border_width === 0 || p.border_width === '0') {
                                el.style.border = 'none';
                                el.style.outline = 'none';
                            } else if (p.border_color) {
                                applyStrokeStyle(el, {
                                    width: 2,
                                    style: bs,
                                    color: p.border_color,
                                    position: pos,
                                });
                            } else if (defaultBorder) {
                                el.style.border = defaultBorder;
                                el.style.outline = 'none';
                            }
                        };

                        if (layer.type === 'camera_cutout') {
                            const cutoutInner = document.createElement('div');
                            cutoutInner.style.cssText = `width:100%;height:100%;box-sizing:border-box;border-radius:${layerRadiusCss};background-image:linear-gradient(45deg, rgba(255,255,255,0.09) 25%, transparent 25%), linear-gradient(-45deg, rgba(255,255,255,0.09) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.09) 75%), linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.09) 75%);background-size:32px 32px;background-color:rgba(0,0,0,0.42);display:grid;place-items:center;color:rgba(250,247,238,0.65);font-size:26px;font-weight:600;pointer-events:none;text-align:center;padding:24px;`;
                            const bw = Number(p.border_width ?? 3);
                            const bc = p.border_color || accent;
                            applyStrokeStyle(cutoutInner, {
                                width: bw,
                                style: p.border_style || 'solid',
                                color: bc,
                                position: p.border_position || 'inside',
                            });
                            if (p.glow) cutoutInner.style.boxShadow = `inset 0 0 0 1px rgba(255,236,179,0.22), 0 0 36px -14px ${bc}`;
                            cutoutInner.textContent = p.label || 'OBS Camera / Capture Shows Here';
                            box.appendChild(cutoutInner);
                        } else if (layer.type === 'color_box') {
                            const inner = document.createElement('div');
                            const fillMode = p.fill_mode || 'solid';
                            const fillColor = p.fill_color || accent;
                            const fillEnd = p.fill_color_end || surface;
                            const angle = Number(p.gradient_angle ?? 135);
                            const bw = Math.max(0, Number(p.border_width ?? 0));
                            const bs = p.border_style || 'solid';
                            const bc = p.border_color || '#FAF7EE';
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;border-radius:${layerRadiusCss};pointer-events:none;`;
                            if (fillMode === 'transparent') {
                                inner.style.background = 'transparent';
                            } else if (fillMode === 'gradient') {
                                inner.style.background = `linear-gradient(${angle}deg, ${fillColor}, ${fillEnd})`;
                            } else {
                                inner.style.background = fillColor;
                            }
                            applyStrokeStyle(inner, {
                                width: bw,
                                style: bs,
                                color: bc,
                                position: p.border_position || 'inside',
                            });
                            if (p.shadow) inner.style.boxShadow = '0 22px 48px -12px rgba(0, 0, 0, 0.72)';
                            box.appendChild(inner);
                        } else if (layer.type === 'asset' && p.url) {
                            const wrapMedia = document.createElement('div');
                            wrapMedia.style.cssText = `width:100%;height:100%;box-sizing:border-box;border-radius:${layerRadiusCss};overflow:hidden;pointer-events:none;`;
                            applyInnerOutline(wrapMedia);
                            if (p.media_kind === 'video' || /\.(webm|mp4)(\?.*)?$/i.test(p.url)) {
                                const vid = document.createElement('video');
                                vid.src = p.url;
                                vid.autoplay = true;
                                vid.loop = true;
                                vid.muted = true;
                                vid.playsInline = true;
                                vid.style.cssText = `width:100%;height:100%;object-fit:${p.fit || 'contain'};display:block;pointer-events:none;`;
                                wrapMedia.appendChild(vid);
                            } else {
                                const img = document.createElement('img');
                                img.src = p.url;
                                img.alt = '';
                                img.style.cssText = `width:100%;height:100%;object-fit:${p.fit || 'contain'};display:block;pointer-events:none;`;
                                wrapMedia.appendChild(img);
                            }
                            box.appendChild(wrapMedia);
                        } else if (layer.type === 'text') {
                            const preview = document.createElement('div');
                            const align = p.align || 'center';
                            const justify = align === 'left' ? 'flex-start' : (align === 'right' ? 'flex-end' : 'center');
                            preview.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:flex;align-items:center;justify-content:${justify};text-align:${align};padding:${Number(p.padding ?? 12)}px;border-radius:${layerRadiusCss};pointer-events:none;`;
                            if (p.bg_color) preview.style.background = p.bg_color;
                            applyInnerOutline(preview);
                            const span = document.createElement('span');
                            span.textContent = p.text || 'Text';
                            span.style.cssText = `font-size:${Number(p.font_size || 48)}px;font-weight:${Number(p.font_weight || 700)};line-height:1.12;letter-spacing:${Number(p.letter_spacing || 0)}px;color:${p.text_color || textColor};white-space:pre-wrap;`;
                            if (p.font_family) span.style.fontFamily = formatFontStack(p.font_family, themeFontStack);
                            if (p.text_shadow) span.style.textShadow = '0 4px 18px rgba(0,0,0,0.75)';
                            preview.appendChild(span);
                            box.appendChild(preview);
                        } else if (layer.type === 'timer') {
                            const showBg = p.show_bg !== false;
                            const rtTimer = colData.runtime_state?.timer || {};
                            const durSecs = Math.max(0, Number(rtTimer.duration_seconds ?? 300));
                            const hh = Math.floor(durSecs / 3600);
                            const mm = Math.floor((durSecs % 3600) / 60);
                            const ss = durSecs % 60;
                            const sampleClock = p.show_hours
                                ? `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}:${String(ss).padStart(2, '0')}`
                                : `${String(Math.floor(durSecs / 60)).padStart(2, '0')}:${String(ss).padStart(2, '0')}`;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:grid;place-content:center;text-align:center;gap:6px;padding:12px 20px;border-radius:${layerRadiusCss};pointer-events:none;`;
                            inner.style.background = showBg ? (p.bg_color || `color-mix(in oklab, ${surface} 85%, transparent)`) : 'transparent';
                            if (p.font_family) inner.style.fontFamily = formatFontStack(p.font_family, themeFontStack);
                            applyInnerOutline(inner, showBg ? `1px solid ${glassBorder}` : null);
                            const lblDiv = document.createElement('div');
                            lblDiv.style.cssText = `font-size:${Number(p.label_font_size || 20)}px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:${p.label_color || mutedColor};`;
                            lblDiv.textContent = p.label || rtTimer.label || 'LIVE IN';
                            const digitsDiv = document.createElement('div');
                            const digitFont = p.font_family ? formatFontStack(p.font_family, themeFontStack) : "'JetBrains Mono', monospace";
                            digitsDiv.style.cssText = `font-family:${digitFont};font-size:${Number(p.font_size || 72)}px;font-weight:700;line-height:1;color:${p.text_color || accent};`;
                            digitsDiv.textContent = sampleClock;
                            inner.append(lblDiv, digitsDiv);
                            box.appendChild(inner);
                        } else if (layer.type === 'scoreboard') {
                            const sb = colData.runtime_state?.scoreboard || {};
                            const sbAccent = p.accent_color || accent;
                            const sbText = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:0 28px;font-size:26px;font-weight:700;border-radius:${layerRadiusCss};color:${sbText};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;`;
                            if (p.font_family) inner.style.fontFamily = formatFontStack(p.font_family, themeFontStack);
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            const esc = (str) => String(str ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
                            const centerTop = sb.match_label ? `<div style="font-size:13px;color:${sbAccent};letter-spacing:0.08em;text-transform:uppercase;">${esc(sb.match_label)}</div>` : '';
                            inner.innerHTML = `<span>${esc(sb.team_a_name || 'Team Alpha')} <strong style="color:${sbAccent};margin-left:12px;">${Number(sb.team_a_score ?? 0)}</strong></span><span style="text-align:center;padding:0 20px;">${centerTop}<div style="font-size:18px;color:${mutedColor};">${esc(sb.round_label || 'VS')}</div></span><span style="text-align:right;"><strong style="color:${sbAccent};margin-right:12px;">${Number(sb.team_b_score ?? 0)}</strong> ${esc(sb.team_b_name || 'Team Omega')}</span>`;
                            box.appendChild(inner);
                        } else if (layer.type === 'lower_third') {
                            const l3 = colData.runtime_state?.lower_third || {};
                            const l3Accent = p.accent_color || accent;
                            const l3Text = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:flex;align-items:center;gap:18px;padding:0 28px;border-radius:${layerRadiusCss};color:${l3Text};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;`;
                            if (p.font_family) inner.style.fontFamily = formatFontStack(p.font_family, themeFontStack);
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            inner.style.borderLeft = `6px solid ${l3Accent}`;
                            const badgeEl = document.createElement('span');
                            badgeEl.style.cssText = `padding:4px 12px;border-radius:999px;font-size:14px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:${l3Accent};background:color-mix(in oklab, ${l3Accent} 22%, transparent);flex-shrink:0;`;
                            badgeEl.textContent = l3.badge || 'LIVE';
                            const txtWrap = document.createElement('div');
                            const tEl = document.createElement('div');
                            tEl.style.cssText = 'font-size:30px;font-weight:700;line-height:1.15;';
                            tEl.textContent = l3.title || 'Broadcast Host';
                            const sEl = document.createElement('div');
                            sEl.style.cssText = `font-size:19px;color:${l3Accent};`;
                            sEl.textContent = l3.subtitle || 'Live From Studio';
                            txtWrap.append(tEl, sEl);
                            inner.append(badgeEl, txtWrap);
                            box.appendChild(inner);
                        } else if (layer.type === 'ticker') {
                            const tk = colData.runtime_state?.ticker || {};
                            const tkAccent = p.accent_color || accent;
                            const tkText = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:flex;align-items:center;gap:20px;padding:0 24px;font-size:22px;border-radius:${layerRadiusCss};color:${tkText};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;overflow:hidden;`;
                            if (p.font_family) inner.style.fontFamily = formatFontStack(p.font_family, themeFontStack);
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            const pillEl = document.createElement('span');
                            pillEl.style.cssText = `color:${tkAccent};font-weight:800;letter-spacing:0.06em;text-transform:uppercase;flex-shrink:0;`;
                            pillEl.textContent = tk.label || 'LIVE';
                            const trackEl = document.createElement('span');
                            trackEl.style.cssText = `color:${tkText};opacity:0.88;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;`;
                            const itemsText = Array.isArray(tk.items) && tk.items.length > 0
                                ? tk.items.join('   •   ')
                                : 'Edit Ticker headlines in the Inspector on the right...';
                            trackEl.textContent = itemsText;
                            inner.append(pillEl, trackEl);
                            box.appendChild(inner);
                        } else if (layer.type === 'embed') {
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:grid;place-items:center;font-size:24px;border-radius:${layerRadiusCss};color:${mutedColor};background:${p.bg_color || `color-mix(in oklab, ${surface} 86%, transparent)`};pointer-events:none;`;
                            applyInnerOutline(inner);
                            inner.textContent = `Web Embed: ${p.url || 'Paste URL in Inspector'}`;
                            box.appendChild(inner);
                        } else {
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;border-radius:${layerRadiusCss};background:${p.fill_color || `color-mix(in oklab, ${surface} 82%, transparent)`};pointer-events:none;`;
                            if (p.backdrop_blur !== undefined && p.backdrop_blur !== '') {
                                inner.style.backdropFilter = `blur(${Number(p.backdrop_blur)}px)`;
                            }
                            applyInnerOutline(inner, `1px solid ${p.border_color || glassBorder}`);
                            box.appendChild(inner);
                        }
                        if (!isSelected) {
                            box.style.borderColor = 'rgba(255, 255, 255, 0.14)';
                        }

                        const tag = document.createElement('div');
                        tag.className = 'canvas-tag';
                        tag.textContent = layer.name || layer.type;
                        const handle = document.createElement('div');
                        handle.className = 'resize-handle';
                        box.append(tag, handle);

                        box.addEventListener('pointerdown', (ev) => {
                            ev.stopPropagation();
                            selectedLayerId = layer.id;
                            renderLayerStack();
                            populateLayerInspector();
                            document.querySelectorAll('.canvas-layer').forEach((el) => el.classList.remove('is-selected'));
                            box.classList.add('is-selected');
                            if (layer.locked) return;

                            const isResize = ev.target === handle;
                            const scale = wrap.getBoundingClientRect().width / wLogical;
                            const startX = ev.clientX;
                            const startY = ev.clientY;
                            const init = { x: Number(layer.x || 0), y: Number(layer.y || 0), w: Number(layer.w || 200), h: Number(layer.h || 80) };
                            const preDragSnap = captureViewSnapshot(view);
                            let movedDuringDrag = false;
                            dragActive = true;
                            box.setPointerCapture(ev.pointerId);

                            const onMove = (mEv) => {
                                const dx = (mEv.clientX - startX) / scale;
                                const dy = (mEv.clientY - startY) / scale;
                                if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5) {
                                    movedDuringDrag = true;
                                }
                                if (isResize) {
                                    const lockSquare = layer.type === 'color_box'
                                        && ['square', 'circle'].includes(layer.props?.shape_preset || '');
                                    if (lockSquare) {
                                        const nextSide = Math.max(40, Math.round(Math.max(init.w + dx, init.h + dy)));
                                        layer.w = nextSide;
                                        layer.h = nextSide;
                                    } else {
                                        layer.w = Math.max(40, Math.round(init.w + dx));
                                        layer.h = Math.max(24, Math.round(init.h + dy));
                                    }
                                    box.style.width = `${layer.w}px`;
                                    box.style.height = `${layer.h}px`;
                                    document.getElementById('lp-w').value = String(layer.w);
                                    document.getElementById('lp-h').value = String(layer.h);
                                } else {
                                    layer.x = Math.round(init.x + dx);
                                    layer.y = Math.round(init.y + dy);
                                    box.style.left = `${layer.x}px`;
                                    box.style.top = `${layer.y}px`;
                                    document.getElementById('lp-x').value = String(layer.x);
                                    document.getElementById('lp-y').value = String(layer.y);
                                }
                                setDirty(true);
                            };
                            const onUp = () => {
                                dragActive = false;
                                if (movedDuringDrag) {
                                    pushUndoSnapshot(preDragSnap);
                                }
                                box.removeEventListener('pointermove', onMove);
                                box.removeEventListener('pointerup', onUp);
                                box.removeEventListener('pointercancel', onUp);
                            };
                            box.addEventListener('pointermove', onMove);
                            box.addEventListener('pointerup', onUp);
                            box.addEventListener('pointercancel', onUp);
                        });

                        logical.appendChild(box);
                    });

                    lastEffectiveBgLabel = effectiveBg.label;
                    updateCanvasScale();
                };

                const renderViewsList = () => {
                    const list = document.getElementById('views-list');
                    const views = colData.views || [];
                    document.getElementById('views-count-meta').textContent = `${views.length} view${views.length === 1 ? '' : 's'}`;

                    list.innerHTML = '';
                    views.forEach((v) => {
                        const isLive = Number(v.id) === Number(colData.active_view_id);
                        const row = document.createElement('div');
                        row.className = `item-row${Number(v.id) === Number(selectedViewId) ? ' is-selected' : ''}`;
                        const top = document.createElement('div');
                        top.className = 'row-top';

                        const selBtn = document.createElement('button');
                        selBtn.type = 'button';
                        selBtn.className = 'row-btn';
                        selBtn.textContent = v.name;
                        if (isLive) {
                            const dot = document.createElement('span');
                            dot.style.cssText = 'display:inline-block;width:7px;height:7px;border-radius:50%;background:#4CC38A;margin-right:6px;vertical-align:middle;';
                            selBtn.prepend(dot);
                        }
                        selBtn.addEventListener('click', () => {
                            selectedViewId = Number(v.id);
                            selectedLayerId = null;
                            renderAll();
                        });

                        const actions = document.createElement('div');
                        actions.style.cssText = 'display:flex;gap:0.25rem;';

                        const dupBtn = document.createElement('button');
                        dupBtn.type = 'button';
                        dupBtn.className = 's-btn s-btn-xs';
                        dupBtn.textContent = 'Copy';
                        dupBtn.addEventListener('click', async () => {
                            const res = await apiJson(endpoints.duplicateViewTpl.replace('__VIEW__', v.id), 'POST', {});
                            colData = res.collection;
                            selectedViewId = Number(res.view_id);
                            selectedLayerId = null;
                            renderAll();
                            showToast('View duplicated');
                        });

                        const delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 's-btn s-btn-xs s-btn-danger';
                        delBtn.textContent = '×';
                        delBtn.disabled = views.length <= 1;
                        delBtn.addEventListener('click', async () => {
                            const ok = await confirmBuilderAction({
                                title: 'Delete Screen View',
                                message: `Permanently delete view "${v.name}"? Any uploaded files used exclusively by this view will also be removed from server storage.`,
                                confirmLabel: 'Delete View',
                            });
                            if (!ok) return;
                            const res = await apiJson(endpoints.deleteViewTpl.replace('__VIEW__', v.id), 'DELETE', {});
                            colData = res.collection;
                            selectedViewId = Number(colData.active_view_id || colData.views?.[0]?.id || 0);
                            selectedLayerId = null;
                            renderAll();
                            showToast('View deleted');
                        });

                        actions.append(dupBtn, delBtn);
                        top.append(selBtn, actions);
                        row.appendChild(top);
                        list.appendChild(row);
                    });
                };

                const renderAssetsList = () => {
                    const list = document.getElementById('assets-list');
                    const stingerSel = document.getElementById('col-tx-stinger');
                    const bgAssetSel = document.getElementById('vp-bg-asset');
                    const colBgAssetSel = document.getElementById('col-bg-asset');
                    const assets = colData.assets || [];

                    if (stingerSel) {
                        stingerSel.innerHTML = '<option value="">None</option>';
                        assets.filter((a) => ['stinger', 'video'].includes(a.type)).forEach((a) => {
                            const o = document.createElement('option');
                            o.value = String(a.id);
                            o.textContent = `${a.name} (${a.type})`;
                            stingerSel.appendChild(o);
                        });
                        stingerSel.value = String(colData.default_transition?.stinger_asset_id || '');
                    }

                    const visualAssets = assets.filter((a) => ['video', 'image', 'stinger'].includes(a.type));

                    if (colBgAssetSel) {
                        colBgAssetSel.innerHTML = '<option value="">Select uploaded video or image...</option>';
                        visualAssets.forEach((a) => {
                            const o = document.createElement('option');
                            o.value = String(a.id);
                            o.textContent = `${a.name} (${a.type})`;
                            colBgAssetSel.appendChild(o);
                        });
                        colBgAssetSel.value = String(colData.theme?.background_asset_id || '');
                    }

                    if (bgAssetSel) {
                        const curView = getCurrentView();
                        bgAssetSel.innerHTML = '<option value="">None</option>';
                        visualAssets.forEach((a) => {
                            const o = document.createElement('option');
                            o.value = String(a.id);
                            o.textContent = `${a.name} (${a.type})`;
                            bgAssetSel.appendChild(o);
                        });
                        if (curView) {
                            bgAssetSel.value = String(curView.background_asset_id || '');
                        }
                    }

                    const cleanUnusedBtn = document.getElementById('btn-clean-unused-assets');
                    const unusedCount = assets.filter((a) => a.in_use === false).length;
                    if (cleanUnusedBtn) {
                        cleanUnusedBtn.textContent = unusedCount > 0 ? `Clean Unused (${unusedCount})` : 'Clean Unused';
                    }

                    list.innerHTML = assets.length === 0 ? '<div class="empty-hint">Upload images, video loops, or .webm stingers above.</div>' : '';
                    assets.forEach((a) => {
                        const row = document.createElement('div');
                        row.className = 'item-row';
                        const top = document.createElement('div');
                        top.className = 'row-top';

                        let thumb;
                        if (a.type === 'image' && a.url) {
                            thumb = document.createElement('img');
                            thumb.className = 'asset-thumb';
                            thumb.src = a.url;
                            thumb.alt = a.name;
                        } else {
                            thumb = document.createElement('span');
                            thumb.className = 'asset-thumb';
                            thumb.textContent = String(a.type || 'file').toUpperCase().slice(0, 4);
                        }

                        const lbl = document.createElement('span');
                        lbl.className = 'row-btn';
                        lbl.style.cursor = 'default';
                        lbl.textContent = a.name;

                        const btns = document.createElement('div');
                        btns.style.cssText = 'display:flex;align-items:center;gap:0.2rem;';

                        if (a.in_use === false) {
                            const unusedTag = document.createElement('span');
                            unusedTag.className = 'unused-tag';
                            unusedTag.textContent = 'Unused';
                            btns.appendChild(unusedTag);
                        }

                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 's-btn s-btn-xs';
                        addBtn.textContent = '+ Add';
                        addBtn.addEventListener('click', () => {
                            const view = getCurrentView();
                            if (!view) return;
                            const id = 'lyr_' + Math.random().toString(36).slice(2, 10);
                            view.layers = view.layers || [];
                            view.layers.push({
                                id,
                                type: 'asset',
                                name: a.name,
                                x: 160,
                                y: 120,
                                w: 480,
                                h: 270,
                                rotation: 0,
                                z_index: view.layers.length + 1,
                                opacity: 1,
                                visible: true,
                                locked: false,
                                radius: 12,
                                enter_animation: 'fade',
                                enter_duration_ms: 350,
                                enter_delay_ms: 0,
                                loop_animation: 'none',
                                condition_tag: 'always',
                                props: { asset_id: a.id, url: a.url, media_kind: a.type === 'video' || a.type === 'stinger' ? 'video' : 'image', fit: 'contain', loop: true, muted: true },
                            });
                            selectedLayerId = id;
                            setDirty(true);
                            renderCanvas();
                            renderLayerStack();
                            populateLayerInspector();
                        });

                        const rmBtn = document.createElement('button');
                        rmBtn.type = 'button';
                        rmBtn.className = 's-btn s-btn-xs s-btn-danger';
                        rmBtn.textContent = '×';
                        rmBtn.addEventListener('click', async () => {
                            const ok = await confirmBuilderAction({
                                title: 'Delete Asset File',
                                message: `Permanently delete "${a.name}" from server storage? This cannot be undone.`,
                                confirmLabel: 'Delete File',
                            });
                            if (!ok) return;
                            await apiJson(endpoints.deleteAssetTpl.replace('__ASSET__', a.id), 'DELETE', {});
                            colData.assets = (colData.assets || []).filter((x) => x.id !== a.id);
                            if (Number(colData.theme?.background_asset_id) === Number(a.id)) {
                                colData.theme.background_asset_id = null;
                                colData.theme.background_asset_url = null;
                                colData.theme.background_asset_type = null;
                            }
                            (colData.views || []).forEach((v) => {
                                if (Number(v.background_asset_id) === Number(a.id)) {
                                    v.background_asset_id = null;
                                    v.background_asset_url = null;
                                    v.background_asset_type = null;
                                }
                            });
                            renderAssetsList();
                            renderCanvas();
                            showToast('Asset deleted from server');
                        });

                        btns.append(addBtn, rmBtn);
                        top.append(thumb, lbl, btns);
                        row.appendChild(top);
                        list.appendChild(row);
                    });
                };

                const renderLayerStack = () => {
                    const list = document.getElementById('view-layers-list');
                    const view = getCurrentView();
                    const layers = [...(view?.layers || [])].sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0)).reverse();
                    document.getElementById('layer-stack-count').textContent = `${layers.length} layer${layers.length === 1 ? '' : 's'}`;
                    list.innerHTML = '';

                    layers.forEach((layer) => {
                        const row = document.createElement('div');
                        row.className = `item-row${String(layer.id) === String(selectedLayerId) ? ' is-selected' : ''}`;
                        const top = document.createElement('div');
                        top.className = 'row-top';

                        const selBtn = document.createElement('button');
                        selBtn.type = 'button';
                        selBtn.className = 'row-btn';
                        selBtn.textContent = `${layer.name || layer.type}`;
                        selBtn.addEventListener('click', () => {
                            selectedLayerId = layer.id;
                            renderCanvas();
                            renderLayerStack();
                            populateLayerInspector();
                        });

                        const moveLayerOrder = (delta) => {
                            const sortedAsc = [...(view?.layers || [])].sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0));
                            const curIdx = sortedAsc.findIndex((l) => String(l.id) === String(layer.id));
                            const targetIdx = curIdx + delta;
                            if (curIdx < 0 || targetIdx < 0 || targetIdx >= sortedAsc.length) return;
                            const [moved] = sortedAsc.splice(curIdx, 1);
                            sortedAsc.splice(targetIdx, 0, moved);
                            sortedAsc.forEach((l, i) => { l.z_index = i + 1; });
                            setDirty(true);
                            renderCanvas();
                            renderLayerStack();
                        };

                        const upBtn = document.createElement('button');
                        upBtn.type = 'button';
                        upBtn.className = 's-btn s-btn-xs';
                        upBtn.textContent = '↑';
                        upBtn.title = 'Bring forward';
                        upBtn.addEventListener('click', () => moveLayerOrder(1));

                        const downBtn = document.createElement('button');
                        downBtn.type = 'button';
                        downBtn.className = 's-btn s-btn-xs';
                        downBtn.textContent = '↓';
                        downBtn.title = 'Send backward';
                        downBtn.addEventListener('click', () => moveLayerOrder(-1));

                        const visBtn = document.createElement('button');
                        visBtn.type = 'button';
                        visBtn.className = `s-btn s-btn-xs${layer.visible !== false ? ' is-active' : ''}`;
                        visBtn.textContent = layer.visible !== false ? 'On' : 'Off';
                        visBtn.addEventListener('click', () => {
                            layer.visible = layer.visible === false;
                            setDirty(true);
                            renderCanvas();
                            renderLayerStack();
                        });

                        const lockBtn = document.createElement('button');
                        lockBtn.type = 'button';
                        lockBtn.className = `s-btn s-btn-xs${layer.locked ? ' is-active' : ''}`;
                        lockBtn.textContent = layer.locked ? 'Lock' : 'Free';
                        lockBtn.addEventListener('click', () => {
                            layer.locked = !layer.locked;
                            setDirty(true);
                            renderCanvas();
                            renderLayerStack();
                        });

                        top.append(selBtn, upBtn, downBtn, visBtn, lockBtn);
                        row.appendChild(top);
                        list.appendChild(row);
                    });
                };

                const populateLayerInspector = () => {
                    const card = document.getElementById('layer-props-card');
                    const hint = document.getElementById('no-layer-hint');
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) {
                        card.hidden = true;
                        if (hint) hint.hidden = false;
                        return;
                    }
                    card.hidden = false;
                    if (hint) hint.hidden = true;

                    const bind = (id, val, onChange) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.value = val ?? '';
                        el.oninput = el.onchange = () => {
                            recordDebouncedUndo(`lp:${id}:${layer.id}`);
                            onChange(el.value);
                            setDirty(true);
                            renderCanvas();
                        };
                    };

                    bind('lp-name', layer.name, (v) => { layer.name = v; renderLayerStack(); });
                    bind('lp-opacity', layer.opacity ?? 1, (v) => { layer.opacity = Number(v); });
                    bind('lp-x', layer.x ?? 0, (v) => { layer.x = Number(v); });
                    bind('lp-y', layer.y ?? 0, (v) => { layer.y = Number(v); });
                    bind('lp-w', layer.w ?? 200, (v) => { layer.w = Math.max(16, Number(v)); });
                    bind('lp-h', layer.h ?? 80, (v) => { layer.h = Math.max(16, Number(v)); });
                    bind('lp-rot', layer.rotation ?? 0, (v) => { layer.rotation = Number(v); });
                    bind('lp-radius', layer.radius ?? 0, (v) => { layer.radius = Math.max(0, Number(v)); });
                    bind('lp-enter-anim', layer.enter_animation || 'fade', (v) => { layer.enter_animation = v; });
                    bind('lp-loop-anim', layer.loop_animation || 'none', (v) => { layer.loop_animation = v; });
                    bind('lp-condition', layer.condition_tag || 'always', (v) => { layer.condition_tag = v; });

                    const extra = document.getElementById('layer-type-fields');
                    extra.innerHTML = '';
                    layer.props = layer.props || {};
                    const activeTheme = getMergedViewTheme(view);

                    const addExtra = (label, inputEl, parent = extra) => {
                        const w = document.createElement('label');
                        w.className = 'stack';
                        const s = document.createElement('span');
                        s.className = 'sub-label';
                        s.textContent = label;
                        w.append(s, inputEl);
                        parent.appendChild(w);
                    };

                    const makeGrid2 = () => {
                        const g = document.createElement('div');
                        g.className = 'grid-2';
                        extra.appendChild(g);
                        return g;
                    };

                    const makeColorField = (currentVal, fallbackHex, placeholder, onChange, labelForAria = 'Color') => {
                        const widget = createColorOpacityWidget({
                            initialValue: currentVal || '',
                            fallbackHex: fallbackHex || '#CDAD69',
                            placeholder: placeholder || 'Inherit',
                            allowClear: true,
                            labelForAria,
                            onChange: (val) => {
                                recordDebouncedUndo(`color:${labelForAria}:${layer.id}`);
                                onChange(val);
                                setDirty(true);
                                renderCanvas();
                            },
                        });
                        return widget.element;
                    };

                    const makeNumberField = (val, { min = 0, max = 500, step = 1, fallback = 0 } = {}, onChange) => {
                        const inp = document.createElement('input');
                        inp.type = 'number';
                        inp.className = 's-input';
                        inp.min = String(min);
                        inp.max = String(max);
                        inp.step = String(step);
                        inp.value = val ?? fallback;
                        inp.oninput = inp.onchange = () => {
                            recordDebouncedUndo(`num:${min}:${max}:${layer.id}`);
                            onChange(Number(inp.value === '' ? fallback : inp.value));
                            setDirty(true);
                            renderCanvas();
                        };
                        return inp;
                    };

                    const makeSelectField = (options, currentVal, onChange) => {
                        const sel = document.createElement('select');
                        sel.className = 's-select';
                        options.forEach(([v, lbl]) => {
                            const o = document.createElement('option');
                            o.value = String(v);
                            o.textContent = lbl;
                            sel.appendChild(o);
                        });
                        sel.value = String(currentVal ?? options[0]?.[0] ?? '');
                        sel.onchange = () => {
                            pushUndoSnapshot();
                            onChange(sel.value);
                            setDirty(true);
                            renderCanvas();
                        };
                        return sel;
                    };

                    const makeToggleField = (labelText, checked, onChange) => {
                        const wrap = document.createElement('label');
                        wrap.className = 'toggle-row';
                        const span = document.createElement('span');
                        span.textContent = labelText;
                        const chk = document.createElement('input');
                        chk.type = 'checkbox';
                        chk.checked = Boolean(checked);
                        chk.onchange = () => {
                            pushUndoSnapshot();
                            onChange(chk.checked);
                            setDirty(true);
                            renderCanvas();
                        };
                        wrap.append(span, chk);
                        extra.appendChild(wrap);
                        return chk;
                    };

                    const addOutlineControls = (defaultWidth = 0, allowStyle = false) => {
                        addExtra('Outline Color', makeColorField(
                            layer.props.border_color || '',
                            activeTheme.accent_color || '#CDAD69',
                            'Default / None',
                            (v) => { layer.props.border_color = v; },
                            'Outline Color'
                        ));
                        const g = makeGrid2();
                        addExtra('Outline Thickness (px)', makeNumberField(
                            layer.props.border_width ?? defaultWidth,
                            { min: 0, max: 40, step: 1, fallback: defaultWidth },
                            (v) => { layer.props.border_width = Math.max(0, v); }
                        ), g);
                        addExtra('Outline Direction', makeSelectField(
                            [['inside', 'Inside Shape'], ['outside', 'Outside Shape'], ['center', 'Center (Half In / Out)']],
                            layer.props.border_position || 'inside',
                            (v) => { layer.props.border_position = v; }
                        ), g);
                        if (allowStyle) {
                            addExtra('Outline Style', makeSelectField(
                                [['solid', 'Solid'], ['dashed', 'Dashed'], ['dotted', 'Dotted']],
                                layer.props.border_style || 'solid',
                                (v) => { layer.props.border_style = v; }
                            ));
                        }
                    };

                    if (layer.type === 'text') {
                        const ta = document.createElement('textarea');
                        ta.className = 's-textarea';
                        ta.rows = 2;
                        ta.value = layer.props.text || '';
                        ta.oninput = () => {
                            recordDebouncedUndo(`text:${layer.id}`);
                            layer.props.text = ta.value;
                            setDirty(true);
                            renderCanvas();
                        };
                        addExtra('Text Content', ta);

                        const g1 = makeGrid2();
                        addExtra('Font Size (px)', makeNumberField(layer.props.font_size ?? 48, { min: 10, max: 320, step: 2, fallback: 48 }, (v) => { layer.props.font_size = Math.max(10, v); }), g1);
                        addExtra('Font Weight', makeSelectField(
                            [['400', 'Regular (400)'], ['500', 'Medium (500)'], ['600', 'SemiBold (600)'], ['700', 'Bold (700)'], ['800', 'ExtraBold (800)']],
                            String(layer.props.font_weight || 700),
                            (v) => { layer.props.font_weight = Number(v); }
                        ), g1);

                        const g2 = makeGrid2();
                        addExtra('Font Family', makeSelectField(
                            FONT_FAMILY_OPTIONS,
                            layer.props.font_family || '',
                            (v) => { layer.props.font_family = v; }
                        ), g2);
                        addExtra('Alignment', makeSelectField(
                            [['left', 'Left'], ['center', 'Center'], ['right', 'Right']],
                            layer.props.align || 'center',
                            (v) => { layer.props.align = v; }
                        ), g2);

                        const g3 = makeGrid2();
                        addExtra('Padding (px)', makeNumberField(layer.props.padding ?? 12, { min: 0, max: 120, step: 2, fallback: 12 }, (v) => { layer.props.padding = Math.max(0, v); }), g3);
                        addExtra('Letter Spacing (px)', makeNumberField(layer.props.letter_spacing ?? 0, { min: -4, max: 24, step: 0.5, fallback: 0 }, (v) => { layer.props.letter_spacing = v; }), g3);

                        addExtra('Font Color', makeColorField(layer.props.text_color || '', activeTheme.text_color || '#FAF7EE', 'Inherit Theme Text', (v) => { layer.props.text_color = v; }, 'Font Color'));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Transparent', (v) => { layer.props.bg_color = v; }, 'Background Color'));
                        addOutlineControls(0, true);
                        makeToggleField('Text Drop Shadow', Boolean(layer.props.text_shadow), (v) => { layer.props.text_shadow = v; });
                    } else if (layer.type === 'timer') {
                        addExtra('Timer Mode', makeSelectField(
                            [['countdown', 'Countdown'], ['countup', 'Stopwatch (Count Up)'], ['clock', 'Local Wall Clock']],
                            layer.props.mode || 'countdown',
                            (v) => { layer.props.mode = v; }
                        ));

                        const lbl = document.createElement('input');
                        lbl.className = 's-input';
                        lbl.value = layer.props.label || 'LIVE IN';
                        lbl.oninput = () => {
                            recordDebouncedUndo(`timer-label:${layer.id}`);
                            layer.props.label = lbl.value;
                            colData.runtime_state = colData.runtime_state || {};
                            colData.runtime_state.timer = { ...(colData.runtime_state.timer || {}), label: lbl.value };
                            runtimeDirty = true;
                            setDirty(true);
                            renderCanvas();
                        };
                        addExtra('Timer Label', lbl);

                        const rtTimer = colData.runtime_state?.timer || {};
                        const timerLiveBox = document.createElement('div');
                        timerLiveBox.className = 'live-content-box';
                        const tlHead = document.createElement('div');
                        tlHead.className = 'live-content-head';
                        tlHead.textContent = 'Live Timer Content';
                        const durRow = document.createElement('div');
                        durRow.style.cssText = 'display:grid;grid-template-columns:minmax(0,1fr) auto;gap:0.35rem;align-items:end;';
                        const durInp = document.createElement('input');
                        durInp.type = 'number';
                        durInp.className = 's-input';
                        durInp.min = '0';
                        durInp.step = '30';
                        durInp.value = String(rtTimer.duration_seconds ?? 300);
                        durInp.oninput = () => {
                            colData.runtime_state = colData.runtime_state || {};
                            colData.runtime_state.timer = { ...(colData.runtime_state.timer || {}), duration_seconds: Math.max(0, Number(durInp.value || 0)) };
                            runtimeDirty = true;
                            setDirty(true);
                            renderCanvas();
                        };
                        const durApply = document.createElement('button');
                        durApply.type = 'button';
                        durApply.className = 's-btn s-btn-xs s-btn-gold';
                        durApply.textContent = 'Apply Live';
                        durApply.onclick = () => pushRuntimePatch({
                            timer: { action: 'set', duration_seconds: Math.max(0, Number(durInp.value || 0)), label: lbl.value.trim(), mode: layer.props.mode || 'countdown' },
                        }, 'Live timer duration & label synced');
                        durRow.append(durInp, durApply);
                        const durField = document.createElement('label');
                        durField.className = 'stack';
                        durField.innerHTML = '<span class="sub-label">Countdown Duration (seconds)</span>';
                        durField.appendChild(durRow);
                        timerLiveBox.append(tlHead, durField);
                        extra.appendChild(timerLiveBox);

                        const endActionFieldset = document.createElement('fieldset');
                        endActionFieldset.className = 'live-content-box';
                        const endActionLegend = document.createElement('legend');
                        endActionLegend.textContent = 'When Timer Ends (00:00)';
                        const endActionHost = document.createElement('div');
                        endActionHost.className = 'stack';
                        endActionFieldset.append(endActionLegend, endActionHost);
                        renderTimerEndActionEditor(endActionHost, view, {
                            idPrefix: 'timer-block-end',
                            showApplyBtn: true,
                            onUpdated: () => {
                                const vpHost = document.getElementById('vp-logic-editor-host');
                                if (vpHost) renderTimerEndActionEditor(vpHost, view, { idPrefix: 'vp-logic', showApplyBtn: false });
                            },
                        });
                        extra.appendChild(endActionFieldset);

                        addExtra('Font Family', makeSelectField(
                            [['', 'Default Mono (JetBrains Mono)'], ...Object.entries(FONT_FAMILY_MAP)],
                            layer.props.font_family || '',
                            (v) => { layer.props.font_family = v; }
                        ));

                        const g1 = makeGrid2();
                        addExtra('Digit Size (px)', makeNumberField(layer.props.font_size ?? 72, { min: 18, max: 260, step: 2, fallback: 72 }, (v) => { layer.props.font_size = Math.max(18, v); }), g1);
                        addExtra('Label Size (px)', makeNumberField(layer.props.label_font_size ?? 20, { min: 10, max: 80, step: 1, fallback: 20 }, (v) => { layer.props.label_font_size = Math.max(10, v); }), g1);

                        addExtra('Digit Font Color', makeColorField(layer.props.text_color || '', activeTheme.accent_color || '#CDAD69', 'Inherit Accent', (v) => { layer.props.text_color = v; }, 'Digit Font Color'));
                        addExtra('Label Font Color', makeColorField(layer.props.label_color || '', activeTheme.muted_color || '#ABA291', 'Inherit Muted', (v) => { layer.props.label_color = v; }, 'Label Font Color'));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Inherit Surface Glass', (v) => { layer.props.bg_color = v; }, 'Background Color'));
                        addOutlineControls(1, true);
                        makeToggleField('Show Card Background', layer.props.show_bg !== false, (v) => { layer.props.show_bg = v; });
                        makeToggleField('Show Hours (HH:MM:SS)', Boolean(layer.props.show_hours), (v) => { layer.props.show_hours = v; });
                    } else if (layer.type === 'color_box') {
                        const g1 = makeGrid2();
                        const shapeSel = makeSelectField(
                            [['rectangle', 'Rectangle'], ['square', 'Square'], ['circle', 'Circle']],
                            layer.props.shape_preset || 'rectangle',
                            (v) => {
                                layer.props.shape_preset = v;
                                if (v === 'square' || v === 'circle') {
                                    const side = Math.max(40, Math.round(Math.min(Number(layer.w || 240), Number(layer.h || 240))));
                                    layer.w = side;
                                    layer.h = side;
                                    document.getElementById('lp-w').value = String(side);
                                    document.getElementById('lp-h').value = String(side);
                                }
                                populateLayerInspector();
                            }
                        );
                        addExtra('Box Shape', shapeSel, g1);

                        const fillModeSel = makeSelectField(
                            [['solid', 'Solid Color'], ['gradient', 'Gradient'], ['transparent', 'Outline Only']],
                            layer.props.fill_mode || 'solid',
                            (v) => {
                                layer.props.fill_mode = v;
                                populateLayerInspector();
                            }
                        );
                        addExtra('Fill Style', fillModeSel, g1);

                        if ((layer.props.fill_mode || 'solid') !== 'transparent') {
                            addExtra('Box Color', makeColorField(
                                layer.props.fill_color || activeTheme.accent_color || '#CDAD69',
                                activeTheme.accent_color || '#CDAD69',
                                '#CDAD69',
                                (v) => { layer.props.fill_color = v; }
                            ));
                        }

                        if (layer.props.fill_mode === 'gradient') {
                            addExtra('Gradient Second Color', makeColorField(
                                layer.props.fill_color_end || activeTheme.surface_color || '#181714',
                                activeTheme.surface_color || '#181714',
                                '#181714',
                                (v) => { layer.props.fill_color_end = v; }
                            ));
                            addExtra('Gradient Angle (°)', makeNumberField(
                                layer.props.gradient_angle ?? 135,
                                { min: 0, max: 360, step: 15, fallback: 135 },
                                (v) => { layer.props.gradient_angle = v; }
                            ));
                        }

                        if ((layer.props.shape_preset || 'rectangle') !== 'circle') {
                            const radRow = document.createElement('div');
                            radRow.style.cssText = 'display:flex;gap:0.3rem;align-items:center;';
                            const radSlider = document.createElement('input');
                            radSlider.type = 'range';
                            radSlider.min = '0';
                            radSlider.max = '240';
                            radSlider.value = String(Number(layer.radius || 0));
                            radSlider.style.cssText = 'flex:1;accent-color:var(--gold-accent);';
                            const radVal = document.createElement('span');
                            radVal.className = 'sub-label';
                            radVal.style.minInlineSize = '2.75rem';
                            radVal.style.textAlign = 'end';
                            radVal.textContent = `${Number(layer.radius || 0)}px`;
                            radSlider.oninput = () => {
                                layer.radius = Number(radSlider.value || 0);
                                radVal.textContent = `${layer.radius}px`;
                                document.getElementById('lp-radius').value = String(layer.radius);
                                setDirty(true);
                                renderCanvas();
                            };
                            radRow.append(radSlider, radVal);
                            addExtra('Rounded Edges', radRow);
                        }

                        addOutlineControls(0, true);
                        makeToggleField('Drop Shadow', Boolean(layer.props.shadow), (v) => { layer.props.shadow = v; });
                    } else if (layer.type === 'scoreboard' || layer.type === 'lower_third' || layer.type === 'ticker') {
                        colData.runtime_state = colData.runtime_state || {};
                        const liveBox = document.createElement('div');
                        liveBox.className = 'live-content-box';
                        const liveHead = document.createElement('div');
                        liveHead.className = 'live-content-head';

                        if (layer.type === 'scoreboard') {
                            colData.runtime_state.scoreboard = {
                                team_a_name: 'Team Alpha',
                                team_b_name: 'Team Omega',
                                team_a_score: 0,
                                team_b_score: 0,
                                match_label: '',
                                round_label: 'VS',
                                ...(colData.runtime_state.scoreboard || {}),
                            };
                            const sb = colData.runtime_state.scoreboard;
                            liveHead.innerHTML = '<span>Live Scoreboard Content</span>';
                            const applyBtn = document.createElement('button');
                            applyBtn.type = 'button';
                            applyBtn.className = 's-btn s-btn-xs s-btn-gold';
                            applyBtn.textContent = 'Push Live';
                            applyBtn.onclick = () => pushRuntimePatch({
                                scoreboard: {
                                    team_a_name: sb.team_a_name,
                                    team_b_name: sb.team_b_name,
                                    team_a_score: Number(sb.team_a_score || 0),
                                    team_b_score: Number(sb.team_b_score || 0),
                                    match_label: sb.match_label || '',
                                    round_label: sb.round_label || '',
                                },
                            }, 'Live scoreboard content updated');
                            liveHead.appendChild(applyBtn);
                            liveBox.appendChild(liveHead);

                            const makeRtText = (lbl, val, onChg, parentEl) => {
                                const inp = document.createElement('input');
                                inp.type = 'text';
                                inp.className = 's-input';
                                inp.value = val ?? '';
                                inp.oninput = () => { onChg(inp.value); runtimeDirty = true; setDirty(true); renderCanvas(); };
                                addExtra(lbl, inp, parentEl);
                            };
                            const makeRtNum = (lbl, val, onChg, parentEl) => {
                                const inp = document.createElement('input');
                                inp.type = 'number';
                                inp.className = 's-input';
                                inp.min = '0';
                                inp.value = String(val ?? 0);
                                inp.oninput = () => { onChg(Math.max(0, Number(inp.value || 0))); runtimeDirty = true; setDirty(true); renderCanvas(); };
                                addExtra(lbl, inp, parentEl);
                            };
                            const rowA = document.createElement('div');
                            rowA.className = 'grid-2';
                            makeRtText('Team A Name', sb.team_a_name, (v) => { sb.team_a_name = v; }, rowA);
                            makeRtNum('Team A Score', sb.team_a_score, (v) => { sb.team_a_score = v; }, rowA);
                            const rowB = document.createElement('div');
                            rowB.className = 'grid-2';
                            makeRtText('Team B Name', sb.team_b_name, (v) => { sb.team_b_name = v; }, rowB);
                            makeRtNum('Team B Score', sb.team_b_score, (v) => { sb.team_b_score = v; }, rowB);
                            const rowM = document.createElement('div');
                            rowM.className = 'grid-2';
                            makeRtText('Match Title', sb.match_label, (v) => { sb.match_label = v; }, rowM);
                            makeRtText('Round / Center', sb.round_label, (v) => { sb.round_label = v; }, rowM);
                            liveBox.append(rowA, rowB, rowM);
                        } else if (layer.type === 'lower_third') {
                            colData.runtime_state.lower_third = {
                                visible: true,
                                badge: 'LIVE',
                                title: 'Broadcast Host',
                                subtitle: 'Live From Studio',
                                ...(colData.runtime_state.lower_third || {}),
                            };
                            const l3 = colData.runtime_state.lower_third;
                            liveHead.innerHTML = '<span>Live Lower Third Content</span>';
                            const applyBtn = document.createElement('button');
                            applyBtn.type = 'button';
                            applyBtn.className = 's-btn s-btn-xs s-btn-gold';
                            applyBtn.textContent = 'Push Live';
                            applyBtn.onclick = () => pushRuntimePatch({
                                lower_third: {
                                    visible: Boolean(l3.visible),
                                    badge: l3.badge || 'LIVE',
                                    title: l3.title || '',
                                    subtitle: l3.subtitle || '',
                                },
                            }, 'Live Lower Third content updated');
                            liveHead.appendChild(applyBtn);
                            liveBox.appendChild(liveHead);

                            const badgeInp = document.createElement('input');
                            badgeInp.className = 's-input';
                            badgeInp.value = l3.badge || 'LIVE';
                            badgeInp.oninput = () => { l3.badge = badgeInp.value; runtimeDirty = true; setDirty(true); renderCanvas(); };
                            addExtra('Badge Text', badgeInp, liveBox);

                            const titleInp = document.createElement('input');
                            titleInp.className = 's-input';
                            titleInp.value = l3.title || '';
                            titleInp.placeholder = 'Speaker / Headline';
                            titleInp.oninput = () => { l3.title = titleInp.value; runtimeDirty = true; setDirty(true); renderCanvas(); };
                            addExtra('Headline / Speaker', titleInp, liveBox);

                            const subInp = document.createElement('input');
                            subInp.className = 's-input';
                            subInp.value = l3.subtitle || '';
                            subInp.placeholder = 'Role / Subtitle';
                            subInp.oninput = () => { l3.subtitle = subInp.value; runtimeDirty = true; setDirty(true); renderCanvas(); };
                            addExtra('Subtitle / Role', subInp, liveBox);

                            const visWrap = document.createElement('label');
                            visWrap.className = 'toggle-row';
                            visWrap.innerHTML = '<span>Visible On Air</span>';
                            const visChk = document.createElement('input');
                            visChk.type = 'checkbox';
                            visChk.checked = Boolean(l3.visible);
                            visChk.onchange = () => { l3.visible = visChk.checked; runtimeDirty = true; setDirty(true); };
                            visWrap.appendChild(visChk);
                            liveBox.appendChild(visWrap);
                        } else if (layer.type === 'ticker') {
                            colData.runtime_state.ticker = {
                                visible: true,
                                label: 'LIVE',
                                items: ['Welcome to the live broadcast!'],
                                ...(colData.runtime_state.ticker || {}),
                            };
                            const tk = colData.runtime_state.ticker;
                            liveHead.innerHTML = '<span>Live Ticker Content</span>';
                            const applyBtn = document.createElement('button');
                            applyBtn.type = 'button';
                            applyBtn.className = 's-btn s-btn-xs s-btn-gold';
                            applyBtn.textContent = 'Push Live';
                            applyBtn.onclick = () => pushRuntimePatch({
                                ticker: {
                                    visible: tk.visible !== false,
                                    label: tk.label || 'LIVE',
                                    items: Array.isArray(tk.items) ? tk.items : [],
                                },
                            }, 'Live Ticker content updated');
                            liveHead.appendChild(applyBtn);
                            liveBox.appendChild(liveHead);

                            const badgeInp = document.createElement('input');
                            badgeInp.className = 's-input';
                            badgeInp.value = tk.label || 'LIVE';
                            badgeInp.placeholder = 'LIVE';
                            badgeInp.oninput = () => { tk.label = badgeInp.value; runtimeDirty = true; setDirty(true); renderCanvas(); };
                            addExtra('Ticker Badge Label', badgeInp, liveBox);

                            const itemsTa = document.createElement('textarea');
                            itemsTa.className = 's-textarea';
                            itemsTa.rows = 3;
                            itemsTa.placeholder = 'One scrolling headline per line...';
                            itemsTa.value = Array.isArray(tk.items) ? tk.items.join('\n') : '';
                            itemsTa.oninput = () => {
                                tk.items = itemsTa.value.split('\n').map((s) => s.trim()).filter(Boolean);
                                runtimeDirty = true;
                                setDirty(true);
                                renderCanvas();
                            };
                            addExtra('Ticker Headlines (one per line)', itemsTa, liveBox);

                            const visWrap = document.createElement('label');
                            visWrap.className = 'toggle-row';
                            visWrap.innerHTML = '<span>Visible On Air</span>';
                            const visChk = document.createElement('input');
                            visChk.type = 'checkbox';
                            visChk.checked = tk.visible !== false;
                            visChk.onchange = () => { tk.visible = visChk.checked; runtimeDirty = true; setDirty(true); };
                            visWrap.appendChild(visChk);
                            liveBox.appendChild(visWrap);
                        }
                        extra.appendChild(liveBox);

                        if (layer.type === 'ticker') {
                            addExtra('Scroll Speed (seconds)', makeNumberField(
                                layer.props.speed_sec ?? 28,
                                { min: 5, max: 120, step: 1, fallback: 28 },
                                (v) => { layer.props.speed_sec = Math.max(5, v); }
                            ));
                        }
                        addExtra('Font Family', makeSelectField(
                            FONT_FAMILY_OPTIONS,
                            layer.props.font_family || '',
                            (v) => { layer.props.font_family = v; }
                        ));
                        addExtra('Text Color', makeColorField(layer.props.text_color || '', activeTheme.text_color || '#FAF7EE', 'Inherit Theme Text', (v) => { layer.props.text_color = v; }, 'Text Color'));
                        addExtra('Accent / Highlight Color', makeColorField(layer.props.accent_color || '', activeTheme.accent_color || '#CDAD69', 'Inherit Theme Accent', (v) => { layer.props.accent_color = v; }, 'Accent Color'));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Inherit Surface Glass', (v) => { layer.props.bg_color = v; }, 'Background Color'));
                        addOutlineControls(1, true);
                    } else if (layer.type === 'shape') {
                        addExtra('Fill / Background Color', makeColorField(layer.props.fill_color || '', activeTheme.surface_color || '#0B0A08', 'Inherit Surface Glass', (v) => { layer.props.fill_color = v; }));
                        addExtra('Backdrop Blur (px)', makeNumberField(
                            layer.props.backdrop_blur ?? 18,
                            { min: 0, max: 60, step: 2, fallback: 18 },
                            (v) => { layer.props.backdrop_blur = Math.max(0, v); }
                        ));
                        addOutlineControls(1, true);
                    } else if (layer.type === 'asset') {
                        const sel = document.createElement('select');
                        sel.className = 's-select';
                        sel.innerHTML = '<option value="">Choose uploaded asset...</option>';
                        (colData.assets || []).forEach((a) => {
                            const o = document.createElement('option');
                            o.value = String(a.id);
                            o.textContent = `${a.name} (${a.type})`;
                            sel.appendChild(o);
                        });
                        sel.value = String(layer.props.asset_id || '');
                        sel.onchange = () => {
                            const found = (colData.assets || []).find((a) => String(a.id) === sel.value);
                            layer.props.asset_id = found ? found.id : null;
                            layer.props.url = found ? found.url : '';
                            layer.props.media_kind = found && ['video', 'stinger'].includes(found.type) ? 'video' : 'image';
                            setDirty(true);
                            renderCanvas();
                        };
                        addExtra('Media Asset', sel);
                        addExtra('Fit Mode', makeSelectField(
                            [['contain', 'Contain (Keep Aspect)'], ['cover', 'Cover (Fill Box)'], ['fill', 'Stretch to Fill']],
                            layer.props.fit || 'contain',
                            (v) => { layer.props.fit = v; }
                        ));
                        addOutlineControls(0, true);
                    } else if (layer.type === 'embed') {
                        const inp = document.createElement('input');
                        inp.type = 'url';
                        inp.className = 's-input';
                        inp.placeholder = 'https://vdo.ninja/?view=...';
                        inp.value = layer.props.url || '';
                        inp.oninput = () => { layer.props.url = inp.value; setDirty(true); renderCanvas(); };
                        addExtra('Widget / VDO.Ninja Embed URL', inp);
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Transparent', (v) => { layer.props.bg_color = v; }));
                        addOutlineControls(0, true);
                    } else if (layer.type === 'camera_cutout') {
                        const lbl = document.createElement('input');
                        lbl.className = 's-input';
                        lbl.value = layer.props.label || '';
                        lbl.placeholder = 'OBS Camera / Capture Shows Here';
                        lbl.oninput = () => { layer.props.label = lbl.value; setDirty(true); renderCanvas(); };
                        addExtra('Cutout Label (Editor Only)', lbl);

                        addExtra('Cutout Border Color', makeColorField(
                            layer.props.border_color || '',
                            activeTheme.accent_color || '#CDAD69',
                            'Inherit Theme Accent',
                            (v) => { layer.props.border_color = v; },
                            'Cutout Border Color'
                        ));
                        const cg = makeGrid2();
                        addExtra('Border Thickness (px)', makeNumberField(
                            layer.props.border_width ?? 3,
                            { min: 0, max: 30, step: 1, fallback: 3 },
                            (v) => { layer.props.border_width = Math.max(0, v); }
                        ), cg);
                        addExtra('Outline Direction', makeSelectField(
                            [['inside', 'Inside Cutout'], ['outside', 'Outside Cutout'], ['center', 'Center (Half In / Out)']],
                            layer.props.border_position || 'inside',
                            (v) => { layer.props.border_position = v; }
                        ), cg);
                        makeToggleField('Accent Border Glow', layer.props.glow !== false, (v) => { layer.props.glow = v; });
                    }
                };

                const normalizeLogicRules = (raw) => {
                    const r = raw && typeof raw === 'object' ? raw : {};
                    return {
                        on_timer_end_action: String(r.on_timer_end_action || 'none'),
                        on_timer_end_target_view_id: r.on_timer_end_target_view_id ? Number(r.on_timer_end_target_view_id) : null,
                        on_timer_end_alert_title: String(r.on_timer_end_alert_title || ''),
                        on_timer_end_alert_message: String(r.on_timer_end_alert_message || ''),
                        on_timer_end_l3_badge: String(r.on_timer_end_l3_badge || ''),
                        on_timer_end_l3_title: String(r.on_timer_end_l3_title || ''),
                        on_timer_end_l3_subtitle: String(r.on_timer_end_l3_subtitle || ''),
                        on_timer_end_obs_scene: String(r.on_timer_end_obs_scene || ''),
                        on_timer_end_macro_id: r.on_timer_end_macro_id ? Number(r.on_timer_end_macro_id) : null,
                    };
                };

                const renderTimerEndActionEditor = (hostEl, view, { idPrefix = 'timer-end', showApplyBtn = true, onUpdated = null } = {}) => {
                    if (!hostEl || !view) return;
                    hostEl.innerHTML = '';
                    view.logic_rules = normalizeLogicRules(view.logic_rules);
                    const rules = view.logic_rules;

                    const makeField = (labelText, controlId, controlEl, hintText = '') => {
                        const wrap = document.createElement('div');
                        wrap.className = 'stack';
                        const lbl = document.createElement('label');
                        lbl.className = 'sub-label';
                        lbl.htmlFor = controlId;
                        lbl.textContent = labelText;
                        controlEl.id = controlId;
                        wrap.append(lbl, controlEl);
                        if (hintText) {
                            const hintId = `${controlId}-hint`;
                            const hint = document.createElement('span');
                            hint.id = hintId;
                            hint.className = 'sub-label';
                            hint.style.opacity = '0.78';
                            hint.textContent = hintText;
                            controlEl.setAttribute('aria-describedby', hintId);
                            wrap.appendChild(hint);
                        }
                        return wrap;
                    };

                    const actionSel = document.createElement('select');
                    actionSel.className = 's-select';
                    actionSel.name = `${idPrefix}_action`;
                    const noneOpt = document.createElement('option');
                    noneOpt.value = 'none';
                    noneOpt.textContent = 'Do Nothing (Stop at 00:00)';
                    actionSel.appendChild(noneOpt);

                    Object.entries(TIMER_END_ACTION_GROUPS).forEach(([groupLabel, options]) => {
                        const og = document.createElement('optgroup');
                        og.label = groupLabel;
                        Object.entries(options || {}).forEach(([key, label]) => {
                            const opt = document.createElement('option');
                            opt.value = key;
                            opt.textContent = label;
                            og.appendChild(opt);
                        });
                        actionSel.appendChild(og);
                    });
                    actionSel.value = rules.on_timer_end_action || 'none';

                    const detailsWrap = document.createElement('div');
                    detailsWrap.className = 'stack';

                    const renderDetails = () => {
                        detailsWrap.innerHTML = '';
                        const act = rules.on_timer_end_action || 'none';

                        if (act === 'switch_view') {
                            const targetSel = document.createElement('select');
                            targetSel.className = 's-select';
                            targetSel.name = `${idPrefix}_target_view_id`;
                            const defOpt = document.createElement('option');
                            defOpt.value = '';
                            defOpt.textContent = 'Select target Screen View...';
                            targetSel.appendChild(defOpt);
                            (colData.views || []).forEach((v) => {
                                const o = document.createElement('option');
                                o.value = String(v.id);
                                o.textContent = Number(v.id) === Number(view.id) ? `${v.name} (current view)` : v.name;
                                if (Number(v.id) === Number(view.id)) o.disabled = true;
                                targetSel.appendChild(o);
                            });
                            targetSel.value = rules.on_timer_end_target_view_id ? String(rules.on_timer_end_target_view_id) : '';
                            targetSel.onchange = () => {
                                pushUndoSnapshot();
                                rules.on_timer_end_target_view_id = targetSel.value ? Number(targetSel.value) : null;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            detailsWrap.appendChild(makeField('Target Screen View', `${idPrefix}-target-view`, targetSel));
                        } else if (act === 'show_alert') {
                            const tInp = document.createElement('input');
                            tInp.type = 'text';
                            tInp.className = 's-input';
                            tInp.name = `${idPrefix}_alert_title`;
                            tInp.maxLength = 120;
                            tInp.placeholder = 'Going Live!';
                            tInp.value = rules.on_timer_end_alert_title || '';
                            tInp.oninput = () => {
                                recordDebouncedUndo(`logic-alert-title:${view.id}`);
                                rules.on_timer_end_alert_title = tInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            const mInp = document.createElement('input');
                            mInp.type = 'text';
                            mInp.className = 's-input';
                            mInp.name = `${idPrefix}_alert_message`;
                            mInp.maxLength = 280;
                            mInp.placeholder = 'Broadcast starting now.';
                            mInp.value = rules.on_timer_end_alert_message || '';
                            mInp.oninput = () => {
                                recordDebouncedUndo(`logic-alert-msg:${view.id}`);
                                rules.on_timer_end_alert_message = mInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            detailsWrap.append(
                                makeField('Alert Title', `${idPrefix}-alert-title`, tInp),
                                makeField('Alert Message', `${idPrefix}-alert-msg`, mInp)
                            );
                        } else if (act === 'show_lower_third') {
                            const bInp = document.createElement('input');
                            bInp.type = 'text';
                            bInp.className = 's-input';
                            bInp.name = `${idPrefix}_l3_badge`;
                            bInp.maxLength = 40;
                            bInp.placeholder = 'LIVE (optional)';
                            bInp.value = rules.on_timer_end_l3_badge || '';
                            bInp.oninput = () => {
                                recordDebouncedUndo(`logic-l3-badge:${view.id}`);
                                rules.on_timer_end_l3_badge = bInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            const tInp = document.createElement('input');
                            tInp.type = 'text';
                            tInp.className = 's-input';
                            tInp.name = `${idPrefix}_l3_title`;
                            tInp.maxLength = 120;
                            tInp.placeholder = 'Leave blank to use current title';
                            tInp.value = rules.on_timer_end_l3_title || '';
                            tInp.oninput = () => {
                                recordDebouncedUndo(`logic-l3-title:${view.id}`);
                                rules.on_timer_end_l3_title = tInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            const sInp = document.createElement('input');
                            sInp.type = 'text';
                            sInp.className = 's-input';
                            sInp.name = `${idPrefix}_l3_subtitle`;
                            sInp.maxLength = 180;
                            sInp.placeholder = 'Leave blank to use current subtitle';
                            sInp.value = rules.on_timer_end_l3_subtitle || '';
                            sInp.oninput = () => {
                                recordDebouncedUndo(`logic-l3-sub:${view.id}`);
                                rules.on_timer_end_l3_subtitle = sInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            detailsWrap.append(
                                makeField('Lower Third Badge', `${idPrefix}-l3-badge`, bInp),
                                makeField('Lower Third Title', `${idPrefix}-l3-title`, tInp),
                                makeField('Lower Third Subtitle', `${idPrefix}-l3-sub`, sInp, 'Blank fields keep your current Lower Third text.')
                            );
                        } else if (act === 'obs_switch_scene') {
                            const sInp = document.createElement('input');
                            sInp.type = 'text';
                            sInp.className = 's-input';
                            sInp.name = `${idPrefix}_obs_scene`;
                            sInp.maxLength = 180;
                            sInp.setAttribute('list', 'builder-obs-scenes-list');
                            sInp.placeholder = OBS_SCENES_LIST[0] || 'Main Camera Scene';
                            sInp.value = rules.on_timer_end_obs_scene || '';
                            sInp.oninput = () => {
                                recordDebouncedUndo(`logic-obs-scene:${view.id}`);
                                rules.on_timer_end_obs_scene = sInp.value;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            detailsWrap.appendChild(makeField(
                                'Target OBS Scene Name',
                                `${idPrefix}-obs-scene`,
                                sInp,
                                OBS_SCENES_LIST.length > 0 ? 'Pick a synced OBS scene or type an exact scene name.' : 'Enter the exact OBS Scene name to switch to.'
                            ));
                        } else if (act === 'run_macro') {
                            const mSel = document.createElement('select');
                            mSel.className = 's-select';
                            mSel.name = `${idPrefix}_macro_id`;
                            const defOpt = document.createElement('option');
                            defOpt.value = '';
                            defOpt.textContent = MACROS_LIST.length > 0 ? 'Select OBS Macro...' : 'No macros created yet';
                            mSel.appendChild(defOpt);
                            MACROS_LIST.forEach((m) => {
                                const o = document.createElement('option');
                                o.value = String(m.id);
                                o.textContent = m.name;
                                mSel.appendChild(o);
                            });
                            mSel.value = rules.on_timer_end_macro_id ? String(rules.on_timer_end_macro_id) : '';
                            mSel.onchange = () => {
                                pushUndoSnapshot();
                                rules.on_timer_end_macro_id = mSel.value ? Number(mSel.value) : null;
                                setDirty(true);
                                if (typeof onUpdated === 'function') onUpdated();
                            };
                            detailsWrap.appendChild(makeField(
                                'OBS Macro to Run',
                                `${idPrefix}-macro`,
                                mSel,
                                MACROS_LIST.length === 0 ? 'Create multi-step macros in Admin Panel -> OBS Macros.' : ''
                            ));
                        } else if (act === 'hide_timer') {
                            const note = document.createElement('span');
                            note.className = 'sub-label';
                            note.textContent = 'Hides timer layer(s) on this view at 00:00 and automatically unhides them when you start or reset the timer.';
                            detailsWrap.appendChild(note);
                        } else if (act === 'restart_timer') {
                            const note = document.createElement('span');
                            note.className = 'sub-label';
                            note.textContent = 'Automatically restarts the countdown from its full duration every time it hits 00:00.';
                            detailsWrap.appendChild(note);
                        } else if (act === 'reset_timer') {
                            const note = document.createElement('span');
                            note.className = 'sub-label';
                            note.textContent = 'Resets the countdown back to its full duration and stops at 00:00.';
                            detailsWrap.appendChild(note);
                        }
                    };

                    actionSel.onchange = () => {
                        pushUndoSnapshot();
                        rules.on_timer_end_action = actionSel.value || 'none';
                        if (rules.on_timer_end_action === 'switch_view' && !rules.on_timer_end_target_view_id) {
                            const alt = (colData.views || []).find((v) => Number(v.id) !== Number(view.id));
                            if (alt) rules.on_timer_end_target_view_id = Number(alt.id);
                        }
                        renderDetails();
                        setDirty(true);
                        if (typeof onUpdated === 'function') onUpdated();
                    };

                    renderDetails();
                    hostEl.append(makeField('Action at 00:00', `${idPrefix}-action`, actionSel), detailsWrap);

                    if (showApplyBtn) {
                        const applyBtn = document.createElement('button');
                        applyBtn.type = 'button';
                        applyBtn.className = 's-btn s-btn-xs s-btn-gold';
                        applyBtn.textContent = 'Save End Action';
                        applyBtn.onclick = async () => {
                            try {
                                const res = await apiJson(endpoints.updateViewTpl.replace('__VIEW__', view.id), 'PUT', {
                                    logic_rules: normalizeLogicRules(view.logic_rules),
                                });
                                colData = res.collection;
                                const refreshed = getCurrentView();
                                if (refreshed) {
                                    const vpHost = document.getElementById('vp-logic-editor-host');
                                    if (vpHost) renderTimerEndActionEditor(vpHost, refreshed, { idPrefix: 'vp-logic', showApplyBtn: false });
                                }
                                showToast('Timer end action saved & synced live');
                            } catch (err) {
                                showToast(`Could not save end action: ${err.message}`);
                            }
                        };
                        hostEl.appendChild(applyBtn);
                    }
                };

                const populateViewSettings = () => {
                    const view = getCurrentView();
                    if (!view) return;
                    document.getElementById('vp-name').value = view.name || '';
                    document.getElementById('vp-bg-mode').value = view.background_mode || 'theme';
                    setStaticColorValue('vp-bg-color', view.background_color || '#0B0A08');
                    document.getElementById('vp-bg-asset').value = String(view.background_asset_id || '');
                    setStaticColorValue('vp-theme-accent', view.theme_override?.accent_color || '');
                    setStaticColorValue('vp-theme-surface', view.theme_override?.surface_color || '');
                    document.getElementById('vp-tx-type').value = view.transition_override?.type || '';
                    document.getElementById('vp-tx-dur').value = view.transition_override?.duration_ms ?? '';
                    const vpHost = document.getElementById('vp-logic-editor-host');
                    if (vpHost) {
                        renderTimerEndActionEditor(vpHost, view, {
                            idPrefix: 'vp-logic',
                            showApplyBtn: false,
                            onUpdated: () => {
                                const curLayer = (view.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                                if (curLayer && curLayer.type === 'timer') {
                                    populateLayerInspector();
                                }
                            },
                        });
                    }
                    syncBackgroundFieldVisibility();
                };

                const populateCollectionSettings = () => {
                    const th = colData.theme || {};
                    const tx = colData.default_transition || {};
                    setStaticColorValue('col-accent-color', th.accent_color || '#CDAD69');
                    setStaticColorValue('col-surface-color', th.surface_color || '#0B0A08');
                    setStaticColorValue('col-text-color', th.text_color || '#FAF7EE');
                    setStaticColorValue('col-sweep-color', tx.sweep_color || th.accent_color || '#CDAD69');
                    const fontSel = document.getElementById('col-font-family');
                    if (fontSel) fontSel.value = th.font_family || 'Instrument Sans';
                    document.getElementById('col-bg-mode').value = th.background_mode || 'gradient';
                    setStaticColorValue('col-bg-color', th.background_color || th.surface_color || '#0B0A08');
                    document.getElementById('col-bg-asset').value = String(th.background_asset_id || '');
                    document.getElementById('col-default-tx').value = tx.type || 'fade';
                    document.getElementById('col-tx-duration').value = String(Math.min(3000, Number(tx.duration_ms ?? 450)));
                    document.getElementById('col-tx-swap').value = String(Math.min(3000, Number(tx.swap_point_ms ?? 225)));
                    document.getElementById('col-tx-stinger').value = String(tx.stinger_asset_id || '');
                    syncBackgroundFieldVisibility();
                };

                const renderAll = () => {
                    renderViewsList();
                    renderAssetsList();
                    renderLayerStack();
                    populateLayerInspector();
                    populateViewSettings();
                    populateCollectionSettings();
                    renderCanvas();
                    syncHistoryButtons();
                };

                const saveCollectionSettings = async (silent = false) => {
                    const accent = document.getElementById('col-accent-color').value;
                    const txType = document.getElementById('col-default-tx').value;
                    const txDur = txType === 'cut'
                        ? 0
                        : Math.min(3000, Math.max(80, Math.round(Number(document.getElementById('col-tx-duration').value || 450))));
                    const txSwap = Math.min(txDur, Math.max(0, Math.round(Number(document.getElementById('col-tx-swap').value || Math.round(txDur / 2)))));
                    const bgAssetIdVal = document.getElementById('col-bg-asset').value;

                    const res = await apiJson(endpoints.updateCollection, 'PUT', {
                        theme: {
                            accent_color: accent,
                            secondary_color: accent === '#CDAD69' ? '#F4E185' : accent,
                            surface_color: document.getElementById('col-surface-color').value,
                            text_color: document.getElementById('col-text-color').value,
                            font_family: document.getElementById('col-font-family')?.value || 'Instrument Sans',
                            background_mode: document.getElementById('col-bg-mode').value || 'gradient',
                            background_color: document.getElementById('col-bg-color').value || '#0B0A08',
                            background_asset_id: bgAssetIdVal ? Number(bgAssetIdVal) : null,
                        },
                        default_transition: {
                            type: txType,
                            duration_ms: txDur,
                            swap_point_ms: txSwap,
                            stinger_asset_id: document.getElementById('col-tx-stinger').value ? Number(document.getElementById('col-tx-stinger').value) : null,
                            sweep_color: document.getElementById('col-sweep-color').value,
                        },
                    });
                    themeDirty = false;
                    if (!silent) {
                        colData = res.collection;
                        setDirty(false);
                        renderAll();
                        showToast('Collection settings saved & synced live');
                    }
                    return res.collection;
                };

                const saveCurrentView = async () => {
                    const view = getCurrentView();
                    if (!view) return;
                    if (themeDirty) {
                        await saveCollectionSettings(true);
                    }
                    if (runtimeDirty && colData.runtime_state) {
                        const rt = colData.runtime_state;
                        await pushRuntimePatch({
                            scoreboard: rt.scoreboard ? {
                                team_a_name: rt.scoreboard.team_a_name,
                                team_b_name: rt.scoreboard.team_b_name,
                                team_a_score: Number(rt.scoreboard.team_a_score || 0),
                                team_b_score: Number(rt.scoreboard.team_b_score || 0),
                                match_label: rt.scoreboard.match_label || '',
                                round_label: rt.scoreboard.round_label || '',
                            } : undefined,
                            lower_third: rt.lower_third ? {
                                visible: Boolean(rt.lower_third.visible),
                                badge: rt.lower_third.badge || 'LIVE',
                                title: rt.lower_third.title || '',
                                subtitle: rt.lower_third.subtitle || '',
                            } : undefined,
                            ticker: rt.ticker ? {
                                visible: rt.ticker.visible !== false,
                                label: rt.ticker.label || 'LIVE',
                                items: Array.isArray(rt.ticker.items) ? rt.ticker.items : [],
                            } : undefined,
                        }, null);
                    }
                    const accentOv = document.getElementById('vp-theme-accent').value.trim();
                    const surfaceOv = document.getElementById('vp-theme-surface').value.trim();
                    const themeOverride = {};
                    if (accentOv) themeOverride.accent_color = accentOv;
                    if (surfaceOv) themeOverride.surface_color = surfaceOv;
                    const txTypeVal = document.getElementById('vp-tx-type').value;
                    const txDurVal = txTypeVal === 'cut'
                        ? 0
                        : Math.min(3000, Math.max(80, Math.round(Number(document.getElementById('vp-tx-dur').value || 450))));
                    const res = await apiJson(endpoints.updateViewTpl.replace('__VIEW__', view.id), 'PUT', {
                        name: document.getElementById('vp-name').value.trim() || view.name,
                        background_mode: document.getElementById('vp-bg-mode').value,
                        background_color: document.getElementById('vp-bg-color').value,
                        background_asset_id: document.getElementById('vp-bg-asset').value ? Number(document.getElementById('vp-bg-asset').value) : null,
                        theme_override: Object.keys(themeOverride).length > 0 ? themeOverride : null,
                        transition_override: txTypeVal ? { type: txTypeVal, duration_ms: txDurVal } : null,
                        layers: view.layers || [],
                        logic_rules: normalizeLogicRules(view.logic_rules),
                    });
                    colData = res.collection;
                    setDirty(false);
                    renderAll();
                    showToast('View saved & synced to live stage');
                };

                // Left & Right Tabs
                document.querySelectorAll('[data-left-tab]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const t = btn.dataset.leftTab;
                        document.querySelectorAll('[data-left-tab]').forEach((b) => b.setAttribute('aria-selected', b === btn ? 'true' : 'false'));
                        ['views', 'assets', 'theme'].forEach((k) => {
                            document.getElementById(`left-tab-${k}`).hidden = k !== t;
                        });
                    });
                });

                document.querySelectorAll('[data-inspector-tab]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const t = btn.dataset.inspectorTab;
                        document.querySelectorAll('[data-inspector-tab]').forEach((b) => b.setAttribute('aria-selected', b === btn ? 'true' : 'false'));
                        document.getElementById('inspector-tab-layer').hidden = t !== 'layer';
                        document.getElementById('inspector-tab-view').hidden = t !== 'view';
                    });
                });

                document.getElementById('btn-add-view').addEventListener('click', async () => {
                    const inp = document.getElementById('new-view-name');
                    const name = inp.value.trim();
                    if (!name) return;
                    const res = await apiJson(endpoints.storeView, 'POST', { name, background_mode: 'theme' });
                    colData = res.collection;
                    selectedViewId = Number(res.view_id);
                    selectedLayerId = null;
                    inp.value = '';
                    renderAll();
                    showToast('View created');
                });

                document.querySelectorAll('[data-quick-add]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const view = getCurrentView();
                        if (!view) return;
                        const type = btn.dataset.quickAdd;
                        const id = 'lyr_' + Math.random().toString(36).slice(2, 10);
                        view.layers = view.layers || [];
                        const presets = {
                            camera_cutout: { name: 'Camera Cutout', x: 260, y: 140, w: 1400, h: 788, radius: 22, props: { label: 'OBS Camera / Capture Shows Here', border_width: 3, glow: true } },
                            timer: { name: 'Countdown Timer', x: 680, y: 180, w: 560, h: 200, radius: 20, props: { mode: 'countdown', label: 'LIVE IN', font_size: 84, label_font_size: 20, show_bg: true } },
                            color_box: { name: 'Color Box', x: 760, y: 340, w: 400, h: 240, radius: 16, props: { shape_preset: 'rectangle', fill_mode: 'solid', fill_color: colData.theme?.accent_color || '#CDAD69', fill_color_end: colData.theme?.surface_color || '#181714', gradient_angle: 135, border_color: '#FAF7EE', border_width: 0, border_style: 'solid', shadow: false } },
                            scoreboard: { name: 'Match Scoreboard', x: 360, y: 32, w: 1200, h: 84, radius: 16, props: { style: 'versus_bar' } },
                            lower_third: { name: 'Lower Third', x: 64, y: 880, w: 640, h: 120, radius: 18, props: { style: 'glass' } },
                            ticker: { name: 'Scrolling Ticker', x: 64, y: 968, w: 1792, h: 64, radius: 14, props: { speed_sec: 28 } },
                            shape: { name: 'Glass Card', x: 310, y: 200, w: 1300, h: 520, radius: 24, props: { border_width: 1, backdrop_blur: 18 } },
                            asset: { name: 'Media Asset', x: 160, y: 140, w: 480, h: 270, radius: 12, props: { asset_id: null, url: '', fit: 'contain' } },
                            embed: { name: 'Web Embed', x: 300, y: 180, w: 960, h: 540, radius: 16, props: { url: '' } },
                            text: { name: 'Text Label', x: 360, y: 400, w: 1200, h: 140, radius: 12, props: { text: 'Broadcast Headline', font_size: 64, font_weight: 700, align: 'center', padding: 12 } },
                        };
                        const cfg = presets[type] || presets.text;
                        pushUndoSnapshot();
                        view.layers.push({
                            id,
                            type,
                            name: cfg.name,
                            x: cfg.x,
                            y: cfg.y,
                            w: cfg.w,
                            h: cfg.h,
                            rotation: 0,
                            z_index: view.layers.length + 1,
                            opacity: 1,
                            visible: true,
                            locked: false,
                            radius: cfg.radius,
                            enter_animation: 'fade',
                            enter_duration_ms: 350,
                            enter_delay_ms: 0,
                            loop_animation: 'none',
                            condition_tag: 'always',
                            props: cfg.props,
                        });
                        selectedLayerId = id;
                        setDirty(true);
                        renderAll();
                    });
                });

                const requestDeleteSelectedLayer = async () => {
                    const view = getCurrentView();
                    if (!view || !selectedLayerId) return;
                    const targetLayer = (view.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!targetLayer) return;
                    const ok = await confirmBuilderAction({
                        title: 'Remove Canvas Layer',
                        message: `Remove layer "${targetLayer.name || targetLayer.type || 'Selected Layer'}" from "${view.name}"? Saving the view will also delete any media file used only by this layer.`,
                        confirmLabel: 'Remove Layer',
                    });
                    if (!ok) return;
                    pushUndoSnapshot();
                    view.layers = (view.layers || []).filter((l) => String(l.id) !== String(selectedLayerId));
                    selectedLayerId = null;
                    setDirty(true);
                    renderAll();
                    showToast(`Removed layer "${targetLayer.name || targetLayer.type}" (Cmd/Ctrl+Z to undo)`);
                };

                document.getElementById('lp-delete-btn').addEventListener('click', requestDeleteSelectedLayer);
                document.getElementById('btn-undo-canvas')?.addEventListener('click', performUndo);
                document.getElementById('btn-redo-canvas')?.addEventListener('click', performRedo);

                document.getElementById('btn-clean-unused-assets')?.addEventListener('click', async () => {
                    const unusedCount = (colData.assets || []).filter((a) => a.in_use === false).length;
                    const ok = await confirmBuilderAction({
                        title: 'Clean Unused Server Files',
                        message: unusedCount > 0
                            ? `Permanently delete ${unusedCount} unused asset(s) and any orphaned upload files on server storage? Files currently used in any collection or view will be kept.`
                            : 'Scan and permanently delete any unused assets or orphaned upload files on server storage? Files currently in use will be kept.',
                        confirmLabel: 'Clean Unused Files',
                    });
                    if (!ok) return;
                    try {
                        const res = await apiJson(endpoints.cleanupAssets, 'POST', { include_orphans: true });
                        if (res.collection) {
                            colData = res.collection;
                            renderAssetsList();
                            renderCanvas();
                        }
                        const deleted = Number(res.summary?.total_deleted || 0);
                        const freed = res.summary?.freed_formatted || '0 B';
                        showToast(deleted > 0 ? `Cleaned ${deleted} unused file(s) · freed ${freed}` : 'No unused or orphaned files found');
                    } catch (err) {
                        showToast(`Cleanup failed: ${err.message}`);
                    }
                });

                document.getElementById('btn-fit-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) return;
                    pushUndoSnapshot();
                    layer.x = 0; layer.y = 0; layer.w = Number(colData.width || 1920); layer.h = Number(colData.height || 1080);
                    setDirty(true);
                    renderCanvas(); populateLayerInspector();
                });

                document.getElementById('btn-center-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) return;
                    pushUndoSnapshot();
                    layer.x = Math.round((Number(colData.width || 1920) - Number(layer.w || 200)) / 2);
                    layer.y = Math.round((Number(colData.height || 1080) - Number(layer.h || 80)) / 2);
                    setDirty(true);
                    renderCanvas(); populateLayerInspector();
                });

                document.addEventListener('keydown', (ev) => {
                    const keyLower = ev.key.toLowerCase();
                    if ((ev.metaKey || ev.ctrlKey) && keyLower === 's') {
                        ev.preventDefault();
                        saveCurrentView();
                        return;
                    }

                    const activeEl = document.activeElement;
                    const isTypingTarget = activeEl && (
                        ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName)
                        || activeEl.isContentEditable
                    );
                    if (isTypingTarget) return;

                    const confirmDlg = document.getElementById('builder-confirm-dialog');
                    if (confirmDlg?.open) return;

                    if ((ev.metaKey || ev.ctrlKey) && keyLower === 'z') {
                        ev.preventDefault();
                        if (ev.shiftKey) performRedo();
                        else performUndo();
                        return;
                    }
                    if (ev.ctrlKey && !ev.metaKey && keyLower === 'y') {
                        ev.preventDefault();
                        performRedo();
                        return;
                    }

                    if ((ev.key === 'Delete' || ev.key === 'Backspace') && selectedLayerId) {
                        ev.preventDefault();
                        requestDeleteSelectedLayer();
                        return;
                    }

                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer || layer.locked) return;
                    const step = ev.shiftKey ? 10 : 1;
                    if (ev.key === 'ArrowLeft') { ev.preventDefault(); recordDebouncedUndo(`nudge:${layer.id}`); layer.x = Number(layer.x || 0) - step; }
                    else if (ev.key === 'ArrowRight') { ev.preventDefault(); recordDebouncedUndo(`nudge:${layer.id}`); layer.x = Number(layer.x || 0) + step; }
                    else if (ev.key === 'ArrowUp') { ev.preventDefault(); recordDebouncedUndo(`nudge:${layer.id}`); layer.y = Number(layer.y || 0) - step; }
                    else if (ev.key === 'ArrowDown') { ev.preventDefault(); recordDebouncedUndo(`nudge:${layer.id}`); layer.y = Number(layer.y || 0) + step; }
                    else return;
                    setDirty(true);
                    renderCanvas();
                    populateLayerInspector();
                });

                const uploadMediaAsset = async (file, explicitType = null) => {
                    if (!file) return null;
                    const inferredType = explicitType || (
                        /^video\/|\.(mp4|webm)$/i.test(file.type || file.name) ? 'video' : 'image'
                    );
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('type', inferredType);
                    const res = await fetch(endpoints.uploadAsset, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: fd,
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        showToast(data.message || 'Upload failed');
                        return null;
                    }
                    colData.assets = data.collection?.assets || colData.assets || [];
                    renderAssetsList();
                    return data.asset || null;
                };

                const applyLiveThemeFromInputs = () => {
                    colData.theme = colData.theme || {};
                    colData.default_transition = colData.default_transition || {};

                    const accent = document.getElementById('col-accent-color').value || '#CDAD69';
                    const surface = document.getElementById('col-surface-color').value || '#0B0A08';
                    const textColor = document.getElementById('col-text-color').value || '#FAF7EE';
                    const bgMode = document.getElementById('col-bg-mode').value || 'gradient';
                    const bgColor = document.getElementById('col-bg-color').value || surface;
                    const bgAssetIdRaw = document.getElementById('col-bg-asset').value;
                    const bgAsset = bgAssetIdRaw
                        ? (colData.assets || []).find((a) => String(a.id) === String(bgAssetIdRaw)) || null
                        : null;

                    colData.theme.accent_color = accent;
                    colData.theme.secondary_color = accent === '#CDAD69' ? '#F4E185' : accent;
                    colData.theme.surface_color = surface;
                    colData.theme.text_color = textColor;
                    colData.theme.font_family = document.getElementById('col-font-family')?.value || 'Instrument Sans';
                    colData.theme.background_mode = bgMode;
                    colData.theme.background_color = bgColor;
                    colData.theme.background_asset_id = bgAsset ? bgAsset.id : null;
                    colData.theme.background_asset_url = bgAsset ? bgAsset.url : null;
                    colData.theme.background_asset_type = bgAsset ? bgAsset.type : null;
                    colData.default_transition.sweep_color = document.getElementById('col-sweep-color').value || accent;

                    syncBackgroundFieldVisibility();
                    themeDirty = true;
                    setDirty(true);
                    renderCanvas();
                };

                const applyLiveViewSettingsFromInputs = () => {
                    const view = getCurrentView();
                    if (!view) return;
                    const bgMode = document.getElementById('vp-bg-mode').value || 'theme';
                    const bgColor = document.getElementById('vp-bg-color').value || '#0B0A08';
                    const bgAssetIdRaw = document.getElementById('vp-bg-asset').value;
                    const bgAsset = bgAssetIdRaw
                        ? (colData.assets || []).find((a) => String(a.id) === String(bgAssetIdRaw)) || null
                        : null;
                    const accentOv = document.getElementById('vp-theme-accent').value.trim();
                    const surfaceOv = document.getElementById('vp-theme-surface').value.trim();
                    const nextOverride = {};
                    if (accentOv) nextOverride.accent_color = accentOv;
                    if (surfaceOv) nextOverride.surface_color = surfaceOv;

                    view.name = document.getElementById('vp-name').value || view.name;
                    view.background_mode = bgMode;
                    view.background_color = bgColor;
                    view.background_asset_id = bgAsset ? bgAsset.id : null;
                    view.background_asset_url = bgAsset ? bgAsset.url : null;
                    view.background_asset_type = bgAsset ? bgAsset.type : null;
                    view.theme_override = Object.keys(nextOverride).length > 0 ? nextOverride : null;

                    syncBackgroundFieldVisibility();
                    setDirty(true);
                    renderCanvas();
                };

                ['col-font-family', 'col-bg-mode', 'col-bg-asset'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('input', applyLiveThemeFromInputs);
                    el.addEventListener('change', applyLiveThemeFromInputs);
                });

                ['col-default-tx', 'col-tx-duration', 'col-tx-swap', 'col-tx-stinger'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('change', () => {
                        themeDirty = true;
                        setDirty(true);
                    });
                });

                ['vp-name', 'vp-bg-mode', 'vp-bg-asset'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.addEventListener('input', applyLiveViewSettingsFromInputs);
                    el.addEventListener('change', applyLiveViewSettingsFromInputs);
                });

                document.getElementById('btn-view-inherit-bg')?.addEventListener('click', () => {
                    const vpMode = document.getElementById('vp-bg-mode');
                    if (vpMode) vpMode.value = 'theme';
                    applyLiveViewSettingsFromInputs();
                    showToast('View now uses Collection Default Background (remember to save view)');
                });

                document.getElementById('asset-file-input').addEventListener('change', async (ev) => {
                    const file = ev.target.files?.[0];
                    if (!file) return;
                    const asset = await uploadMediaAsset(file, document.getElementById('asset-upload-type').value);
                    ev.target.value = '';
                    if (asset) showToast(`Uploaded: ${asset.name}`);
                });

                document.getElementById('col-bg-upload-input')?.addEventListener('change', async (ev) => {
                    const file = ev.target.files?.[0];
                    if (!file) return;
                    showToast('Uploading default background...');
                    const asset = await uploadMediaAsset(file);
                    ev.target.value = '';
                    if (!asset) return;
                    document.getElementById('col-bg-mode').value = 'asset';
                    document.getElementById('col-bg-asset').value = String(asset.id);
                    applyLiveThemeFromInputs();
                    showToast(`Default BG uploaded: ${asset.name} — click Save Collection Settings to push live`);
                });

                document.getElementById('vp-bg-upload-input')?.addEventListener('change', async (ev) => {
                    const file = ev.target.files?.[0];
                    if (!file) return;
                    showToast('Uploading view background...');
                    const asset = await uploadMediaAsset(file);
                    ev.target.value = '';
                    if (!asset) return;
                    document.getElementById('vp-bg-mode').value = 'asset';
                    document.getElementById('vp-bg-asset').value = String(asset.id);
                    applyLiveViewSettingsFromInputs();
                    showToast(`View BG uploaded: ${asset.name} — click Save View to push live`);
                });

                document.getElementById('btn-save-collection-settings').addEventListener('click', () => saveCollectionSettings(false));

                document.getElementById('builder-save-view-btn').addEventListener('click', saveCurrentView);
                document.getElementById('lp-save-btn').addEventListener('click', saveCurrentView);
                document.getElementById('vp-save-btn').addEventListener('click', saveCurrentView);

                document.getElementById('builder-go-live-btn').addEventListener('click', async () => {
                    await saveCurrentView();
                    const res = await apiJson(endpoints.switchView, 'POST', { view_id: selectedViewId });
                    colData = res.collection;
                    renderViewsList();
                    showToast('Switched live OBS browser view');
                });

                document.getElementById('builder-copy-stage-btn').addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(colData.stage_url);
                        showToast('Stage URL copied for OBS Browser Source');
                    } catch {
                        prompt('Copy OBS Browser Source URL:', colData.stage_url);
                    }
                });

                document.getElementById('builder-push-obs-btn').addEventListener('click', async () => {
                    await apiJson(endpoints.pushObs, 'POST', {});
                    showToast('Pushing unified single scene to OBS Studio');
                });

                mountStaticColorPickers();
                renderAll();
            })();
        </script>
    </body>
</html>
