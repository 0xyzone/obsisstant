@php
    $liveState = $studio->live_state ?? [];
    $scenes = $studio->getScenes();
    $sceneItemsByScene = $studio->getSceneItemsByScene();
    $audioInputs = $studio->getAudioInputs();
    $allInputs = $studio->getAllInputs();
    $inputKinds = $studio->getInputKinds();
    $outputs = $studio->getOutputs();
    $stats = $studio->getStats();
    $dims = $studio->getVideoDimensions();
    $programScene = $studio->getCurrentProgramScene();
    $previewScene = $studio->getCurrentPreviewScene();
    $initialEditingScene = $programScene ?: ($scenes[0]['sceneName'] ?? '');
    $screensPayload = $screens->map(fn ($scr) => [
        'id' => $scr->id,
        'name' => $scr->name,
        'slug' => $scr->slug,
        'template_type' => $scr->template_type,
        'overlay_url' => $scr->getOverlayUrl(),
        'state' => $scr->getMergedState(),
    ])->values();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $studio->name }} · Studio Control Center · {{ config('app.name', 'Obsisstant') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;
                --bg-canvas: #070706;
                --bg-chrome: #0B0B0A;
                --bg-card: #121210;
                --bg-raised: #1B1A17;
                --bg-bronze: #2D1F11;
                --gold-accent: #CDAD69;
                --gold-light: #F4E185;
                --hairline: rgba(205, 173, 105, 0.16);
                --hairline-strong: rgba(205, 173, 105, 0.36);
                --ink-primary: #FAF7EE;
                --ink-secondary: #C8C0AF;
                --ink-muted: #8E8777;
                --positive: #4CC38A;
                --danger: #E5484D;
                --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
            }

            *, *::before, *::after {
                box-sizing: border-box;
            }

            html, body {
                margin: 0;
                min-block-size: 100dvb;
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, sans-serif;
                font-size: 0.875rem;
                line-height: 1.5;
                -webkit-font-smoothing: antialiased;
                overflow-x: clip;
            }

            :is(a, button, input, select, textarea, [tabindex]):focus-visible {
                outline: 2px solid var(--gold-light);
                outline-offset: 2px;
            }

            [hidden] {
                display: none !important;
            }

            /* Top Studio Header */
            .studio-topbar {
                position: sticky;
                inset-block-start: 0;
                z-index: 40;
                min-block-size: 3.75rem;
                padding: 0.5rem clamp(0.75rem, 2vw, 1.5rem);
                background: rgba(11, 11, 10, 0.88);
                -webkit-backdrop-filter: blur(16px);
                backdrop-filter: blur(16px);
                border-block-end: 1px solid var(--hairline);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .studio-bar-left,
            .studio-bar-right {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.625rem;
            }

            .brand-lockup {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--ink-primary);
                text-decoration: none;
                font-weight: 600;
                font-size: 0.9375rem;
            }

            .obs-brand-version-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                border: 1px solid var(--hairline-strong);
                background-color: rgba(205, 173, 105, 0.12);
                color: var(--gold-light);
                font-variant-numeric: tabular-nums;
                font-size: 0.6875rem;
                font-weight: 500;
                text-decoration: none;
            }

            .studio-divider {
                inline-size: 1px;
                block-size: 1.25rem;
                background-color: var(--hairline);
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
                border: 1px solid rgba(255, 255, 255, 0.1);
                background-color: var(--bg-raised);
                color: var(--ink-secondary);
            }

            .obs-reverb-dot {
                inline-size: 0.4375rem;
                block-size: 0.4375rem;
                border-radius: 50%;
                background-color: var(--ink-muted);
            }

            .obs-reverb-pill.is-active {
                border-color: rgba(76, 195, 138, 0.34);
                background-color: rgba(76, 195, 138, 0.08);
                color: #9EE6C3;
            }

            .obs-reverb-pill.is-active .obs-reverb-dot {
                background-color: var(--positive);
            }

            .obs-reverb-pill.is-offline {
                border-color: rgba(229, 72, 77, 0.32);
                background-color: rgba(229, 72, 77, 0.08);
                color: #F4A3A6;
            }

            .obs-reverb-pill.is-offline .obs-reverb-dot {
                background-color: var(--danger);
            }

            /* Controls & Buttons */
            .s-input,
            .s-select,
            .s-textarea {
                min-block-size: 2.25rem;
                padding: 0.375rem 0.625rem;
                border-radius: 0.5rem;
                border: 1px solid var(--hairline-strong);
                background-color: var(--bg-chrome);
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.8125rem;
            }

            .s-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.375rem;
                min-block-size: 2.125rem;
                padding: 0.3125rem 0.75rem;
                border-radius: 0.5rem;
                border: 1px solid var(--hairline);
                background-color: var(--bg-raised);
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                text-decoration: none;
                cursor: pointer;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out), color 140ms var(--ease-out), transform 120ms var(--ease-out);
            }

            .s-btn:hover {
                border-color: var(--hairline-strong);
                background-color: #23221E;
            }

            @media (prefers-reduced-motion: no-preference) {
                .s-btn:active {
                    transform: scale(0.985);
                }
            }

            .s-btn-gold {
                background-image: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border-color: rgba(244, 225, 133, 0.45);
            }

            .s-btn-gold:hover {
                background-image: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%);
                color: #070706;
            }

            .s-btn-danger {
                border-color: rgba(229, 72, 77, 0.35);
                background-color: rgba(229, 72, 77, 0.12);
                color: #F4A3A6;
            }

            .s-btn-danger:hover {
                border-color: rgba(229, 72, 77, 0.65);
                background-color: rgba(229, 72, 77, 0.22);
            }

            .s-btn.is-active {
                border-color: var(--gold-accent);
                background-color: var(--bg-bronze);
                color: var(--gold-light);
            }

            .s-btn-xs {
                min-block-size: 1.75rem;
                padding: 0.125rem 0.5rem;
                font-size: 0.6875rem;
            }

            .stat-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.25rem 0.5625rem;
                border-radius: 0.375rem;
                background-color: var(--bg-raised);
                border: 1px solid var(--hairline);
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                color: var(--ink-secondary);
            }

            .stat-chip.is-live {
                border-color: rgba(76, 195, 138, 0.34);
                color: #9EE6C3;
            }

            /* Fullscreen Workstation 3-Column Layout */
            .workspace-grid {
                padding: 0.875rem clamp(0.75rem, 2vw, 1.5rem) 2rem;
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                align-items: start;
            }

            @media (min-width: 72rem) {
                .workspace-grid {
                    grid-template-columns: 18rem minmax(0, 1fr) 22rem;
                }
            }

            .studio-column {
                display: grid;
                gap: 0.875rem;
                min-inline-size: 0;
            }

            .dock-panel {
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.75rem;
            }

            .dock-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-block-end: 0.5rem;
                border-block-end: 1px solid var(--hairline);
            }

            .dock-title {
                margin: 0;
                font-size: 0.8125rem;
                font-weight: 600;
                color: var(--ink-primary);
            }

            .dock-subtitle {
                font-size: 0.75rem;
                color: var(--ink-muted);
            }

            /* Scene Items List */
            .scene-list {
                display: grid;
                gap: 0.375rem;
            }

            .scene-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.375rem;
                padding: 0.375rem 0.5rem;
                border-radius: 0.5rem;
                background-color: var(--bg-raised);
                border: 1px solid transparent;
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out);
            }

            .scene-row.is-editing {
                border-color: var(--gold-accent);
                background-color: rgba(205, 173, 105, 0.12);
            }

            .scene-row.is-program {
                box-shadow: inset 3px 0 0 var(--positive);
            }

            .scene-row-btn {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                background: transparent;
                border: 0;
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.8125rem;
                font-weight: 500;
                text-align: start;
                cursor: pointer;
                padding: 0.125rem;
                min-inline-size: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .scene-tag-pgm {
                padding: 0.0625rem 0.375rem;
                border-radius: 0.25rem;
                background-color: rgba(76, 195, 138, 0.16);
                color: #9EE6C3;
                font-size: 0.625rem;
                font-weight: 600;
            }

            .scene-actions {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            /* 16:9 Interactive Live Stage */
            .stage-panel {
                background-color: var(--bg-card);
                border: 1px solid var(--hairline-strong);
                border-radius: 1rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.75rem;
            }

            .stage-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .stage-canvas-outer {
                position: relative;
                inline-size: 100%;
                aspect-ratio: 16 / 9;
                border-radius: 0.625rem;
                border: 1px solid var(--hairline-strong);
                background-color: #080807;
                background-image:
                    linear-gradient(to right, rgba(205, 173, 105, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(205, 173, 105, 0.05) 1px, transparent 1px);
                background-size: 6.25% 11.111%;
                overflow: hidden;
                user-select: none;
                touch-action: none;
            }

            .stage-underlay {
                position: absolute;
                inset: 0;
                inline-size: 100%;
                block-size: 100%;
                object-fit: contain;
                pointer-events: none;
            }

            .stage-underlay[hidden] {
                display: none;
            }

            .stage-layers-surface {
                position: absolute;
                inset: 0;
            }

            .stage-item-box {
                position: absolute;
                border: 1.5px solid rgba(205, 173, 105, 0.65);
                background-color: rgba(205, 173, 105, 0.08);
                border-radius: 0.25rem;
                cursor: grab;
                transition: border-color 120ms ease, background-color 120ms ease;
            }

            .stage-item-box:hover {
                border-color: var(--gold-light);
                background-color: rgba(205, 173, 105, 0.14);
            }

            .stage-item-box.is-selected {
                border: 2px solid var(--gold-light);
                background-color: rgba(244, 225, 133, 0.14);
                z-index: 20 !important;
            }

            .stage-item-box.is-hidden {
                opacity: 0.34;
                border-style: dashed;
            }

            .stage-item-box.is-locked {
                cursor: default;
                border-color: rgba(142, 135, 119, 0.55);
            }

            .stage-item-label {
                position: absolute;
                inset-inline-start: 0.25rem;
                inset-block-start: 0.25rem;
                max-inline-size: calc(100% - 0.5rem);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                padding: 0.0625rem 0.375rem;
                border-radius: 0.25rem;
                background: rgba(7, 7, 6, 0.86);
                color: var(--ink-primary);
                font-size: 0.6875rem;
                font-weight: 500;
                pointer-events: none;
            }

            .stage-resize-handle {
                position: absolute;
                inset-inline-end: -4px;
                inset-block-end: -4px;
                inline-size: 0.75rem;
                block-size: 0.75rem;
                border-radius: 0.125rem;
                background-color: var(--gold-light);
                border: 1px solid #070706;
                cursor: nwse-resize;
            }

            .stage-item-box.is-locked .stage-resize-handle {
                display: none;
            }

            .stage-footer-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                font-size: 0.75rem;
                color: var(--ink-muted);
            }

            /* Inspector Tabs & Layer Stack */
            .tab-row {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.25rem;
                padding: 0.25rem;
                border-radius: 0.5rem;
                background-color: var(--bg-chrome);
                border: 1px solid var(--hairline);
            }

            .tab-btn {
                min-block-size: 2rem;
                border-radius: 0.375rem;
                border: 0;
                background: transparent;
                color: var(--ink-muted);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
            }

            .tab-btn[aria-selected="true"] {
                background-color: var(--bg-raised);
                color: var(--gold-light);
            }

            .tab-panel[hidden] {
                display: none;
            }

            .layer-list {
                display: grid;
                gap: 0.375rem;
                max-block-size: 16rem;
                overflow: auto;
            }

            .layer-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.375rem;
                padding: 0.375rem 0.5rem;
                border-radius: 0.5rem;
                background-color: var(--bg-raised);
                border: 1px solid transparent;
            }

            .layer-row.is-selected {
                border-color: var(--gold-accent);
                background-color: rgba(205, 173, 105, 0.12);
            }

            .transform-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.5rem;
            }

            .field-stack {
                display: grid;
                gap: 0.25rem;
            }

            .field-label {
                font-size: 0.6875rem;
                font-weight: 500;
                color: var(--ink-muted);
            }

            .audio-item {
                padding: 0.5rem 0.625rem;
                border-radius: 0.5rem;
                background-color: var(--bg-raised);
                border: 1px solid var(--hairline);
                display: grid;
                gap: 0.375rem;
            }

            .audio-item input[type="range"] {
                inline-size: 100%;
                accent-color: var(--gold-accent);
            }

            /* Native Top-Layer Modals with Smooth Entry/Exit & Fallback */
            dialog.obs-dialog {
                margin: auto;
                inline-size: min(92dvw, 34rem);
                max-block-size: 88dvh;
                padding: 0;
                border-radius: 1rem;
                border: 1px solid var(--hairline-strong);
                background-color: var(--bg-card);
                color: var(--ink-primary);
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85);
                opacity: 0;
                transition-property: opacity, transform, display, overlay;
                transition-duration: 200ms;
                transition-timing-function: var(--ease-out);
                transition-behavior: allow-discrete;
            }

            @media (prefers-reduced-motion: no-preference) {
                dialog.obs-dialog {
                    transform: scale(0.96);
                }
            }

            dialog.obs-dialog[open]:where(:not([data-closing])) {
                opacity: 1;

                @starting-style {
                    opacity: 0;
                }

                @media (prefers-reduced-motion: no-preference) {
                    transform: scale(1);

                    @starting-style {
                        transform: scale(0.96);
                    }
                }
            }

            dialog.obs-dialog::backdrop {
                background-color: rgba(7, 7, 6, 0);
                transition-property: background-color, display, overlay;
                transition-duration: 200ms;
                transition-behavior: allow-discrete;
            }

            dialog.obs-dialog[open]:where(:not([data-closing]))::backdrop {
                background-color: rgba(7, 7, 6, 0.72);

                @starting-style {
                    background-color: rgba(7, 7, 6, 0);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                dialog.obs-dialog,
                dialog.obs-dialog::backdrop {
                    transition-duration: 90ms;
                }
            }

            .dialog-inner {
                padding: 1.25rem;
                display: grid;
                gap: 1rem;
            }

            .dialog-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding-block-end: 0.625rem;
                border-block-end: 1px solid var(--hairline);
            }

            .dialog-title {
                margin: 0;
                font-size: 1rem;
                font-weight: 600;
            }

            .toast-bar {
                position: fixed;
                inset-inline-end: 1rem;
                inset-block-end: 1rem;
                z-index: 60;
                max-inline-size: 24rem;
                padding: 0.625rem 0.875rem;
                border-radius: 0.625rem;
                border: 1px solid var(--hairline-strong);
                background: rgba(18, 18, 16, 0.94);
                color: var(--gold-light);
                font-size: 0.8125rem;
                font-weight: 500;
            }

            .toast-bar[hidden] {
                display: none;
            }

            @media (forced-colors: active) {
                .dock-panel,
                .stage-panel,
                .scene-row,
                .layer-row,
                .audio-item,
                dialog.obs-dialog,
                .obs-reverb-pill,
                .obs-brand-version-pill {
                    background: Canvas;
                    color: CanvasText;
                    border: 1px solid CanvasText;
                }
            }
        </style>
    </head>
    <body>
        <header class="studio-topbar">
            <div class="studio-bar-left">
                <a href="{{ route('home') }}" class="brand-lockup" aria-label="Obsisstant Home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CDAD69" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                        <circle cx="12" cy="12" r="2.5" fill="#CDAD69"/>
                    </svg>
                    <span>Obsisstant</span>
                </a>
                <a href="{{ route('changelog') }}" class="obs-brand-version-pill">v{{ $currentVersion }}</a>
                <span class="studio-divider" aria-hidden="true"></span>
                <label for="studio-switcher" class="sr-only" style="position:absolute;inline-size:1px;block-size:1px;overflow:hidden;">Studio</label>
                <select id="studio-switcher" class="s-select">
                    @foreach ($studios as $item)
                        <option value="{{ route('studio.show', ['studio' => $item->slug]) }}" @selected($item->id === $studio->id)>
                            {{ $item->name }} ({{ ucfirst($item->status) }})
                        </option>
                    @endforeach
                </select>
                <span id="studio-live-badge" class="stat-chip {{ $studio->isOnline() ? 'is-live' : '' }}">
                    {{ ucfirst($studio->status) }}
                </span>
                <span id="studio-stats-chip" class="stat-chip">
                    {{ isset($stats['activeFps']) ? $stats['activeFps'].' FPS · '.($stats['cpuUsage'] ?? 0).'% CPU' : 'Telemetry idle' }}
                </span>
            </div>

            <div class="studio-bar-right">
                <x-reverb-status-pill />
                <a href="{{ route('obs.deck.show', ['studio' => $studio->slug]) }}" class="s-btn">Mobile Deck</a>
                <a href="{{ route('knowledge-base') }}" class="s-btn">Knowledge Base</a>
                <a href="{{ url('/admin') }}" class="s-btn s-btn-gold">Admin Panel</a>
            </div>
        </header>

        <main class="workspace-grid">
            {{-- Left Column: Scenes Manager, Broadcast Outputs & Macros --}}
            <section class="studio-column" aria-label="Scenes, Outputs and Macros">
                <div class="dock-panel">
                    <div class="dock-head">
                        <div>
                            <h2 class="dock-title">Scenes &amp; Collections</h2>
                            <div class="dock-subtitle">Click scene to inspect · Live cuts output</div>
                        </div>
                        <button type="button" class="s-btn s-btn-xs" id="sync-obs-btn">Sync</button>
                    </div>

                    <div style="display: grid; gap: 0.25rem; padding-block-end: 0.5rem; border-block-end: 1px solid var(--hairline);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.375rem;">
                            <label for="scene-collection-select" class="field-label">Scene Collection</label>
                            <button type="button" class="s-btn s-btn-xs" commandfor="create-collection-dialog" command="show-modal">+ Collection</button>
                        </div>
                        <select id="scene-collection-select" class="s-select" style="inline-size: 100%; min-block-size: 2rem; font-size: 0.75rem;"></select>
                        <div class="dock-subtitle" style="font-size: 0.6875rem;">Switch or create OBS Scene Collections remotely (rename/delete in OBS desktop menu).</div>
                    </div>

                    <div style="display: flex; gap: 0.375rem;">
                        <input id="new-scene-name" type="text" class="s-input" placeholder="New scene name..." style="flex: 1; min-inline-size: 0;">
                        <button type="button" id="create-scene-btn" class="s-btn s-btn-gold">Add</button>
                    </div>

                    <div id="scenes-list" class="scene-list"></div>
                </div>

                <div class="dock-panel">
                    <div class="dock-head">
                        <h2 class="dock-title">Broadcast Outputs</h2>
                        <span id="studio-mode-label" class="dock-subtitle"></span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.375rem;">
                        <button type="button" id="btn-stream" class="s-btn">Start Stream</button>
                        <button type="button" id="btn-record" class="s-btn">Start Record</button>
                        <button type="button" id="btn-pause-rec" class="s-btn">Pause Rec</button>
                        <button type="button" id="btn-replay" class="s-btn">Save Replay</button>
                        <button type="button" id="btn-vcam" class="s-btn">Virtual Cam</button>
                        <button type="button" id="btn-studio-mode" class="s-btn">Studio Mode</button>
                    </div>
                    <button type="button" id="btn-transition" class="s-btn s-btn-gold" hidden>
                        Trigger Studio Mode Transition
                    </button>
                </div>

                <div class="dock-panel">
                    <div class="dock-head">
                        <h2 class="dock-title">Broadcast Macros</h2>
                        <a href="{{ url('/admin/obs-macros/create') }}" class="s-btn s-btn-xs">+ New</a>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        @forelse ($macros as $macro)
                            <button type="button" class="s-btn" data-run-macro="{{ $macro->id }}">
                                {{ $macro->name }}
                            </button>
                        @empty
                            <span class="dock-subtitle">No macros created yet.</span>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Center Stage: Live View & Interactive Source Layout Builder --}}
            <section class="stage-panel" aria-label="Live Scene Stage">
                <div class="stage-toolbar">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                        <span class="stat-chip" id="stage-editing-label">Scene: {{ $initialEditingScene ?: 'None' }}</span>
                        <button type="button" id="cut-editing-scene-btn" class="s-btn s-btn-gold s-btn-xs">Cut Scene Live</button>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;">
                        <label for="live-view-mode" class="field-label">Live View</label>
                        <select id="live-view-mode" class="s-select" style="min-block-size: 1.875rem; font-size: 0.75rem;">
                            <option value="snapshot" @selected($studio->isOnline())>OBS Pixel Feed (Snapshots)</option>
                            <option value="layout" @selected(! $studio->isOnline())>Layout Grid Only</option>
                            <option value="vcam">Local OBS Virtual Cam (WebRTC)</option>
                        </select>
                        <button
                            type="button"
                            class="s-btn s-btn-gold s-btn-xs"
                            commandfor="add-source-dialog"
                            command="show-modal"
                        >
                            + Add Source
                        </button>
                    </div>
                </div>

                <div id="stage-canvas" class="stage-canvas-outer">
                    <img id="live-preview-img" class="stage-underlay" alt="Live OBS scene output preview" hidden>
                    <video id="webrtc-preview-video" class="stage-underlay" autoplay muted playsinline hidden></video>
                    <div id="stage-layers" class="stage-layers-surface"></div>
                </div>

                <div class="stage-footer-bar">
                    <span id="stage-canvas-meta">Base Canvas: {{ $dims['width'] }} × {{ $dims['height'] }} · Drag boxes to reposition, drag corner to scale</span>
                    <span id="stage-preview-status">Layout mode</span>
                </div>
            </section>

            {{-- Right Column: Source Stack + Inspector / Audio Mixer / Overlay Push --}}
            <aside class="studio-column" aria-label="Inspector and Mixer">
                <div class="dock-panel">
                    <div class="tab-row" role="tablist" aria-label="Inspector tabs">
                        <button type="button" class="tab-btn" role="tab" aria-selected="true" data-tab-btn="sources">Sources</button>
                        <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-btn="audio">Audio</button>
                        <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab-btn="overlays">Overlays</button>
                    </div>

                    {{-- Tab 1: Scene Source Stack & Transform/Settings Inspector --}}
                    <div id="tab-sources" class="tab-panel" style="display: grid; gap: 0.75rem;">
                        <div class="dock-head">
                            <h3 class="dock-title">Scene Layers</h3>
                            <span id="layer-count-meta" class="dock-subtitle">0 sources</span>
                        </div>
                        <div id="layers-list" class="layer-list"></div>

                        <div id="selected-source-card" style="display: grid; gap: 0.625rem; padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);" hidden>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <div>
                                    <div id="sel-source-name" style="font-weight: 600; font-size: 0.875rem; color: var(--gold-light);"></div>
                                    <div id="sel-source-kind" class="dock-subtitle"></div>
                                </div>
                                <div style="display: flex; gap: 0.25rem;">
                                    <button type="button" id="btn-fit-source" class="s-btn s-btn-xs">Fit 16:9</button>
                                    <button type="button" id="btn-center-source" class="s-btn s-btn-xs">Center</button>
                                </div>
                            </div>

                            <div class="transform-grid">
                                <label class="field-stack">
                                    <span class="field-label">X Position</span>
                                    <input type="number" id="tf-x" class="s-input" step="1">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Y Position</span>
                                    <input type="number" id="tf-y" class="s-input" step="1">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Scale X</span>
                                    <input type="number" id="tf-sx" class="s-input" step="0.05" min="0.05">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Scale Y</span>
                                    <input type="number" id="tf-sy" class="s-input" step="0.05" min="0.05">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Rotation (°)</span>
                                    <input type="number" id="tf-rot" class="s-input" step="1">
                                </label>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="button" id="btn-apply-transform" class="s-btn s-btn-gold" style="inline-size: 100%;">Apply Transform</button>
                                </div>
                            </div>

                            <div style="display: grid; gap: 0.375rem; padding-block-start: 0.375rem; border-block-start: 1px solid var(--hairline);">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <span class="field-label">OBS Source Settings (JSON)</span>
                                    <button type="button" id="btn-inspect-settings" class="s-btn s-btn-xs">Load Settings</button>
                                </div>
                                <textarea id="source-settings-json" class="s-textarea" rows="4" placeholder='Click "Load Settings" to fetch live properties from OBS...' style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem;"></textarea>
                                <button type="button" id="btn-save-settings" class="s-btn">Save Settings to OBS</button>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Audio Mixer --}}
                    <div id="tab-audio" class="tab-panel" hidden>
                        <div id="audio-mixer-list" style="display: grid; gap: 0.5rem;"></div>
                    </div>

                    {{-- Tab 3: Live Overlay Push & Recent Command Log --}}
                    <div id="tab-overlays" class="tab-panel" hidden>
                        <div style="display: grid; gap: 0.5rem;">
                            <label class="field-stack">
                                <span class="field-label">Target Overlay Screen</span>
                                <select id="overlay-screen-select" class="s-select">
                                    @foreach ($screens as $scr)
                                        <option value="{{ $scr->id }}">{{ $scr->name }} ({{ $scr->template_type }})</option>
                                    @endforeach
                                </select>
                            </label>
                            <div style="display: flex; gap: 0.375rem;">
                                <a id="overlay-open-link" href="{{ $screens->first()?->getOverlayUrl() ?? '#' }}" target="_blank" class="s-btn" style="flex: 1;">Open URL</a>
                                <button type="button" id="btn-push-overlay-scene" class="s-btn s-btn-gold" style="flex: 1;">Push to Scene</button>
                            </div>
                            <input type="text" id="overlay-guest-name" class="s-input" placeholder="Lower-third name">
                            <input type="text" id="overlay-guest-role" class="s-input" placeholder="Role / subtitle">
                            <div style="display: flex; gap: 0.375rem;">
                                <button type="button" id="btn-overlay-show" class="s-btn s-btn-gold" style="flex: 1;">Show Lower Third</button>
                                <button type="button" id="btn-overlay-hide" class="s-btn" style="flex: 1;">Hide</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dock-panel">
                    <div class="dock-head">
                        <h3 class="dock-title">Recent Command Log</h3>
                    </div>
                    <div id="recent-logs-list" style="display: grid; gap: 0.25rem; font-size: 0.75rem;">
                        @forelse ($recentLogs as $log)
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.25rem 0.375rem; border-radius: 0.375rem; background: var(--bg-raised);">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $log->request_type }}</span>
                                <span style="color: {{ $log->status === 'failed' ? 'var(--danger)' : ($log->status === 'succeeded' ? 'var(--positive)' : 'var(--gold-light)') }};">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </div>
                        @empty
                            <span class="dock-subtitle">No commands dispatched yet.</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </main>

        {{-- Native Declarative Modal: Add Source to Edited Scene --}}
        <dialog id="add-source-dialog" class="obs-dialog" aria-labelledby="add-source-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="add-source-title" class="dialog-title">Add Source to Scene</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="add-source-dialog" command="close">Close</button>
                </div>

                <form id="add-source-form" style="display: grid; gap: 0.75rem;">
                    <div class="field-stack">
                        <label for="add-source-mode" class="field-label">Mode</label>
                        <select id="add-source-mode" class="s-select">
                            <option value="create">Create New OBS Input Source</option>
                            <option value="existing">Reuse Existing OBS Source</option>
                        </select>
                    </div>

                    <div id="add-source-new-fields" style="display: grid; gap: 0.625rem;">
                        <label class="field-stack">
                            <span class="field-label">Source Kind</span>
                            <select id="add-source-kind" class="s-select"></select>
                        </label>
                        <label class="field-stack">
                            <span class="field-label">New Source Name</span>
                            <input type="text" id="add-source-name" class="s-input" placeholder="e.g. Guest Browser Overlay" required>
                        </label>
                        <label class="field-stack" id="add-source-url-wrap">
                            <span class="field-label">URL / File / Text (Optional)</span>
                            <input type="text" id="add-source-url" class="s-input" placeholder="https://ob/screens/... or leave blank">
                        </label>
                    </div>

                    <div id="add-source-existing-fields" style="display: grid; gap: 0.625rem;" hidden>
                        <label class="field-stack">
                            <span class="field-label">Existing OBS Source</span>
                            <select id="add-source-existing" class="s-select"></select>
                        </label>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="s-btn" commandfor="add-source-dialog" command="close">Cancel</button>
                        <button type="submit" class="s-btn s-btn-gold">Add to Scene</button>
                    </div>
                </form>
            </div>
        </dialog>

        {{-- Native Declarative Modal: Rename Scene --}}
        <dialog id="rename-scene-dialog" class="obs-dialog" aria-labelledby="rename-scene-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="rename-scene-title" class="dialog-title">Rename OBS Scene</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="rename-scene-dialog" command="close">Close</button>
                </div>
                <form id="rename-scene-form" style="display: grid; gap: 0.75rem;">
                    <input type="hidden" id="rename-scene-original">
                    <label class="field-stack">
                        <span class="field-label">New Scene Name</span>
                        <input type="text" id="rename-scene-new" class="s-input" required>
                    </label>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="s-btn" commandfor="rename-scene-dialog" command="close">Cancel</button>
                        <button type="submit" class="s-btn s-btn-gold">Rename Scene</button>
                    </div>
                </form>
            </div>
        </dialog>

        {{-- Native Declarative Modal: Create Scene Collection --}}
        <dialog id="create-collection-dialog" class="obs-dialog" aria-labelledby="create-collection-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="create-collection-title" class="dialog-title">Create OBS Scene Collection</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="create-collection-dialog" command="close">Close</button>
                </div>
                <form id="create-collection-form" style="display: grid; gap: 0.75rem;">
                    <label class="field-stack">
                        <span class="field-label">New Scene Collection Name</span>
                        <input type="text" id="create-collection-name" class="s-input" placeholder="e.g. Tournament Finals" required>
                    </label>
                    <p style="margin: 0; font-size: 0.75rem; color: var(--ink-muted);">
                        Creates a new Scene Collection inside OBS Studio and switches to it automatically.
                    </p>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="s-btn" commandfor="create-collection-dialog" command="close">Cancel</button>
                        <button type="submit" class="s-btn s-btn-gold">Create Collection</button>
                    </div>
                </form>
            </div>
        </dialog>

        {{-- Native Declarative Modal: Confirm Scene Delete --}}
        <dialog id="delete-scene-dialog" class="obs-dialog" aria-labelledby="delete-scene-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="delete-scene-title" class="dialog-title">Delete OBS Scene</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="delete-scene-dialog" command="close">Close</button>
                </div>
                <p id="delete-scene-copy" style="margin: 0; color: var(--ink-secondary);"></p>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="s-btn" commandfor="delete-scene-dialog" command="close">Cancel</button>
                    <button type="button" id="confirm-delete-scene-btn" class="s-btn s-btn-danger">Delete Scene</button>
                </div>
            </div>
        </dialog>

        <div id="studio-toast" class="toast-bar" role="status" hidden></div>

        <script src="{{ asset('js/filament/filament/echo.js') }}"></script>
        <script type="module">
            // Conditional Invoker Commands fallback only when browser lacks native support
            if (!('commandForElement' in HTMLButtonElement.prototype)) {
                import('https://esm.run/invokers-polyfill').catch(() => {});
            }
        </script>
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const studioSlug = @json($studio->slug);
                const studioId = @json($studio->id);
                const screensData = @json($screensPayload);

                const endpoints = {
                    command: @json(route('studio.command', ['studio' => $studio->slug])),
                    commandResult: @json(route('studio.command-result', ['studio' => $studio->slug, 'commandUuid' => '__UUID__'])),
                    previewToggle: @json(route('studio.preview-toggle', ['studio' => $studio->slug])),
                    previewFrame: @json(route('studio.preview-frame', ['studio' => $studio->slug])),
                };

                let liveState = @json($liveState ?: new stdClass());
                let studioStatus = @json($studio->status);
                let editingScene = @json($initialEditingScene);
                let selectedSceneItemId = null;
                let previewLeaseTimer = null;
                let vcamStream = null;
                let pendingDeleteScene = null;

                let cachedDisplaySupport;
                const canTransitionDisplay = () => {
                    if (cachedDisplaySupport !== undefined) return cachedDisplaySupport;
                    if (!window.CSS?.supports?.('transition-behavior', 'allow-discrete') || !document.body) return false;
                    const probe = document.createElement('div');
                    probe.style.cssText = 'transition: display 1s allow-discrete !important; display: block;';
                    document.body.appendChild(probe);
                    getComputedStyle(probe).display;
                    probe.style.display = 'none';
                    cachedDisplaySupport = getComputedStyle(probe).display === 'block';
                    probe.remove();
                    return cachedDisplaySupport;
                };

                const supportsTopLayerExit = () => Boolean(window.CSS?.supports?.('overlay', 'auto') && canTransitionDisplay());

                const closeDialog = async (dialogEl) => {
                    if (!dialogEl || !dialogEl.open) return;
                    if (!supportsTopLayerExit()) {
                        dialogEl.setAttribute('data-closing', '');
                        const animations = dialogEl.getAnimations({ subtree: true });
                        if (animations.length > 0) {
                            await Promise.race([
                                Promise.allSettled(animations.map((a) => a.finished)),
                                new Promise((resolve) => setTimeout(resolve, 600)),
                            ]);
                        }
                        if (!dialogEl.hasAttribute('data-closing')) return;
                        dialogEl.removeAttribute('data-closing');
                    }
                    dialogEl.close();
                };

                const openDialog = (dialogEl) => {
                    if (!dialogEl) return;
                    dialogEl.removeAttribute('data-closing');
                    if (typeof dialogEl.showModal === 'function') {
                        dialogEl.showModal();
                    } else {
                        dialogEl.setAttribute('open', '');
                    }
                };

                if (!('commandForElement' in HTMLButtonElement.prototype)) {
                    document.querySelectorAll('[commandfor][command="show-modal"]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const dlg = document.getElementById(btn.getAttribute('commandfor') || '');
                            openDialog(dlg);
                        });
                    });
                }

                document.querySelectorAll('dialog.obs-dialog').forEach((dlg) => {
                    dlg.addEventListener('cancel', (event) => {
                        event.preventDefault();
                        closeDialog(dlg);
                    });
                });

                document.querySelectorAll('[commandfor][command="close"]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const target = document.getElementById(btn.getAttribute('commandfor') || '');
                        closeDialog(target);
                    });
                });

                const toastEl = document.getElementById('studio-toast');
                let toastTimer = null;
                const showToast = (msg) => {
                    if (!toastEl) return;
                    toastEl.textContent = msg;
                    toastEl.hidden = false;
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => { toastEl.hidden = true; }, 2400);
                };

                const postJson = async (url, body = {}) => {
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
                        throw new Error(`HTTP ${res.status}`);
                    }
                    return res.json();
                };

                const sendObsCommand = async (requestType, requestData = {}) => {
                    const res = await postJson(endpoints.command, {
                        request_type: requestType,
                        request_data: requestData,
                    });
                    showToast(`Dispatched ${requestType}`);
                    return res;
                };

                const getVideoDims = () => ({
                    width: Math.max(320, Number(liveState?.videoDimensions?.width || 1920)),
                    height: Math.max(180, Number(liveState?.videoDimensions?.height || 1080)),
                });

                const getItemsForEditingScene = () => {
                    const byScene = liveState?.sceneItemsByScene || {};
                    if (editingScene && Array.isArray(byScene[editingScene])) {
                        return byScene[editingScene];
                    }
                    if (editingScene && editingScene === liveState?.currentProgramScene && Array.isArray(liveState?.sceneItems)) {
                        return liveState.sceneItems;
                    }
                    return [];
                };

                const renderAll = () => {
                    renderHeaderAndOutputs();
                    renderScenes();
                    renderStageLayers();
                    renderLayersInspector();
                    renderAudioMixer();
                    populateAddSourceOptions();
                };

                const renderHeaderAndOutputs = () => {
                    const liveBadge = document.getElementById('studio-live-badge');
                    if (liveBadge) {
                        liveBadge.textContent = studioStatus.charAt(0).toUpperCase() + studioStatus.slice(1);
                        liveBadge.classList.toggle('is-live', studioStatus !== 'offline');
                    }

                    const stats = liveState?.stats || {};
                    const statsChip = document.getElementById('studio-stats-chip');
                    if (statsChip) {
                        statsChip.textContent = stats.activeFps !== undefined
                            ? `${stats.activeFps} FPS · ${stats.cpuUsage ?? 0}% CPU`
                            : 'Telemetry idle';
                    }

                    const outputs = liveState?.outputs || {};
                    const streamBtn = document.getElementById('btn-stream');
                    if (streamBtn) {
                        streamBtn.textContent = outputs.streaming ? 'Stop Stream' : 'Start Stream';
                        streamBtn.classList.toggle('s-btn-danger', Boolean(outputs.streaming));
                        streamBtn.classList.toggle('s-btn-gold', !outputs.streaming);
                    }

                    const recordBtn = document.getElementById('btn-record');
                    if (recordBtn) {
                        recordBtn.textContent = outputs.recording ? 'Stop Record' : 'Start Record';
                        recordBtn.classList.toggle('is-active', Boolean(outputs.recording));
                    }

                    const pauseBtn = document.getElementById('btn-pause-rec');
                    if (pauseBtn) {
                        pauseBtn.textContent = outputs.recordingPaused ? 'Resume Rec' : 'Pause Rec';
                    }

                    const vcamBtn = document.getElementById('btn-vcam');
                    if (vcamBtn) {
                        vcamBtn.classList.toggle('is-active', Boolean(outputs.virtualCam));
                    }

                    const studioModeBtn = document.getElementById('btn-studio-mode');
                    const transitionBtn = document.getElementById('btn-transition');
                    const modeLabel = document.getElementById('studio-mode-label');
                    const studioModeOn = Boolean(liveState?.studioModeEnabled);
                    studioModeBtn?.classList.toggle('is-active', studioModeOn);
                    if (transitionBtn) transitionBtn.hidden = !studioModeOn;
                    if (modeLabel) modeLabel.textContent = studioModeOn ? 'Studio Mode ON' : 'Direct Cut';

                    const dims = getVideoDims();
                    const canvasMeta = document.getElementById('stage-canvas-meta');
                    if (canvasMeta) {
                        canvasMeta.textContent = `Base Canvas: ${dims.width} × ${dims.height} · Drag boxes to reposition, corner to resize`;
                    }
                };

                const renderScenes = () => {
                    const colSelect = document.getElementById('scene-collection-select');
                    if (colSelect) {
                        const collections = Array.isArray(liveState?.sceneCollections) ? liveState.sceneCollections : [];
                        const currentCol = liveState?.currentSceneCollection || '';
                        colSelect.innerHTML = '';

                        if (collections.length === 0) {
                            const opt = document.createElement('option');
                            opt.value = '';
                            opt.textContent = currentCol || 'No synced collections';
                            colSelect.appendChild(opt);
                        } else {
                            collections.forEach((colName) => {
                                const opt = document.createElement('option');
                                opt.value = colName;
                                opt.textContent = colName;
                                opt.selected = colName === currentCol;
                                colSelect.appendChild(opt);
                            });
                        }
                    }

                    const container = document.getElementById('scenes-list');
                    if (!container) return;

                    const scenes = Array.isArray(liveState?.scenes) ? liveState.scenes : [];
                    if ((!editingScene || !scenes.some((s) => s.sceneName === editingScene)) && scenes.length > 0) {
                        editingScene = liveState?.currentProgramScene || scenes[0].sceneName;
                    }

                    const editLabel = document.getElementById('stage-editing-label');
                    if (editLabel) {
                        editLabel.textContent = `Scene: ${editingScene || 'None'}`;
                    }

                    if (scenes.length === 0) {
                        container.innerHTML = '<div class="dock-subtitle">Connect obs-agent to populate live scenes.</div>';
                        return;
                    }

                    container.innerHTML = '';
                    scenes.forEach((scene) => {
                        const sName = scene.sceneName || '';
                        const isProgram = sName === liveState?.currentProgramScene;
                        const row = document.createElement('div');
                        row.className = `scene-row${sName === editingScene ? ' is-editing' : ''}${isProgram ? ' is-program' : ''}`;

                        const selectBtn = document.createElement('button');
                        selectBtn.type = 'button';
                        selectBtn.className = 'scene-row-btn';
                        selectBtn.textContent = sName;
                        if (isProgram) {
                            const pgm = document.createElement('span');
                            pgm.className = 'scene-tag-pgm';
                            pgm.textContent = 'PGM';
                            selectBtn.prepend(pgm);
                        }
                        selectBtn.addEventListener('click', () => {
                            editingScene = sName;
                            selectedSceneItemId = null;
                            renderAll();
                            if (document.getElementById('live-view-mode')?.value === 'snapshot') {
                                enableSnapshotPreview(true);
                            }
                        });

                        const actions = document.createElement('div');
                        actions.className = 'scene-actions';

                        const cutBtn = document.createElement('button');
                        cutBtn.type = 'button';
                        cutBtn.className = 's-btn s-btn-xs';
                        cutBtn.textContent = liveState?.studioModeEnabled ? 'Preview' : 'Live';
                        cutBtn.addEventListener('click', () => {
                            const cmd = liveState?.studioModeEnabled ? 'SetCurrentPreviewScene' : 'SetCurrentProgramScene';
                            sendObsCommand(cmd, { sceneName: sName });
                        });

                        const renameBtn = document.createElement('button');
                        renameBtn.type = 'button';
                        renameBtn.className = 's-btn s-btn-xs';
                        renameBtn.textContent = 'Edit';
                        renameBtn.addEventListener('click', () => {
                            document.getElementById('rename-scene-original').value = sName;
                            document.getElementById('rename-scene-new').value = sName;
                            openDialog(document.getElementById('rename-scene-dialog'));
                        });

                        const delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 's-btn s-btn-xs s-btn-danger';
                        delBtn.textContent = '×';
                        delBtn.title = `Delete ${sName}`;
                        delBtn.addEventListener('click', () => {
                            pendingDeleteScene = sName;
                            document.getElementById('delete-scene-copy').textContent = `Delete OBS scene "${sName}"? This removes the scene inside OBS Studio.`;
                            openDialog(document.getElementById('delete-scene-dialog'));
                        });

                        actions.append(cutBtn, renameBtn, delBtn);
                        row.append(selectBtn, actions);
                        container.appendChild(row);
                    });
                };

                const computeItemRect = (item) => {
                    const dims = getVideoDims();
                    const tf = item.sceneItemTransform || {};
                    const scaleX = Number(tf.scaleX ?? 1);
                    const scaleY = Number(tf.scaleY ?? 1);
                    const hasBounds = tf.boundsType && tf.boundsType !== 'OBS_BOUNDS_NONE';
                    const fallbackW = hasBounds ? Number(tf.boundsWidth || 480) : Number((tf.sourceWidth || 480) * scaleX);
                    const fallbackH = hasBounds ? Number(tf.boundsHeight || 270) : Number((tf.sourceHeight || 270) * scaleY);

                    const rawW = Number(tf.width && tf.width > 0 ? tf.width : fallbackW);
                    const rawH = Number(tf.height && tf.height > 0 ? tf.height : fallbackH);
                    const w = Math.max(40, isFinite(rawW) && rawW > 0 ? rawW : 480);
                    const h = Math.max(24, isFinite(rawH) && rawH > 0 ? rawH : 270);

                    const posX = Number(tf.positionX ?? 0);
                    const posY = Number(tf.positionY ?? 0);
                    const align = Number(tf.alignment ?? 5);
                    const hAlign = align & 3;
                    const vAlign = align & 12;

                    let left = posX;
                    if (hAlign === 0) left = posX - (w / 2);
                    else if (hAlign === 2) left = posX - w;

                    let top = posY;
                    if (vAlign === 0) top = posY - (h / 2);
                    else if (vAlign === 8) top = posY - h;

                    return {
                        x: left,
                        y: top,
                        posX,
                        posY,
                        w,
                        h,
                        baseW: dims.width,
                        baseH: dims.height,
                    };
                };

                const renderStageLayers = () => {
                    const surface = document.getElementById('stage-layers');
                    const canvas = document.getElementById('stage-canvas');
                    if (!surface || !canvas) return;

                    surface.innerHTML = '';
                    const items = getItemsForEditingScene();

                    items.forEach((item, idx) => {
                        const rect = computeItemRect(item);
                        const box = document.createElement('div');
                        const itemId = Number(item.sceneItemId);

                        box.className = 'stage-item-box'
                            + (itemId === selectedSceneItemId ? ' is-selected' : '')
                            + (!item.sceneItemEnabled ? ' is-hidden' : '')
                            + (item.sceneItemLocked ? ' is-locked' : '');

                        box.style.left = `${(rect.x / rect.baseW) * 100}%`;
                        box.style.top = `${(rect.y / rect.baseH) * 100}%`;
                        box.style.width = `${Math.min(100, (rect.w / rect.baseW) * 100)}%`;
                        box.style.height = `${Math.min(100, (rect.h / rect.baseH) * 100)}%`;
                        box.style.zIndex = String(idx + 1);

                        const label = document.createElement('div');
                        label.className = 'stage-item-label';
                        label.textContent = item.sourceName || `Item #${itemId}`;

                        const handle = document.createElement('div');
                        handle.className = 'stage-resize-handle';

                        box.append(label, handle);

                        box.addEventListener('pointerdown', (ev) => {
                            selectedSceneItemId = itemId;
                            renderLayersInspector();
                            document.querySelectorAll('.stage-item-box').forEach((b) => b.classList.remove('is-selected'));
                            box.classList.add('is-selected');

                            if (item.sceneItemLocked) return;

                            const isResize = ev.target === handle;
                            const canvasRect = canvas.getBoundingClientRect();
                            const startClientX = ev.clientX;
                            const startClientY = ev.clientY;
                            const startTf = { ...rect };
                            const origTf = item.sceneItemTransform || {};
                            const hasBounds = origTf.boundsType && origTf.boundsType !== 'OBS_BOUNDS_NONE';
                            const startScaleX = Number(origTf.scaleX ?? 1);
                            const startScaleY = Number(origTf.scaleY ?? 1);

                            box.setPointerCapture(ev.pointerId);

                            const onMove = (moveEv) => {
                                const dxPx = moveEv.clientX - startClientX;
                                const dyPx = moveEv.clientY - startClientY;
                                const dxObs = (dxPx / canvasRect.width) * rect.baseW;
                                const dyObs = (dyPx / canvasRect.height) * rect.baseH;

                                if (isResize) {
                                    const nextW = Math.max(80, Math.round(startTf.w + dxObs));
                                    const nextH = Math.max(45, Math.round(startTf.h + dyObs));
                                    box.style.width = `${(nextW / rect.baseW) * 100}%`;
                                    box.style.height = `${(nextH / rect.baseH) * 100}%`;
                                } else {
                                    const nextLeft = Math.round(startTf.x + dxObs);
                                    const nextTop = Math.round(startTf.y + dyObs);
                                    const nextPosX = Math.round(startTf.posX + dxObs);
                                    const nextPosY = Math.round(startTf.posY + dyObs);
                                    box.style.left = `${(nextLeft / rect.baseW) * 100}%`;
                                    box.style.top = `${(nextTop / rect.baseH) * 100}%`;
                                    document.getElementById('tf-x').value = String(nextPosX);
                                    document.getElementById('tf-y').value = String(nextPosY);
                                }
                            };

                            const onUp = (upEv) => {
                                box.removeEventListener('pointermove', onMove);
                                box.removeEventListener('pointerup', onUp);

                                const dxObs = ((upEv.clientX - startClientX) / canvasRect.width) * rect.baseW;
                                const dyObs = ((upEv.clientY - startClientY) / canvasRect.height) * rect.baseH;
                                if (Math.abs(dxObs) < 2 && Math.abs(dyObs) < 2) return;

                                const patch = {};
                                if (isResize) {
                                    const nextW = Math.max(80, startTf.w + dxObs);
                                    const nextH = Math.max(45, startTf.h + dyObs);
                                    if (hasBounds) {
                                        patch.boundsWidth = Math.round(nextW);
                                        patch.boundsHeight = Math.round(nextH);
                                    } else {
                                        const ratioX = nextW / startTf.w;
                                        const ratioY = nextH / startTf.h;
                                        patch.scaleX = Number((startScaleX * ratioX).toFixed(3));
                                        patch.scaleY = Number((startScaleY * ratioY).toFixed(3));
                                    }
                                } else {
                                    patch.positionX = Math.round(startTf.posX + dxObs);
                                    patch.positionY = Math.round(startTf.posY + dyObs);
                                }

                                sendObsCommand('SetSceneItemTransform', {
                                    sceneName: editingScene,
                                    sceneItemId: itemId,
                                    sceneItemTransform: patch,
                                });
                            };

                            box.addEventListener('pointermove', onMove);
                            box.addEventListener('pointerup', onUp);
                        });

                        surface.appendChild(box);
                    });
                };

                const renderLayersInspector = () => {
                    const listEl = document.getElementById('layers-list');
                    const countEl = document.getElementById('layer-count-meta');
                    const selCard = document.getElementById('selected-source-card');
                    if (!listEl) return;

                    const items = [...getItemsForEditingScene()];
                    if (countEl) countEl.textContent = `${items.length} source${items.length === 1 ? '' : 's'}`;

                    if (items.length === 0) {
                        listEl.innerHTML = '<div class="dock-subtitle">No sources in this scene yet.</div>';
                        if (selCard) selCard.hidden = true;
                        return;
                    }

                    // Visual layer list: top overlay layer at top (reverse index order)
                    const ordered = [...items].reverse();
                    listEl.innerHTML = '';

                    ordered.forEach((item) => {
                        const itemId = Number(item.sceneItemId);
                        const idx = Number(item.sceneItemIndex ?? 0);
                        const row = document.createElement('div');
                        row.className = `layer-row${itemId === selectedSceneItemId ? ' is-selected' : ''}`;

                        const selectBtn = document.createElement('button');
                        selectBtn.type = 'button';
                        selectBtn.className = 'scene-row-btn';
                        selectBtn.textContent = item.sourceName || `Source #${itemId}`;
                        selectBtn.addEventListener('click', () => {
                            selectedSceneItemId = itemId;
                            renderStageLayers();
                            renderLayersInspector();
                        });

                        const btns = document.createElement('div');
                        btns.className = 'scene-actions';

                        const upBtn = document.createElement('button');
                        upBtn.type = 'button';
                        upBtn.className = 's-btn s-btn-xs';
                        upBtn.textContent = '↑';
                        upBtn.title = 'Bring forward';
                        upBtn.addEventListener('click', () => {
                            sendObsCommand('SetSceneItemIndex', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                                sceneItemIndex: Math.min(items.length - 1, idx + 1),
                            });
                        });

                        const downBtn = document.createElement('button');
                        downBtn.type = 'button';
                        downBtn.className = 's-btn s-btn-xs';
                        downBtn.textContent = '↓';
                        downBtn.title = 'Send backward';
                        downBtn.addEventListener('click', () => {
                            sendObsCommand('SetSceneItemIndex', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                                sceneItemIndex: Math.max(0, idx - 1),
                            });
                        });

                        const visBtn = document.createElement('button');
                        visBtn.type = 'button';
                        visBtn.className = `s-btn s-btn-xs${item.sceneItemEnabled ? ' is-active' : ''}`;
                        visBtn.textContent = item.sceneItemEnabled ? 'On' : 'Off';
                        visBtn.addEventListener('click', () => {
                            sendObsCommand('SetSceneItemEnabled', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                                sceneItemEnabled: !item.sceneItemEnabled,
                            });
                        });

                        const lockBtn = document.createElement('button');
                        lockBtn.type = 'button';
                        lockBtn.className = `s-btn s-btn-xs${item.sceneItemLocked ? ' is-active' : ''}`;
                        lockBtn.textContent = item.sceneItemLocked ? 'Locked' : 'Free';
                        lockBtn.addEventListener('click', () => {
                            sendObsCommand('SetSceneItemLocked', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                                sceneItemLocked: !item.sceneItemLocked,
                            });
                        });

                        const rmBtn = document.createElement('button');
                        rmBtn.type = 'button';
                        rmBtn.className = 's-btn s-btn-xs s-btn-danger';
                        rmBtn.textContent = '×';
                        rmBtn.title = 'Remove from scene';
                        rmBtn.addEventListener('click', () => {
                            if (selectedSceneItemId === itemId) selectedSceneItemId = null;
                            sendObsCommand('RemoveSceneItem', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                            });
                        });

                        btns.append(upBtn, downBtn, visBtn, lockBtn, rmBtn);
                        row.append(selectBtn, btns);
                        listEl.appendChild(row);
                    });

                    const selectedItem = items.find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!selectedItem || !selCard) {
                        if (selCard) selCard.hidden = true;
                        return;
                    }

                    selCard.hidden = false;
                    document.getElementById('sel-source-name').textContent = selectedItem.sourceName || '';
                    document.getElementById('sel-source-kind').textContent = selectedItem.inputKind || 'Scene source';

                    const tf = selectedItem.sceneItemTransform || {};
                    document.getElementById('tf-x').value = String(Math.round(Number(tf.positionX ?? 0)));
                    document.getElementById('tf-y').value = String(Math.round(Number(tf.positionY ?? 0)));
                    document.getElementById('tf-sx').value = String(Number(Number(tf.scaleX ?? 1).toFixed(2)));
                    document.getElementById('tf-sy').value = String(Number(Number(tf.scaleY ?? 1).toFixed(2)));
                    document.getElementById('tf-rot').value = String(Math.round(Number(tf.rotation ?? 0)));
                };

                const renderAudioMixer = () => {
                    const container = document.getElementById('audio-mixer-list');
                    if (!container) return;

                    const inputs = Array.isArray(liveState?.audioInputs) ? liveState.audioInputs : [];
                    if (inputs.length === 0) {
                        container.innerHTML = '<div class="dock-subtitle">No OBS audio inputs detected yet.</div>';
                        return;
                    }

                    container.innerHTML = '';
                    inputs.forEach((inp) => {
                        const name = inp.inputName || '';
                        const muted = Boolean(inp.inputMuted);
                        const db = Math.round(Number(inp.inputVolumeDb ?? 0) * 10) / 10;

                        const card = document.createElement('div');
                        card.className = 'audio-item';

                        const top = document.createElement('div');
                        top.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:0.5rem;';

                        const title = document.createElement('span');
                        title.style.fontWeight = '500';
                        title.textContent = name;

                        const right = document.createElement('div');
                        right.style.cssText = 'display:inline-flex;align-items:center;gap:0.375rem;';

                        const dbTag = document.createElement('span');
                        dbTag.className = 'dock-subtitle';
                        dbTag.textContent = `${db} dB`;

                        const muteBtn = document.createElement('button');
                        muteBtn.type = 'button';
                        muteBtn.className = `s-btn s-btn-xs${muted ? ' s-btn-danger' : ''}`;
                        muteBtn.textContent = muted ? 'Muted' : 'Live';
                        muteBtn.addEventListener('click', () => {
                            sendObsCommand('SetInputMute', { inputName: name, inputMuted: !muted });
                        });

                        right.append(dbTag, muteBtn);
                        top.append(title, right);

                        const slider = document.createElement('input');
                        slider.type = 'range';
                        slider.min = '-60';
                        slider.max = '0';
                        slider.step = '0.5';
                        slider.value = String(db);
                        slider.addEventListener('change', (ev) => {
                            sendObsCommand('SetInputVolume', {
                                inputName: name,
                                inputVolumeDb: parseFloat(ev.target.value),
                            });
                        });

                        card.append(top, slider);
                        container.appendChild(card);
                    });
                };

                const populateAddSourceOptions = () => {
                    const kindSelect = document.getElementById('add-source-kind');
                    const existingSelect = document.getElementById('add-source-existing');
                    if (kindSelect && kindSelect.options.length === 0) {
                        const rawKinds = Array.isArray(liveState?.inputKinds) && liveState.inputKinds.length > 0
                            ? liveState.inputKinds
                            : ['browser_source', 'image_source', 'ffmpeg_source', 'color_source_v3', 'text_ft2_source_v2'];

                        rawKinds.forEach((kind) => {
                            const opt = document.createElement('option');
                            opt.value = kind;
                            opt.textContent = kind;
                            kindSelect.appendChild(opt);
                        });
                    }

                    if (existingSelect) {
                        const prev = existingSelect.value;
                        existingSelect.innerHTML = '';
                        (Array.isArray(liveState?.allInputs) ? liveState.allInputs : []).forEach((inp) => {
                            const opt = document.createElement('option');
                            opt.value = inp.inputName;
                            opt.textContent = `${inp.inputName} (${inp.inputKind})`;
                            existingSelect.appendChild(opt);
                        });
                        if (prev) existingSelect.value = prev;
                    }
                };

                // Topbar Studio Switcher & Basic Controls
                document.getElementById('studio-switcher')?.addEventListener('change', (ev) => {
                    if (ev.target.value) window.location.href = ev.target.value;
                });

                document.getElementById('sync-obs-btn')?.addEventListener('click', () => {
                    sendObsCommand('obsisstant.RefreshAllState');
                });

                document.getElementById('scene-collection-select')?.addEventListener('change', (ev) => {
                    const collectionName = ev.target.value;
                    if (!collectionName) return;
                    editingScene = '';
                    selectedSceneItemId = null;
                    sendObsCommand('SetCurrentSceneCollection', { sceneCollectionName: collectionName });
                });

                document.getElementById('create-collection-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const input = document.getElementById('create-collection-name');
                    const name = input?.value?.trim();
                    if (!name) return;
                    editingScene = '';
                    selectedSceneItemId = null;
                    await sendObsCommand('CreateSceneCollection', { sceneCollectionName: name });
                    input.value = '';
                    closeDialog(document.getElementById('create-collection-dialog'));
                });

                document.getElementById('create-scene-btn')?.addEventListener('click', async () => {
                    const input = document.getElementById('new-scene-name');
                    const sceneName = input?.value?.trim();
                    if (!sceneName) return;
                    await sendObsCommand('CreateScene', { sceneName });
                    editingScene = sceneName;
                    input.value = '';
                });

                document.getElementById('cut-editing-scene-btn')?.addEventListener('click', () => {
                    if (!editingScene) return;
                    const cmd = liveState?.studioModeEnabled ? 'SetCurrentPreviewScene' : 'SetCurrentProgramScene';
                    sendObsCommand(cmd, { sceneName: editingScene });
                });

                document.getElementById('btn-stream')?.addEventListener('click', () => {
                    const isStreaming = Boolean(liveState?.outputs?.streaming);
                    sendObsCommand(isStreaming ? 'StopStream' : 'StartStream');
                });

                document.getElementById('btn-record')?.addEventListener('click', () => {
                    const isRec = Boolean(liveState?.outputs?.recording);
                    sendObsCommand(isRec ? 'StopRecord' : 'StartRecord');
                });

                document.getElementById('btn-pause-rec')?.addEventListener('click', () => sendObsCommand('ToggleRecordPause'));
                document.getElementById('btn-replay')?.addEventListener('click', () => sendObsCommand('SaveReplayBuffer'));
                document.getElementById('btn-vcam')?.addEventListener('click', () => sendObsCommand('ToggleVirtualCam'));
                document.getElementById('btn-studio-mode')?.addEventListener('click', () => {
                    sendObsCommand('SetStudioModeEnabled', { studioModeEnabled: !liveState?.studioModeEnabled });
                });
                document.getElementById('btn-transition')?.addEventListener('click', () => sendObsCommand('TriggerStudioModeTransition'));

                document.querySelectorAll('[data-run-macro]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const macroId = btn.getAttribute('data-run-macro');
                        await postJson(`/studio/${studioSlug}/macro/${macroId}`);
                        showToast(`Macro triggered: ${btn.textContent.trim()}`);
                    });
                });

                // Inspector Tabs
                document.querySelectorAll('[data-tab-btn]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const target = btn.getAttribute('data-tab-btn');
                        document.querySelectorAll('[data-tab-btn]').forEach((b) => b.setAttribute('aria-selected', b === btn ? 'true' : 'false'));
                        ['sources', 'audio', 'overlays'].forEach((id) => {
                            const el = document.getElementById(`tab-${id}`);
                            if (el) el.hidden = id !== target;
                        });
                    });
                });

                // Selected Transform & Settings Inspector Controls
                document.getElementById('btn-apply-transform')?.addEventListener('click', () => {
                    if (!editingScene || selectedSceneItemId === null) return;
                    sendObsCommand('SetSceneItemTransform', {
                        sceneName: editingScene,
                        sceneItemId: selectedSceneItemId,
                        sceneItemTransform: {
                            positionX: Number(document.getElementById('tf-x').value || 0),
                            positionY: Number(document.getElementById('tf-y').value || 0),
                            scaleX: Number(document.getElementById('tf-sx').value || 1),
                            scaleY: Number(document.getElementById('tf-sy').value || 1),
                            rotation: Number(document.getElementById('tf-rot').value || 0),
                        },
                    });
                });

                document.getElementById('btn-fit-source')?.addEventListener('click', () => {
                    if (!editingScene || selectedSceneItemId === null) return;
                    const dims = getVideoDims();
                    sendObsCommand('SetSceneItemTransform', {
                        sceneName: editingScene,
                        sceneItemId: selectedSceneItemId,
                        sceneItemTransform: {
                            positionX: 0,
                            positionY: 0,
                            boundsType: 'OBS_BOUNDS_SCALE_INNER',
                            boundsWidth: dims.width,
                            boundsHeight: dims.height,
                        },
                    });
                });

                document.getElementById('btn-center-source')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item || !editingScene) return;
                    const rect = computeItemRect(item);
                    sendObsCommand('SetSceneItemTransform', {
                        sceneName: editingScene,
                        sceneItemId: selectedSceneItemId,
                        sceneItemTransform: {
                            positionX: Math.round((rect.baseW - rect.w) / 2),
                            positionY: Math.round((rect.baseH - rect.h) / 2),
                        },
                    });
                });

                document.getElementById('btn-inspect-settings')?.addEventListener('click', async () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item?.sourceName) return;

                    const { command_uuid: uuid } = await sendObsCommand('obsisstant.FetchInputSettings', {
                        inputName: item.sourceName,
                    });

                    const box = document.getElementById('source-settings-json');
                    if (box) box.value = 'Loading live OBS settings...';

                    for (let attempt = 0; attempt < 10; attempt += 1) {
                        await new Promise((r) => setTimeout(r, 350));
                        const res = await fetch(endpoints.commandResult.replace('__UUID__', uuid), {
                            headers: { Accept: 'application/json' },
                        });
                        if (!res.ok) continue;
                        const data = await res.json();
                        if (data.status === 'succeeded' || data.status === 'failed') {
                            if (box) {
                                box.value = data.status === 'succeeded'
                                    ? JSON.stringify(data.response_data?.inputSettings || {}, null, 2)
                                    : `Error: ${data.error_message}`;
                            }
                            break;
                        }
                    }
                });

                document.getElementById('btn-save-settings')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    const box = document.getElementById('source-settings-json');
                    if (!item?.sourceName || !box?.value) return;

                    try {
                        const parsed = JSON.parse(box.value);
                        sendObsCommand('SetInputSettings', {
                            inputName: item.sourceName,
                            inputSettings: parsed,
                            overlay: true,
                        });
                    } catch {
                        showToast('Invalid JSON in source settings');
                    }
                });

                // Add Source / Rename / Delete Dialogs
                document.getElementById('add-source-mode')?.addEventListener('change', (ev) => {
                    const isNew = ev.target.value === 'create';
                    document.getElementById('add-source-new-fields').hidden = !isNew;
                    document.getElementById('add-source-existing-fields').hidden = isNew;
                    document.getElementById('add-source-name').required = isNew;
                });

                document.getElementById('add-source-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    if (!editingScene) {
                        showToast('Select or create a scene first');
                        return;
                    }

                    const mode = document.getElementById('add-source-mode').value;
                    if (mode === 'create') {
                        const kind = document.getElementById('add-source-kind').value;
                        const name = document.getElementById('add-source-name').value.trim();
                        const urlOrFile = document.getElementById('add-source-url').value.trim();
                        const settings = {};
                        if (urlOrFile) {
                            if (kind.includes('browser')) {
                                settings.url = urlOrFile;
                                settings.width = getVideoDims().width;
                                settings.height = getVideoDims().height;
                            } else if (kind.includes('image')) {
                                settings.file = urlOrFile;
                            } else if (kind.includes('ffmpeg')) {
                                settings.local_file = urlOrFile;
                            } else if (kind.includes('text')) {
                                settings.text = urlOrFile;
                            }
                        }
                        await sendObsCommand('CreateInput', {
                            sceneName: editingScene,
                            inputName: name,
                            inputKind: kind,
                            inputSettings: settings,
                            sceneItemEnabled: true,
                        });
                        document.getElementById('add-source-name').value = '';
                        document.getElementById('add-source-url').value = '';
                    } else {
                        const existingName = document.getElementById('add-source-existing').value;
                        if (existingName) {
                            await sendObsCommand('CreateSceneItem', {
                                sceneName: editingScene,
                                sourceName: existingName,
                                sceneItemEnabled: true,
                            });
                        }
                    }

                    closeDialog(document.getElementById('add-source-dialog'));
                });

                document.getElementById('rename-scene-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const oldName = document.getElementById('rename-scene-original').value;
                    const newName = document.getElementById('rename-scene-new').value.trim();
                    if (!oldName || !newName) return;
                    await sendObsCommand('SetSceneName', { sceneName: oldName, newSceneName: newName });
                    if (editingScene === oldName) editingScene = newName;
                    closeDialog(document.getElementById('rename-scene-dialog'));
                });

                document.getElementById('confirm-delete-scene-btn')?.addEventListener('click', async () => {
                    if (!pendingDeleteScene) return;
                    await sendObsCommand('RemoveScene', { sceneName: pendingDeleteScene });
                    if (editingScene === pendingDeleteScene) editingScene = '';
                    pendingDeleteScene = null;
                    closeDialog(document.getElementById('delete-scene-dialog'));
                });

                // Overlays Panel Controls
                const syncOverlayFields = () => {
                    const selId = Number(document.getElementById('overlay-screen-select')?.value);
                    const scr = screensData.find((s) => s.id === selId);
                    if (!scr) return;
                    document.getElementById('overlay-open-link').href = scr.overlay_url;
                    document.getElementById('overlay-guest-name').value = scr.state?.guest_name || '';
                    document.getElementById('overlay-guest-role').value = scr.state?.guest_role || '';
                };
                document.getElementById('overlay-screen-select')?.addEventListener('change', syncOverlayFields);
                syncOverlayFields();

                const pushOverlayState = async (showLowerThird) => {
                    const selId = Number(document.getElementById('overlay-screen-select')?.value);
                    if (!selId) return;
                    await postJson(`/studio/${studioSlug}/screens/${selId}/live`, {
                        state: {
                            guest_name: document.getElementById('overlay-guest-name').value,
                            guest_role: document.getElementById('overlay-guest-role').value,
                            show_lower_third: showLowerThird,
                        },
                    });
                    showToast('Overlay pushed live via Reverb');
                };

                document.getElementById('btn-overlay-show')?.addEventListener('click', () => pushOverlayState(true));
                document.getElementById('btn-overlay-hide')?.addEventListener('click', () => pushOverlayState(false));
                document.getElementById('btn-push-overlay-scene')?.addEventListener('click', async () => {
                    const selId = Number(document.getElementById('overlay-screen-select')?.value);
                    if (!selId) return;
                    await postJson(`/studio/${studioSlug}/screens/${selId}/push`, {
                        scene_name: editingScene || null,
                    });
                    showToast('Pushing screen to OBS scene');
                });

                // Live Preview Underlay Modes (Layout / Snapshot Feed / WebRTC Virtual Cam)
                const previewImg = document.getElementById('live-preview-img');
                const previewVideo = document.getElementById('webrtc-preview-video');
                const previewStatusLabel = document.getElementById('stage-preview-status');

                const stopVirtualCam = () => {
                    if (vcamStream) {
                        vcamStream.getTracks().forEach((t) => t.stop());
                        vcamStream = null;
                    }
                    if (previewVideo) {
                        previewVideo.srcObject = null;
                        previewVideo.hidden = true;
                    }
                };

                const enableSnapshotPreview = async (active) => {
                    clearInterval(previewLeaseTimer);
                    if (!active) {
                        if (previewImg) previewImg.hidden = true;
                        await postJson(endpoints.previewToggle, { active: false }).catch(() => {});
                        return;
                    }

                    const renew = () => postJson(endpoints.previewToggle, {
                        active: true,
                        scene_name: editingScene || null,
                        interval_ms: 550,
                        width: 960,
                    }).catch(() => {});

                    await renew();
                    if (previewImg) {
                        previewImg.onerror = () => { previewImg.hidden = true; };
                        previewImg.onload = () => { previewImg.hidden = false; };
                        previewImg.src = `${endpoints.previewFrame}?t=${Date.now()}`;
                    }
                    setTimeout(() => {
                        if (document.getElementById('live-view-mode')?.value === 'snapshot' && previewImg && previewImg.hidden) {
                            previewImg.src = `${endpoints.previewFrame}?t=${Date.now()}`;
                        }
                    }, 750);
                    previewLeaseTimer = setInterval(() => {
                        renew();
                        if (previewImg && !window.Echo) {
                            previewImg.src = `${endpoints.previewFrame}?t=${Date.now()}`;
                        }
                    }, 18000);
                };

                document.getElementById('live-view-mode')?.addEventListener('change', async (ev) => {
                    const mode = ev.target.value;
                    stopVirtualCam();
                    await enableSnapshotPreview(false);

                    if (mode === 'snapshot') {
                        if (previewStatusLabel) previewStatusLabel.textContent = 'OBS snapshot feed active (~1.5 fps)';
                        await enableSnapshotPreview(true);
                    } else if (mode === 'vcam') {
                        if (!navigator.mediaDevices?.getUserMedia) {
                            showToast('WebRTC getUserMedia not supported in this browser');
                            ev.target.value = 'layout';
                            return;
                        }
                        try {
                            vcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            if (previewVideo) {
                                previewVideo.srcObject = vcamStream;
                                previewVideo.hidden = false;
                            }
                            if (previewStatusLabel) previewStatusLabel.textContent = 'Local camera / Virtual Cam active';
                        } catch {
                            showToast('Camera / Virtual Cam permission denied');
                            ev.target.value = 'layout';
                            if (previewStatusLabel) previewStatusLabel.textContent = 'Layout mode';
                        }
                    } else if (previewStatusLabel) {
                        previewStatusLabel.textContent = 'Layout mode';
                    }
                });

                window.addEventListener('beforeunload', () => {
                    stopVirtualCam();
                    clearInterval(previewLeaseTimer);
                });

                // Real-time Reverb Echo Sync
                const echoCfg = @json($echoConfig);
                if (window.EchoFactory && echoCfg?.key) {
                    window.Echo = new window.EchoFactory(echoCfg);
                    window.Echo.private(`obs.studio.${studioId}`)
                        .listen('.obs.studio.state', (payload) => {
                            if (payload.status) studioStatus = payload.status;
                            if (payload.live_state) liveState = payload.live_state;
                            renderAll();
                        })
                        .listen('.obs.preview.frame', (payload) => {
                            if (document.getElementById('live-view-mode')?.value === 'snapshot' && previewImg) {
                                previewImg.src = `${endpoints.previewFrame}?v=${encodeURIComponent(payload.version || Date.now())}`;
                                previewImg.hidden = false;
                            }
                        })
                        .listen('.obs.command.acknowledged', (payload) => {
                            if (payload.status === 'failed' && payload.error_message) {
                                showToast(`OBS Error: ${payload.error_message}`);
                            }
                        });
                }

                renderAll();

                // Auto-start OBS Pixel Feed underlay on launch whenever Studio is online
                const initialViewMode = document.getElementById('live-view-mode')?.value;
                if (initialViewMode === 'snapshot') {
                    if (previewStatusLabel) previewStatusLabel.textContent = 'OBS snapshot feed active (~1.8 fps)';
                    enableSnapshotPreview(true);
                }
            })();
        </script>
    </body>
</html>
