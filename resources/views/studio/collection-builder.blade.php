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
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&family=jetbrains-mono:500,600&display=swap" rel="stylesheet">
        <style>
            :root {
                color-scheme: dark;
                --bg-canvas: #070706;
                --bg-chrome: #121210;
                --bg-card: #191815;
                --bg-raised: #1E1D19;
                --gold-accent: #CDAD69;
                --gold-light: #F4E185;
                --positive: #4CC38A;
                --danger: #E5484D;
                --ink-primary: #FAF7EE;
                --ink-secondary: #C8C0AF;
                --ink-muted: #8E8777;
                --hairline: rgba(205, 173, 105, 0.16);
                --hairline-strong: rgba(205, 173, 105, 0.34);
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
                gap: 0.625rem;
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
                cursor: pointer;
            }

            .s-btn:hover { border-color: var(--hairline-strong); background: #262520; }
            .s-btn-gold {
                background: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border-color: rgba(244, 225, 133, 0.45);
            }
            .s-btn-gold:hover { background: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%); color: #070706; }
            .s-btn-danger {
                border-color: rgba(229, 72, 77, 0.4);
                background: rgba(229, 72, 77, 0.14);
                color: #F4A3A6;
            }
            .s-btn-xs { min-block-size: 1.65rem; padding: 0.125rem 0.45rem; font-size: 0.6875rem; }
            .s-btn.is-active { border-color: var(--gold-accent); background: rgba(205, 173, 105, 0.16); color: var(--gold-light); }

            .s-input, .s-select, .s-textarea {
                background: #0F0E0C;
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

            .builder-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                padding: 0.875rem;
                align-items: start;
            }

            @media (min-width: 72rem) {
                .builder-grid {
                    grid-template-columns: 18.5rem minmax(0, 1fr) 21.5rem;
                }
            }

            .panel {
                background: var(--bg-card);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.65rem;
            }

            .panel-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-block-end: 0.45rem;
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

            .stack { display: grid; gap: 0.45rem; }

            .list-scroll {
                display: grid;
                gap: 0.35rem;
                max-block-size: 16rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
            }

            .item-row {
                display: grid;
                gap: 0.3rem;
                padding: 0.45rem;
                border-radius: 0.5rem;
                background: var(--bg-raised);
                border: 1px solid transparent;
            }

            .item-row.is-selected {
                border-color: var(--gold-accent);
                background: rgba(205, 173, 105, 0.13);
            }

            .row-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.35rem;
                min-inline-size: 0;
            }

            .row-btn {
                background: transparent;
                border: 0;
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.78rem;
                font-weight: 600;
                text-align: start;
                cursor: pointer;
                padding: 0;
                min-inline-size: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                flex: 1;
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
                border: 1.5px solid rgba(205, 173, 105, 0.52);
                cursor: grab;
                transition: border-color 110ms ease;
            }

            .canvas-layer:hover { border-color: var(--gold-light); }
            .canvas-layer.is-selected { border: 2px solid var(--gold-light); box-shadow: 0 0 0 2px rgba(244, 225, 133, 0.25); }
            .canvas-layer.is-locked { cursor: default; border-color: rgba(142, 135, 119, 0.5); }
            .canvas-layer.is-hidden { opacity: 0.3; border-style: dashed; }

            .canvas-tag {
                position: absolute;
                inset-inline-start: 4px;
                inset-block-start: 4px;
                padding: 2px 6px;
                border-radius: 4px;
                background: rgba(7, 7, 6, 0.86);
                color: var(--gold-light);
                font-size: 13px;
                font-weight: 600;
                pointer-events: none;
                white-space: nowrap;
                z-index: 5;
            }

            .resize-handle {
                position: absolute;
                inset-inline-end: -7px;
                inset-block-end: -7px;
                inline-size: 16px;
                block-size: 16px;
                border-radius: 3px;
                background: var(--gold-light);
                border: 2px solid #070706;
                cursor: nwse-resize;
                z-index: 6;
            }

            .canvas-layer.is-locked .resize-handle { display: none; }

            .tab-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.25rem;
                padding: 0.2rem;
                border-radius: 0.5rem;
                background: var(--bg-chrome);
                border: 1px solid var(--hairline);
            }

            .tab-btn {
                min-block-size: 1.9rem;
                border: 0;
                border-radius: 0.375rem;
                background: transparent;
                color: var(--ink-muted);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
            }

            .tab-btn[aria-selected="true"] {
                background: var(--bg-raised);
                color: var(--gold-light);
            }

            .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.4rem; }

            .toast {
                position: fixed;
                inset-block-end: 1rem;
                inset-inline-end: 1rem;
                z-index: 60;
                padding: 0.6rem 0.9rem;
                border-radius: 0.6rem;
                border: 1px solid var(--hairline-strong);
                background: rgba(18, 18, 16, 0.96);
                color: var(--gold-light);
                font-size: 0.8rem;
                font-weight: 500;
            }
            .toast[hidden] { display: none; }
            [hidden] { display: none !important; }
        </style>
    </head>
    <body>
        <header class="topbar">
            <div class="bar-group">
                <a href="{{ route('studio.show', ['studio' => $studio->slug]) }}" class="s-btn">&larr; Back to Studio</a>
                <strong style="font-size: 0.9375rem; color: var(--gold-light);" id="collection-title-badge">{{ $collection->name }}</strong>
                <span class="sub-label">{{ $collection->width }} &times; {{ $collection->height }}</span>
                <span class="sub-label">v{{ $currentVersion }}</span>
            </div>

            <div class="bar-group">
                <button type="button" id="builder-copy-stage-btn" class="s-btn">Copy Browser URL</button>
                <a href="{{ $collection->getStageUrl() }}" target="_blank" class="s-btn">Open Stage URL</a>
                <button type="button" id="builder-push-obs-btn" class="s-btn">Push Single Scene to OBS</button>
                <button type="button" id="builder-save-view-btn" class="s-btn s-btn-gold">Save View</button>
                <button type="button" id="builder-go-live-btn" class="s-btn s-btn-gold">Go Live With View</button>
            </div>
        </header>

        <main class="builder-grid">
            {{-- Left: Screen Views, Assets Library & Collection Theme/Transition --}}
            <aside class="stack">
                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Screen Views</h2>
                        <span class="sub-label" id="views-count-meta">0 views</span>
                    </div>
                    <div style="display: flex; gap: 0.35rem;">
                        <input type="text" id="new-view-name" class="s-input" placeholder="New view name...">
                        <button type="button" id="btn-add-view" class="s-btn s-btn-gold">Add</button>
                    </div>
                    <div id="views-list" class="list-scroll"></div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">Collection Assets</h2>
                        <span class="sub-label">PNG / SVG / MP4 / WebM</span>
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
                </section>

                <section class="panel">
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
                            <span class="sub-label">Duration (ms)</span>
                            <input type="number" id="col-tx-duration" class="s-input" min="0" max="5000" step="50">
                        </label>
                        <label class="stack">
                            <span class="sub-label">Swap Point (ms)</span>
                            <input type="number" id="col-tx-swap" class="s-input" min="0" max="5000" step="25">
                        </label>
                    </div>
                    <label class="stack">
                        <span class="sub-label">Default Stinger Video Asset</span>
                        <select id="col-tx-stinger" class="s-select">
                            <option value="">None</option>
                        </select>
                    </label>
                    <button type="button" id="btn-save-collection-settings" class="s-btn s-btn-gold">Save Collection Theme &amp; Transition</button>
                </section>
            </aside>

            {{-- Center: Interactive Canvas Editor & Layer Addition Toolbar --}}
            <section class="panel" style="border-color: var(--hairline-strong);">
                <div class="panel-head">
                    <div>
                        <h1 class="panel-title" style="font-size: 0.95rem;">
                            Canvas Editor · <span id="editing-view-title" style="color: var(--gold-light);">View</span>
                        </h1>
                        <span class="sub-label">Drag layers to move, bottom-right handle to resize. Checkered cutouts let OBS Cameras shine through.</span>
                    </div>
                    <div class="bar-group">
                        <select id="add-layer-kind" class="s-select" style="inline-size: auto;">
                            @foreach ($layerTypes as $lKey => $lLabel)
                                <option value="{{ $lKey }}">{{ $lLabel }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="btn-add-layer" class="s-btn s-btn-gold">+ Add Layer</button>
                    </div>
                </div>

                <div id="canvas-stage-wrap" class="canvas-stage-wrap">
                    <div id="canvas-logical" class="canvas-logical"></div>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span class="sub-label" id="canvas-meta-readout">Logical coordinate space: 1920 × 1080</span>
                    <div class="bar-group">
                        <button type="button" id="btn-fit-layer" class="s-btn s-btn-xs">Fit Full Canvas</button>
                        <button type="button" id="btn-center-layer" class="s-btn s-btn-xs">Center Selected</button>
                    </div>
                </div>
            </section>

            {{-- Right: Layer Stack, Layer Properties & View Logic Inspector --}}
            <aside class="stack">
                <section class="panel">
                    <div class="tab-row" role="tablist">
                        <button type="button" class="tab-btn" role="tab" aria-selected="true" data-inspector-tab="layer">Layer &amp; Animation</button>
                        <button type="button" class="tab-btn" role="tab" aria-selected="false" data-inspector-tab="view">View, Theme &amp; Logic</button>
                    </div>

                    <div id="inspector-tab-layer" class="stack">
                        <div class="panel-head">
                            <h2 class="panel-title">View Layers</h2>
                            <span class="sub-label" id="layer-stack-count">0 layers</span>
                        </div>
                        <div id="view-layers-list" class="list-scroll" style="max-block-size: 11rem;"></div>

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

                            <div id="layer-type-fields" class="stack" style="padding: 0.5rem; border-radius: 0.5rem; background: var(--bg-chrome); border: 1px solid var(--hairline);"></div>

                            <div class="grid-2">
                                <button type="button" id="lp-delete-btn" class="s-btn s-btn-danger">Delete Layer</button>
                                <button type="button" id="lp-save-btn" class="s-btn s-btn-gold">Save View</button>
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
                                <option value="theme">Opaque Collection Gradient (Cutouts Punch Alpha)</option>
                                <option value="solid">Solid Color (Cutouts Punch Alpha)</option>
                                <option value="transparent">Full Transparent Overlay Canvas</option>
                                <option value="asset">Uploaded Image / Video Backdrop</option>
                            </select>
                        </label>
                        <div class="grid-2">
                            <label class="stack">
                                <span class="sub-label">Solid Bg Color</span>
                                <input type="color" id="vp-bg-color" class="s-input" style="padding: 0.15rem;">
                            </label>
                            <label class="stack">
                                <span class="sub-label">Bg Media Asset</span>
                                <select id="vp-bg-asset" class="s-select">
                                    <option value="">None</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid-2">
                            <label class="stack">
                                <span class="sub-label">View Accent Override</span>
                                <input type="text" id="vp-theme-accent" class="s-input" placeholder="Inherit (#CDAD69)">
                            </label>
                            <label class="stack">
                                <span class="sub-label">View Surface Override</span>
                                <input type="text" id="vp-theme-surface" class="s-input" placeholder="Inherit (#0B0A08)">
                            </label>
                        </div>

                        <div class="grid-2">
                            <label class="stack">
                                <span class="sub-label">View Transition Override</span>
                                <select id="vp-tx-type" class="s-select">
                                    <option value="">Inherit Collection Default</option>
                                    @foreach ($transitionTypes as $txKey => $txLabel)
                                        <option value="{{ $txKey }}">{{ $txLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="stack">
                                <span class="sub-label">Transition ms</span>
                                <input type="number" id="vp-tx-dur" class="s-input" placeholder="450">
                            </label>
                        </div>

                        <div class="stack" style="padding: 0.55rem; border-radius: 0.5rem; background: var(--bg-chrome); border: 1px solid var(--hairline);">
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

                        <button type="button" id="vp-save-btn" class="s-btn s-btn-gold">Save View &amp; Logic Rules</button>
                    </div>
                </section>
            </aside>
        </main>

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
                'deleteAssetTpl' => route('studio.collections.assets.destroy', ['studio' => $studio->slug, 'asset' => '__ASSET__'], false),
            ];
        @endphp
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const studioSlug = @json($studio->slug);
                let colData = @json($collectionPayload);
                let selectedViewId = Number(colData.active_view_id || colData.views?.[0]?.id || 0);
                let selectedLayerId = null;
                let dragActive = false;

                const endpoints = @json($builderEndpoints);

                const showToast = (msg) => {
                    const el = document.getElementById('builder-toast');
                    if (!el) return;
                    el.textContent = msg;
                    el.hidden = false;
                    clearTimeout(el._timer);
                    el._timer = setTimeout(() => { el.hidden = true; }, 2600);
                };

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
                    document.getElementById('canvas-meta-readout').textContent = `Logical coordinate space: ${w} × ${h} px`;
                };

                if (window.ResizeObserver) {
                    new ResizeObserver(updateCanvasScale).observe(document.getElementById('canvas-stage-wrap'));
                }
                window.addEventListener('resize', updateCanvasScale);

                const renderCanvas = () => {
                    if (dragActive) return;
                    const logical = document.getElementById('canvas-logical');
                    const view = getCurrentView();
                    if (!logical || !view) return;

                    document.getElementById('editing-view-title').textContent = view.name || 'View';
                    logical.innerHTML = '';

                    const theme = { ...(colData.theme || {}), ...(view.theme_override || {}) };
                    const bg = document.createElement('div');
                    bg.className = 'canvas-backdrop';
                    if (view.background_mode === 'transparent') {
                        bg.style.background = 'transparent';
                    } else if (view.background_mode === 'solid') {
                        bg.style.background = view.background_color || '#0B0A08';
                    } else if (view.background_mode === 'asset' && view.background_asset_url) {
                        bg.style.background = `url(${view.background_asset_url}) center / cover no-repeat, #080807`;
                    } else {
                        bg.style.background = `radial-gradient(circle at 18% 20%, ${theme.accent_color || '#CDAD69'}33, transparent 48%), linear-gradient(155deg, ${theme.surface_color || '#0B0A08'} 0%, #050507 100%)`;
                    }
                    logical.appendChild(bg);

                    const layers = [...(view.layers || [])].sort((a, b) => Number(a.z_index || 0) - Number(b.z_index || 0));
                    const wrap = document.getElementById('canvas-stage-wrap');
                    const wLogical = Math.max(320, Number(colData.width || 1920));
                    const hLogical = Math.max(180, Number(colData.height || 1080));

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
                        if (layer.type === 'camera_cutout') {
                            box.style.backgroundImage = 'linear-gradient(45deg, rgba(255,255,255,0.08) 25%, transparent 25%), linear-gradient(-45deg, rgba(255,255,255,0.08) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.08) 75%), linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.08) 75%)';
                            box.style.backgroundSize = '32px 32px';
                            box.style.backgroundColor = 'rgba(0,0,0,0.45)';
                            box.style.borderColor = p.border_color || theme.accent_color || '#CDAD69';
                        } else if (layer.type === 'asset' && p.url) {
                            if (p.media_kind === 'video' || /\.webm$|\.mp4$/i.test(p.url)) {
                                const vid = document.createElement('video');
                                vid.src = p.url;
                                vid.muted = true;
                                vid.style.cssText = 'width:100%;height:100%;object-fit:contain;pointer-events:none;';
                                box.appendChild(vid);
                            } else {
                                const img = document.createElement('img');
                                img.src = p.url;
                                img.alt = '';
                                img.style.cssText = `width:100%;height:100%;object-fit:${p.fit || 'contain'};pointer-events:none;`;
                                box.appendChild(img);
                            }
                        } else if (layer.type === 'text') {
                            const preview = document.createElement('div');
                            preview.textContent = p.text || 'Text';
                            preview.style.cssText = `width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:${Number(p.font_size || 48)}px;font-weight:700;color:${p.text_color || '#FAF7EE'};pointer-events:none;`;
                            box.appendChild(preview);
                        } else {
                            box.style.background = `${theme.surface_color || '#121210'}CC`;
                        }

                        const tag = document.createElement('div');
                        tag.className = 'canvas-tag';
                        tag.textContent = `${layer.name || layer.type}`;
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
                                    layer.w = Math.max(40, Math.round(init.w + dx));
                                    layer.h = Math.max(24, Math.round(init.h + dy));
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
                    views.forEach((v, idx) => {
                        const row = document.createElement('div');
                        row.className = `item-row${Number(v.id) === Number(selectedViewId) ? ' is-selected' : ''}`;
                        const top = document.createElement('div');
                        top.className = 'row-top';

                        const selBtn = document.createElement('button');
                        selBtn.type = 'button';
                        selBtn.className = 'row-btn';
                        selBtn.textContent = (Number(v.id) === Number(colData.active_view_id) ? '● ' : '') + v.name;
                        selBtn.addEventListener('click', () => {
                            selectedViewId = Number(v.id);
                            selectedLayerId = null;
                            renderAll();
                        });

                        const actions = document.createElement('div');
                        actions.style.cssText = 'display:flex;gap:0.2rem;';

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

                    if (bgAssetSel) {
                        bgAssetSel.innerHTML = '<option value="">None</option>';
                        assets.filter((a) => ['image', 'video'].includes(a.type)).forEach((a) => {
                            const o = document.createElement('option');
                            o.value = String(a.id);
                            o.textContent = `${a.name} (${a.type})`;
                            bgAssetSel.appendChild(o);
                        });
                    }

                    list.innerHTML = assets.length === 0 ? '<span class="sub-label">Upload images, looping videos, or .webm stingers above.</span>' : '';
                    assets.forEach((a) => {
                        const row = document.createElement('div');
                        row.className = 'item-row';
                        const top = document.createElement('div');
                        top.className = 'row-top';
                        const lbl = document.createElement('span');
                        lbl.className = 'row-btn';
                        lbl.style.cursor = 'default';
                        lbl.textContent = `${a.name} [${a.type}]`;

                        const btns = document.createElement('div');
                        btns.style.cssText = 'display:flex;gap:0.2rem;';

                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 's-btn s-btn-xs s-btn-gold';
                        addBtn.textContent = '+ Canvas';
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
                            renderCanvas();
                            renderLayerStack();
                            populateLayerInspector();
                        });

                        const rmBtn = document.createElement('button');
                        rmBtn.type = 'button';
                        rmBtn.className = 's-btn s-btn-xs s-btn-danger';
                        rmBtn.textContent = '×';
                        rmBtn.addEventListener('click', async () => {
                            await apiJson(endpoints.deleteAssetTpl.replace('__ASSET__', a.id), 'DELETE', {});
                            colData.assets = (colData.assets || []).filter((x) => x.id !== a.id);
                            renderAssetsList();
                            showToast('Asset deleted');
                        });

                        btns.append(addBtn, rmBtn);
                        top.append(lbl, btns);
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

                        const visBtn = document.createElement('button');
                        visBtn.type = 'button';
                        visBtn.className = `s-btn s-btn-xs${layer.visible !== false ? ' is-active' : ''}`;
                        visBtn.textContent = layer.visible !== false ? 'On' : 'Off';
                        visBtn.addEventListener('click', () => {
                            layer.visible = layer.visible === false;
                            renderCanvas();
                            renderLayerStack();
                        });

                        const lockBtn = document.createElement('button');
                        lockBtn.type = 'button';
                        lockBtn.className = `s-btn s-btn-xs${layer.locked ? ' is-active' : ''}`;
                        lockBtn.textContent = layer.locked ? 'Lock' : 'Free';
                        lockBtn.addEventListener('click', () => {
                            layer.locked = !layer.locked;
                            renderCanvas();
                            renderLayerStack();
                        });

                        top.append(selBtn, visBtn, lockBtn);
                        row.appendChild(top);
                        list.appendChild(row);
                    });
                };

                const populateLayerInspector = () => {
                    const card = document.getElementById('layer-props-card');
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) {
                        card.hidden = true;
                        return;
                    }
                    card.hidden = false;

                    const bind = (id, val, onChange) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.value = val ?? '';
                        el.oninput = el.onchange = () => {
                            onChange(el.value);
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
                    const addExtra = (label, inputEl) => {
                        const w = document.createElement('label');
                        w.className = 'stack';
                        const s = document.createElement('span');
                        s.className = 'sub-label';
                        s.textContent = label;
                        w.append(s, inputEl);
                        extra.appendChild(w);
                    };

                    if (layer.type === 'text') {
                        const ta = document.createElement('textarea');
                        ta.className = 's-textarea';
                        ta.rows = 2;
                        ta.value = layer.props.text || '';
                        ta.oninput = () => { layer.props.text = ta.value; renderCanvas(); };
                        addExtra('Text Content', ta);

                        const fs = document.createElement('input');
                        fs.type = 'number';
                        fs.className = 's-input';
                        fs.value = layer.props.font_size || 48;
                        fs.oninput = () => { layer.props.font_size = Number(fs.value || 48); renderCanvas(); };
                        addExtra('Font Size (px)', fs);
                    } else if (layer.type === 'timer') {
                        const sel = document.createElement('select');
                        sel.className = 's-select';
                        sel.innerHTML = '<option value="countdown">Countdown</option><option value="countup">Stopwatch (Count Up)</option><option value="clock">Local Wall Clock</option>';
                        sel.value = layer.props.mode || 'countdown';
                        sel.onchange = () => { layer.props.mode = sel.value; };
                        addExtra('Timer Mode', sel);

                        const lbl = document.createElement('input');
                        lbl.className = 's-input';
                        lbl.value = layer.props.label || 'LIVE IN';
                        lbl.oninput = () => { layer.props.label = lbl.value; };
                        addExtra('Timer Label', lbl);
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
                            renderCanvas();
                        };
                        addExtra('Media Asset', sel);
                    } else if (layer.type === 'embed') {
                        const inp = document.createElement('input');
                        inp.type = 'url';
                        inp.className = 's-input';
                        inp.placeholder = 'https://vdo.ninja/?view=...';
                        inp.value = layer.props.url || '';
                        inp.oninput = () => { layer.props.url = inp.value; };
                        addExtra('Widget / VDO.Ninja Embed URL', inp);
                    } else if (layer.type === 'camera_cutout') {
                        const inp = document.createElement('input');
                        inp.className = 's-input';
                        inp.value = layer.props.border_color || '';
                        inp.placeholder = 'Default accent (#CDAD69)';
                        inp.oninput = () => { layer.props.border_color = inp.value; renderCanvas(); };
                        addExtra('Cutout Border Color', inp);
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
                };

                const populateCollectionSettings = () => {
                    const th = colData.theme || {};
                    const tx = colData.default_transition || {};
                    document.getElementById('col-accent-color').value = th.accent_color || '#CDAD69';
                    document.getElementById('col-surface-color').value = th.surface_color || '#0B0A08';
                    document.getElementById('col-text-color').value = th.text_color || '#FAF7EE';
                    document.getElementById('col-sweep-color').value = tx.sweep_color || th.accent_color || '#CDAD69';
                    document.getElementById('col-default-tx').value = tx.type || 'fade';
                    document.getElementById('col-tx-duration').value = String(tx.duration_ms ?? 450);
                    document.getElementById('col-tx-swap').value = String(tx.swap_point_ms ?? 225);
                    document.getElementById('col-tx-stinger').value = String(tx.stinger_asset_id || '');
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

                const saveCurrentView = async () => {
                    const view = getCurrentView();
                    if (!view) return;
                    const res = await apiJson(endpoints.updateViewTpl.replace('__VIEW__', view.id), 'PUT', {
                        name: document.getElementById('vp-name').value.trim() || view.name,
                        background_mode: document.getElementById('vp-bg-mode').value,
                        background_color: document.getElementById('vp-bg-color').value,
                        background_asset_id: document.getElementById('vp-bg-asset').value ? Number(document.getElementById('vp-bg-asset').value) : null,
                        theme_override: {
                            accent_color: document.getElementById('vp-theme-accent').value.trim() || null,
                            surface_color: document.getElementById('vp-theme-surface').value.trim() || null,
                        },
                        transition_override: document.getElementById('vp-tx-type').value
                            ? { type: document.getElementById('vp-tx-type').value, duration_ms: Number(document.getElementById('vp-tx-dur').value || 450) }
                            : null,
                        layers: view.layers || [],
                        logic_rules: {
                            on_timer_end_action: document.getElementById('vp-logic-action').value,
                            on_timer_end_target_view_id: document.getElementById('vp-logic-target-view').value ? Number(document.getElementById('vp-logic-target-view').value) : null,
                        },
                    });
                    colData = res.collection;
                    renderAll();
                    showToast('View saved & synced to live stage');
                };

                // Tabs
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

                document.getElementById('btn-add-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    if (!view) return;
                    const type = document.getElementById('add-layer-kind').value;
                    const id = 'lyr_' + Math.random().toString(36).slice(2, 10);
                    view.layers = view.layers || [];
                    view.layers.push({
                        id,
                        type,
                        name: type.replace('_', ' ').toUpperCase(),
                        x: 260,
                        y: 180,
                        w: type === 'ticker' ? 1792 : 640,
                        h: type === 'ticker' ? 64 : 260,
                        rotation: 0,
                        z_index: view.layers.length + 1,
                        opacity: 1,
                        visible: true,
                        locked: false,
                        radius: 16,
                        enter_animation: 'fade',
                        enter_duration_ms: 350,
                        enter_delay_ms: 0,
                        loop_animation: 'none',
                        condition_tag: 'always',
                        props: { text: 'Live Text', label: 'TIMER', mode: 'countdown' },
                    });
                    selectedLayerId = id;
                    renderAll();
                });

                document.getElementById('lp-delete-btn').addEventListener('click', () => {
                    const view = getCurrentView();
                    if (!view || !selectedLayerId) return;
                    view.layers = (view.layers || []).filter((l) => String(l.id) !== String(selectedLayerId));
                    selectedLayerId = null;
                    renderAll();
                });

                document.getElementById('btn-fit-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) return;
                    layer.x = 0; layer.y = 0; layer.w = Number(colData.width || 1920); layer.h = Number(colData.height || 1080);
                    renderCanvas(); populateLayerInspector();
                });

                document.getElementById('btn-center-layer').addEventListener('click', () => {
                    const view = getCurrentView();
                    const layer = (view?.layers || []).find((l) => String(l.id) === String(selectedLayerId));
                    if (!layer) return;
                    layer.x = Math.round((Number(colData.width || 1920) - Number(layer.w || 200)) / 2);
                    layer.y = Math.round((Number(colData.height || 1080) - Number(layer.h || 80)) / 2);
                    renderCanvas(); populateLayerInspector();
                });

                document.getElementById('asset-file-input').addEventListener('change', async (ev) => {
                    const file = ev.target.files?.[0];
                    if (!file) return;
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('type', document.getElementById('asset-upload-type').value);
                    const res = await fetch(endpoints.uploadAsset, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: fd,
                    });
                    const data = await res.json();
                    ev.target.value = '';
                    if (!res.ok) {
                        showToast(data.message || 'Upload failed');
                        return;
                    }
                    colData = data.collection;
                    renderAssetsList();
                    showToast(`Uploaded: ${data.asset.name}`);
                });

                document.getElementById('btn-save-collection-settings').addEventListener('click', async () => {
                    const res = await apiJson(endpoints.updateCollection, 'PUT', {
                        theme: {
                            accent_color: document.getElementById('col-accent-color').value,
                            surface_color: document.getElementById('col-surface-color').value,
                            text_color: document.getElementById('col-text-color').value,
                        },
                        default_transition: {
                            type: document.getElementById('col-default-tx').value,
                            duration_ms: Number(document.getElementById('col-tx-duration').value || 450),
                            swap_point_ms: Number(document.getElementById('col-tx-swap').value || 225),
                            stinger_asset_id: document.getElementById('col-tx-stinger').value ? Number(document.getElementById('col-tx-stinger').value) : null,
                            sweep_color: document.getElementById('col-sweep-color').value,
                        },
                    });
                    colData = res.collection;
                    renderAll();
                    showToast('Collection theme & transition updated live');
                });

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
