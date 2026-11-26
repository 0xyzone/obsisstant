<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $collection->name }} · Screen Canvas Builder · Obsisstant</title>
        <x-app-favicon />
        <x-dev-hot-reload />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800|jetbrains-mono:500,600,700|poppins:400,500,600,700,800&display=swap" rel="stylesheet">
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

            .color-field-row {
                display: grid;
                grid-template-columns: 2.25rem minmax(0, 1fr) auto;
                gap: 0.3rem;
                align-items: center;
            }
            .color-swatch-input {
                inline-size: 2.25rem;
                min-block-size: 2rem;
                padding: 0.12rem;
                cursor: pointer;
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
                background: var(--bg-panel);
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
            <aside class="panel">
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
                    <div class="grid-2">
                        <label class="stack">
                            <span class="sub-label">Accent Color</span>
                            <input type="color" id="col-accent-color" class="s-input" style="padding: 0.15rem;">
                        </label>
                        <label class="stack">
                            <span class="sub-label">Surface Color</span>
                            <input type="color" id="col-surface-color" class="s-input" style="padding: 0.15rem;">
                        </label>
                        <label class="stack">
                            <span class="sub-label">Text Color</span>
                            <input type="color" id="col-text-color" class="s-input" style="padding: 0.15rem;">
                        </label>
                        <label class="stack">
                            <span class="sub-label">Sweep Color</span>
                            <input type="color" id="col-sweep-color" class="s-input" style="padding: 0.15rem;">
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

                        <label class="stack" id="col-bg-color-wrap" hidden>
                            <span class="sub-label">Default Background Color</span>
                            <input type="color" id="col-bg-color" class="s-input" style="padding: 0.15rem;">
                        </label>

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
                    <span class="sub-label">Tip: Cmd/Ctrl + S saves changes immediately to your live stage</span>
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
                    <label class="stack" id="vp-bg-color-wrap" hidden>
                        <span class="sub-label">Solid Bg Color</span>
                        <input type="color" id="vp-bg-color" class="s-input" style="padding: 0.15rem;">
                    </label>
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

                    <div class="grid-2">
                        <label class="stack">
                            <span class="sub-label">View Accent Override</span>
                            <input type="text" id="vp-theme-accent" class="s-input" placeholder="Inherit Collection">
                        </label>
                        <label class="stack">
                            <span class="sub-label">View Surface Override</span>
                            <input type="text" id="vp-theme-surface" class="s-input" placeholder="Inherit Collection">
                        </label>
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

                    <div class="stack" style="padding: 0.6rem; border-radius: 0.5rem; background: var(--bg-chrome); border: 1px solid var(--hairline);">
                        <strong style="font-size: 0.75rem; color: var(--gold-light);">Timer Automation Logic</strong>
                        <label class="stack">
                            <span class="sub-label">When Countdown Hits 00:00</span>
                            <select id="vp-logic-action" class="s-select">
                                <option value="none">Do Nothing</option>
                                <option value="switch_view">Auto-Switch to Screen View</option>
                                <option value="show_alert">Trigger Pop-Up Alert Toast</option>
                            </select>
                        </label>
                        <label class="stack" id="vp-logic-target-wrap">
                            <span class="sub-label">Auto-Switch Target View</span>
                            <select id="vp-logic-target-view" class="s-select"></select>
                        </label>
                    </div>

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
                let selectedViewId = Number(colData.active_view_id || colData.views?.[0]?.id || 0);
                let selectedLayerId = null;
                let dragActive = false;
                let isDirty = false;
                let themeDirty = false;
                let lastEffectiveBgLabel = '';
                const endpoints = @json($builderEndpoints);

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
                    const secondary = theme.secondary_color && theme.secondary_color !== '#F4E185'
                        ? theme.secondary_color
                        : (accent === '#CDAD69' ? '#F4E185' : accent);
                    const effectiveBg = resolveEffectiveBackground(view, theme);

                    logical.style.color = textColor;

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
                            if (p.border_width !== undefined && p.border_width !== '' && bw > 0) {
                                el.style.border = `${bw}px ${bs} ${p.border_color || accent}`;
                            } else if (p.border_width === 0 || p.border_width === '0') {
                                el.style.border = 'none';
                            } else if (p.border_color) {
                                el.style.border = `2px ${bs} ${p.border_color}`;
                            } else if (defaultBorder) {
                                el.style.border = defaultBorder;
                            }
                        };

                        if (layer.type === 'camera_cutout') {
                            const cutoutInner = document.createElement('div');
                            cutoutInner.style.cssText = `width:100%;height:100%;box-sizing:border-box;border-radius:${layerRadiusCss};background-image:linear-gradient(45deg, rgba(255,255,255,0.09) 25%, transparent 25%), linear-gradient(-45deg, rgba(255,255,255,0.09) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.09) 75%), linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.09) 75%);background-size:32px 32px;background-color:rgba(0,0,0,0.42);display:grid;place-items:center;color:rgba(250,247,238,0.65);font-size:26px;font-weight:600;pointer-events:none;text-align:center;padding:24px;`;
                            const bw = Number(p.border_width ?? 3);
                            const bc = p.border_color || accent;
                            if (bw > 0) cutoutInner.style.border = `${bw}px solid ${bc}`;
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
                            if (bw > 0) inner.style.border = `${bw}px ${bs} ${bc}`;
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
                            if (p.font_family) span.style.fontFamily = `'${p.font_family}', ui-sans-serif, system-ui, sans-serif`;
                            if (p.text_shadow) span.style.textShadow = '0 4px 18px rgba(0,0,0,0.75)';
                            preview.appendChild(span);
                            box.appendChild(preview);
                        } else if (layer.type === 'timer') {
                            const showBg = p.show_bg !== false;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:grid;place-content:center;text-align:center;gap:6px;padding:12px 20px;border-radius:${layerRadiusCss};pointer-events:none;`;
                            inner.style.background = showBg ? (p.bg_color || `color-mix(in oklab, ${surface} 85%, transparent)`) : 'transparent';
                            applyInnerOutline(inner, showBg ? `1px solid ${glassBorder}` : null);
                            const lblDiv = document.createElement('div');
                            lblDiv.style.cssText = `font-size:${Number(p.label_font_size || 20)}px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:${p.label_color || mutedColor};`;
                            lblDiv.textContent = p.label || 'LIVE IN';
                            const digitsDiv = document.createElement('div');
                            digitsDiv.style.cssText = `font-family:'JetBrains Mono',monospace;font-size:${Number(p.font_size || 72)}px;font-weight:700;line-height:1;color:${p.text_color || accent};`;
                            digitsDiv.textContent = p.show_hours ? '00:05:00' : '05:00';
                            inner.append(lblDiv, digitsDiv);
                            box.appendChild(inner);
                        } else if (layer.type === 'scoreboard') {
                            const sb = colData.runtime_state?.scoreboard || {};
                            const sbAccent = p.accent_color || accent;
                            const sbText = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:0 28px;font-size:26px;font-weight:700;border-radius:${layerRadiusCss};color:${sbText};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;`;
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            inner.innerHTML = `<span>${sb.team_a_name || 'Team Alpha'} <strong style="color:${sbAccent};margin-left:12px;">${sb.team_a_score ?? 0}</strong></span><span style="font-size:18px;color:${mutedColor};padding:0 20px;">${sb.round_label || 'VS'}</span><span style="text-align:right;"><strong style="color:${sbAccent};margin-right:12px;">${sb.team_b_score ?? 0}</strong> ${sb.team_b_name || 'Team Omega'}</span>`;
                            box.appendChild(inner);
                        } else if (layer.type === 'lower_third') {
                            const l3 = colData.runtime_state?.lower_third || {};
                            const l3Accent = p.accent_color || accent;
                            const l3Text = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;padding:0 28px;border-radius:${layerRadiusCss};color:${l3Text};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;`;
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            inner.style.borderLeft = `6px solid ${l3Accent}`;
                            inner.innerHTML = `<div style="font-size:32px;font-weight:700;">${l3.title || 'Broadcast Host'}</div><div style="font-size:20px;color:${l3Accent};">${l3.subtitle || 'Live From Studio'}</div>`;
                            box.appendChild(inner);
                        } else if (layer.type === 'ticker') {
                            const tkAccent = p.accent_color || accent;
                            const tkText = p.text_color || textColor;
                            const inner = document.createElement('div');
                            inner.style.cssText = `width:100%;height:100%;box-sizing:border-box;display:flex;align-items:center;gap:20px;padding:0 24px;font-size:22px;border-radius:${layerRadiusCss};color:${tkText};background:${p.bg_color || `color-mix(in oklab, ${surface} 88%, transparent)`};pointer-events:none;overflow:hidden;`;
                            applyInnerOutline(inner, `1px solid ${glassBorder}`);
                            inner.innerHTML = `<span style="color:${tkAccent};font-weight:700;">LIVE</span><span style="color:${tkText};opacity:0.86;white-space:nowrap;">Scrolling news and tournament updates appear here...</span>`;
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
                            dragActive = true;
                            box.setPointerCapture(ev.pointerId);

                            const onMove = (mEv) => {
                                const dx = (mEv.clientX - startX) / scale;
                                const dy = (mEv.clientY - startY) / scale;
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
                    const targetSel = document.getElementById('vp-logic-target-view');
                    const views = colData.views || [];
                    document.getElementById('views-count-meta').textContent = `${views.length} view${views.length === 1 ? '' : 's'}`;

                    if (targetSel) {
                        targetSel.innerHTML = '<option value="">Select View</option>';
                        views.forEach((v) => {
                            const o = document.createElement('option');
                            o.value = String(v.id);
                            o.textContent = v.name;
                            targetSel.appendChild(o);
                        });
                    }

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

                    const isValidHex6 = (val) => /^#[0-9a-f]{6}$/i.test(String(val || '').trim());

                    const makeColorField = (currentVal, fallbackHex, placeholder, onChange) => {
                        const row = document.createElement('div');
                        row.className = 'color-field-row';
                        const swatch = document.createElement('input');
                        swatch.type = 'color';
                        swatch.className = 's-input color-swatch-input';
                        swatch.value = isValidHex6(currentVal) ? currentVal : (isValidHex6(fallbackHex) ? fallbackHex : '#CDAD69');

                        const txt = document.createElement('input');
                        txt.type = 'text';
                        txt.className = 's-input';
                        txt.placeholder = placeholder || 'Inherit';
                        txt.value = currentVal || '';

                        const clr = document.createElement('button');
                        clr.type = 'button';
                        clr.className = 's-btn s-btn-xs';
                        clr.textContent = '×';
                        clr.title = 'Reset / Clear color';

                        swatch.oninput = () => {
                            txt.value = swatch.value;
                            onChange(swatch.value);
                            setDirty(true);
                            renderCanvas();
                        };
                        txt.oninput = () => {
                            const cleaned = txt.value.trim();
                            if (isValidHex6(cleaned)) swatch.value = cleaned;
                            onChange(cleaned);
                            setDirty(true);
                            renderCanvas();
                        };
                        clr.onclick = () => {
                            txt.value = '';
                            swatch.value = isValidHex6(fallbackHex) ? fallbackHex : '#CDAD69';
                            onChange('');
                            setDirty(true);
                            renderCanvas();
                        };

                        row.append(swatch, txt, clr);
                        return row;
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
                            (v) => { layer.props.border_color = v; }
                        ));
                        const g = makeGrid2();
                        addExtra('Outline Thickness (px)', makeNumberField(
                            layer.props.border_width ?? defaultWidth,
                            { min: 0, max: 40, step: 1, fallback: defaultWidth },
                            (v) => { layer.props.border_width = Math.max(0, v); }
                        ), g);
                        if (allowStyle) {
                            addExtra('Outline Style', makeSelectField(
                                [['solid', 'Solid'], ['dashed', 'Dashed'], ['dotted', 'Dotted']],
                                layer.props.border_style || 'solid',
                                (v) => { layer.props.border_style = v; }
                            ), g);
                        }
                    };

                    if (layer.type === 'text') {
                        const ta = document.createElement('textarea');
                        ta.className = 's-textarea';
                        ta.rows = 2;
                        ta.value = layer.props.text || '';
                        ta.oninput = () => { layer.props.text = ta.value; setDirty(true); renderCanvas(); };
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
                            [['', 'Inherit Theme'], ['Instrument Sans', 'Instrument Sans'], ['Poppins', 'Poppins'], ['JetBrains Mono', 'JetBrains Mono']],
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

                        addExtra('Font Color', makeColorField(layer.props.text_color || '', activeTheme.text_color || '#FAF7EE', 'Inherit Theme Text', (v) => { layer.props.text_color = v; }));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Transparent', (v) => { layer.props.bg_color = v; }));
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
                        lbl.oninput = () => { layer.props.label = lbl.value; setDirty(true); renderCanvas(); };
                        addExtra('Timer Label', lbl);

                        const g1 = makeGrid2();
                        addExtra('Digit Size (px)', makeNumberField(layer.props.font_size ?? 72, { min: 18, max: 260, step: 2, fallback: 72 }, (v) => { layer.props.font_size = Math.max(18, v); }), g1);
                        addExtra('Label Size (px)', makeNumberField(layer.props.label_font_size ?? 20, { min: 10, max: 80, step: 1, fallback: 20 }, (v) => { layer.props.label_font_size = Math.max(10, v); }), g1);

                        addExtra('Digit Font Color', makeColorField(layer.props.text_color || '', activeTheme.accent_color || '#CDAD69', 'Inherit Accent', (v) => { layer.props.text_color = v; }));
                        addExtra('Label Font Color', makeColorField(layer.props.label_color || '', activeTheme.muted_color || '#ABA291', 'Inherit Muted', (v) => { layer.props.label_color = v; }));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Inherit Surface Glass', (v) => { layer.props.bg_color = v; }));
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
                        if (layer.type === 'ticker') {
                            addExtra('Scroll Speed (seconds)', makeNumberField(
                                layer.props.speed_sec ?? 28,
                                { min: 5, max: 120, step: 1, fallback: 28 },
                                (v) => { layer.props.speed_sec = Math.max(5, v); }
                            ));
                        }
                        addExtra('Text Color', makeColorField(layer.props.text_color || '', activeTheme.text_color || '#FAF7EE', 'Inherit Theme Text', (v) => { layer.props.text_color = v; }));
                        addExtra('Accent / Highlight Color', makeColorField(layer.props.accent_color || '', activeTheme.accent_color || '#CDAD69', 'Inherit Theme Accent', (v) => { layer.props.accent_color = v; }));
                        addExtra('Background Color', makeColorField(layer.props.bg_color || '', activeTheme.surface_color || '#0B0A08', 'Inherit Surface Glass', (v) => { layer.props.bg_color = v; }));
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
                            (v) => { layer.props.border_color = v; }
                        ));
                        addExtra('Border Thickness (px)', makeNumberField(
                            layer.props.border_width ?? 3,
                            { min: 0, max: 30, step: 1, fallback: 3 },
                            (v) => { layer.props.border_width = Math.max(0, v); }
                        ));
                        makeToggleField('Accent Border Glow', layer.props.glow !== false, (v) => { layer.props.glow = v; });
                    }
                };

                const populateViewSettings = () => {
                    const view = getCurrentView();
                    if (!view) return;
                    document.getElementById('vp-name').value = view.name || '';
                    document.getElementById('vp-bg-mode').value = view.background_mode || 'theme';
                    document.getElementById('vp-bg-color').value = view.background_color || '#0B0A08';
                    document.getElementById('vp-bg-asset').value = String(view.background_asset_id || '');
                    document.getElementById('vp-theme-accent').value = view.theme_override?.accent_color || '';
                    document.getElementById('vp-theme-surface').value = view.theme_override?.surface_color || '';
                    document.getElementById('vp-tx-type').value = view.transition_override?.type || '';
                    document.getElementById('vp-tx-dur').value = view.transition_override?.duration_ms ?? '';
                    document.getElementById('vp-logic-action').value = view.logic_rules?.on_timer_end_action || 'none';
                    document.getElementById('vp-logic-target-view').value = String(view.logic_rules?.on_timer_end_target_view_id || '');
                    syncBackgroundFieldVisibility();
                };

                const populateCollectionSettings = () => {
                    const th = colData.theme || {};
                    const tx = colData.default_transition || {};
                    document.getElementById('col-accent-color').value = th.accent_color || '#CDAD69';
                    document.getElementById('col-surface-color').value = th.surface_color || '#0B0A08';
                    document.getElementById('col-text-color').value = th.text_color || '#FAF7EE';
                    document.getElementById('col-sweep-color').value = tx.sweep_color || th.accent_color || '#CDAD69';
                    document.getElementById('col-bg-mode').value = th.background_mode || 'gradient';
                    document.getElementById('col-bg-color').value = th.background_color || th.surface_color || '#0B0A08';
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
                        logic_rules: {
                            on_timer_end_action: document.getElementById('vp-logic-action').value,
                            on_timer_end_target_view_id: document.getElementById('vp-logic-target-view').value ? Number(document.getElementById('vp-logic-target-view').value) : null,
                        },
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

                document.getElementById('lp-delete-btn').addEventListener('click', async () => {
                    const view = getCurrentView();
                    if (!view || !selectedLayerId) return;
                    const targetLayer = (view.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    const ok = await confirmBuilderAction({
                        title: 'Remove Canvas Layer',
                        message: `Remove layer "${targetLayer?.name || targetLayer?.type || 'Selected Layer'}" from "${view.name}"? Saving the view will also delete any media file used only by this layer.`,
                        confirmLabel: 'Remove Layer',
                    });
                    if (!ok) return;
                    view.layers = (view.layers || []).filter((l) => String(l.id) !== String(selectedLayerId));
                    selectedLayerId = null;
                    setDirty(true);
                    renderAll();
                });

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
                    layer.x = 0; layer.y = 0; layer.w = Number(colData.width || 1920); layer.h = Number(colData.height || 1080);
                    setDirty(true);
                    renderCanvas(); populateLayerInspector();
                });

                document.getElementById('btn-center-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) return;
                    layer.x = Math.round((Number(colData.width || 1920) - Number(layer.w || 200)) / 2);
                    layer.y = Math.round((Number(colData.height || 1080) - Number(layer.h || 80)) / 2);
                    setDirty(true);
                    renderCanvas(); populateLayerInspector();
                });

                document.addEventListener('keydown', (ev) => {
                    if ((ev.metaKey || ev.ctrlKey) && ev.key.toLowerCase() === 's') {
                        ev.preventDefault();
                        saveCurrentView();
                        return;
                    }
                    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName || '')) return;
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer || layer.locked) return;
                    const step = ev.shiftKey ? 10 : 1;
                    if (ev.key === 'ArrowLeft') { ev.preventDefault(); layer.x = Number(layer.x || 0) - step; }
                    else if (ev.key === 'ArrowRight') { ev.preventDefault(); layer.x = Number(layer.x || 0) + step; }
                    else if (ev.key === 'ArrowUp') { ev.preventDefault(); layer.y = Number(layer.y || 0) - step; }
                    else if (ev.key === 'ArrowDown') { ev.preventDefault(); layer.y = Number(layer.y || 0) + step; }
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

                ['col-accent-color', 'col-surface-color', 'col-text-color', 'col-sweep-color', 'col-bg-mode', 'col-bg-color', 'col-bg-asset'].forEach((id) => {
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

                ['vp-name', 'vp-bg-mode', 'vp-bg-color', 'vp-bg-asset', 'vp-theme-accent', 'vp-theme-surface'].forEach((id) => {
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

                renderAll();
            })();
        </script>
    </body>
</html>
