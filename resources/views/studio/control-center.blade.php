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

            .stack-grid {
                display: grid;
                gap: 0.625rem;
            }

            .prop-editor {
                display: grid;
                gap: 0.5rem;
                padding: 0.625rem;
                border-radius: 0.5rem;
                background-color: var(--bg-chrome);
                border: 1px solid var(--hairline);
            }

            .prop-check {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding: 0.375rem 0.5rem;
                border-radius: 0.375rem;
                background-color: var(--bg-raised);
                cursor: pointer;
            }

            .prop-check span {
                font-size: 0.75rem;
                color: var(--ink-secondary);
            }

            .prop-check input[type="checkbox"] {
                inline-size: 1rem;
                block-size: 1rem;
                accent-color: var(--gold-accent);
                cursor: pointer;
            }

            .color-picker-row {
                display: grid;
                grid-template-columns: 3rem minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.5rem;
            }

            .color-picker-row input[type="color"] {
                inline-size: 3rem;
                block-size: 2.25rem;
                padding: 0.125rem;
                border-radius: 0.375rem;
                border: 1px solid var(--hairline-strong);
                background-color: var(--bg-raised);
                cursor: pointer;
            }

            .details-raw {
                border-radius: 0.5rem;
                border: 1px solid var(--hairline);
                background-color: var(--bg-chrome);
                padding: 0.375rem 0.5rem;
            }

            .details-raw summary {
                cursor: pointer;
                font-size: 0.75rem;
                color: var(--ink-muted);
                user-select: none;
            }

            .details-raw[open] summary {
                margin-block-end: 0.5rem;
                color: var(--ink-secondary);
            }

            .toast-bar.is-error {
                border-color: rgba(229, 72, 77, 0.55);
                color: #F4A3A6;
            }

            .toast-bar.is-success {
                border-color: rgba(76, 195, 138, 0.45);
                color: #9EE6C3;
            }

            .s-btn[disabled] {
                opacity: 0.55;
                cursor: wait;
            }

            .upload-box {
                display: grid;
                gap: 0.45rem;
                padding: 0.55rem;
                border-radius: 0.5rem;
                border: 1px dashed var(--hairline-strong);
                background: rgba(205, 173, 105, 0.04);
            }

            .upload-box-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .upload-preview-thumb {
                max-inline-size: 100%;
                max-block-size: 6rem;
                object-fit: contain;
                border-radius: 0.375rem;
                border: 1px solid var(--hairline);
                background: #080807;
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
                <button type="button" id="open-install-agent-btn" class="s-btn" commandfor="install-agent-dialog" command="show-modal">Install Agent</button>
                <a href="{{ url('/admin/studio-settings') }}" class="s-btn">Storage Settings</a>
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
                <div
                    id="agent-offline-banner"
                    @if ($studio->isOnline()) hidden @endif
                    style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem 0.875rem; border-radius: 0.5rem; border: 1px solid rgba(205, 173, 105, 0.45); background: linear-gradient(135deg, rgba(205, 173, 105, 0.1), rgba(18, 18, 16, 0.92));"
                >
                    <div style="display: grid; gap: 0.125rem;">
                        <strong id="agent-banner-title" style="font-size: 0.875rem; color: var(--gold-light);">
                            {{ $agentReachable ? 'OBS Agent connected — open OBS Studio' : 'Connect OBS Studio on your Mac or Windows PC' }}
                        </strong>
                        <span id="agent-banner-copy" class="dock-subtitle" style="color: var(--ink-secondary);">
                            {{ $agentReachable
                                ? 'In OBS Studio, open Tools -> WebSocket Server Settings and enable WebSocket server (Port 4455).'
                                : 'Install the pre-configured OBS Agent in one click — no terminal coding or token setup needed.' }}
                        </span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        <button type="button" id="agent-banner-cta" class="s-btn s-btn-gold s-btn-xs" commandfor="install-agent-dialog" command="show-modal">
                            {{ $agentReachable ? 'Install on Another PC' : 'Install OBS Agent' }}
                        </button>
                    </div>
                </div>

                <div class="stage-toolbar">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                        <span class="stat-chip" id="stage-editing-label">Scene: {{ $initialEditingScene ?: 'None' }}</span>
                        <button type="button" id="cut-editing-scene-btn" class="s-btn s-btn-gold s-btn-xs">Cut Scene Live</button>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;">
                        <label for="live-view-mode" class="field-label">Live View</label>
                        <select id="live-view-mode" class="s-select" style="min-block-size: 1.875rem; font-size: 0.75rem;">
                            <option value="snapshot" @selected($studio->isOnline())>OBS Preview · Balanced (2.5s)</option>
                            <option value="snapshot-fast">OBS Preview · Active (1.2s)</option>
                            <option value="layout" @selected(! $studio->isOnline())>Layout Grid Only (0 Load)</option>
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

                    {{-- Tab 1: Scene Source Stack & Transform/Visual Properties Inspector --}}
                    <div id="tab-sources" class="tab-panel" style="display: grid; gap: 0.75rem;">
                        <div class="dock-head">
                            <div>
                                <h3 class="dock-title">Scene Layers</h3>
                                <span id="layer-count-meta" class="dock-subtitle">0 sources</span>
                            </div>
                            <button
                                type="button"
                                class="s-btn s-btn-gold s-btn-xs"
                                commandfor="add-source-dialog"
                                command="show-modal"
                            >
                                + Add Source
                            </button>
                        </div>
                        <div id="layers-list" class="layer-list"></div>

                        <div id="selected-source-card" class="stack-grid" style="padding-block-start: 0.625rem; border-block-start: 1px solid var(--hairline);" hidden>
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;">
                                <div style="min-inline-size: 0;">
                                    <div id="sel-source-name" style="font-weight: 600; font-size: 0.875rem; color: var(--gold-light); overflow: hidden; text-overflow: ellipsis;"></div>
                                    <div id="sel-source-kind" class="dock-subtitle"></div>
                                </div>
                                <div style="display: flex; gap: 0.25rem; flex-shrink: 0;">
                                    <button type="button" id="btn-fit-source" class="s-btn s-btn-xs">Fit 16:9</button>
                                    <button type="button" id="btn-center-source" class="s-btn s-btn-xs">Center</button>
                                </div>
                            </div>

                            <div style="display: flex; gap: 0.375rem; align-items: center;">
                                <input type="text" id="rename-source-input" class="s-input" placeholder="Source name..." aria-label="Rename selected source" style="flex: 1; min-inline-size: 0; min-block-size: 2rem;">
                                <button type="button" id="btn-rename-source" class="s-btn s-btn-xs">Rename</button>
                            </div>

                            <div class="transform-grid">
                                <label class="field-stack">
                                    <span class="field-label">X Position (px)</span>
                                    <input type="number" id="tf-x" class="s-input" step="1">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Y Position (px)</span>
                                    <input type="number" id="tf-y" class="s-input" step="1">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Width (px)</span>
                                    <input type="number" id="tf-w" class="s-input" step="1" min="16">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Height (px)</span>
                                    <input type="number" id="tf-h" class="s-input" step="1" min="16">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Rotation (°)</span>
                                    <input type="number" id="tf-rot" class="s-input" step="1">
                                </label>
                                <div style="display: flex; align-items: flex-end;">
                                    <button type="button" id="btn-apply-transform" class="s-btn s-btn-gold" style="inline-size: 100%;">Apply Size &amp; Pos</button>
                                </div>
                            </div>

                            <div class="stack-grid" style="padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <div>
                                        <span class="field-label" style="color: var(--ink-primary);">Source Properties</span>
                                        <div id="settings-load-status" class="dock-subtitle">Select a source to inspect</div>
                                    </div>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <button type="button" id="btn-refresh-browser" class="s-btn s-btn-xs" hidden>Refresh Page</button>
                                        <button type="button" id="btn-inspect-settings" class="s-btn s-btn-xs">Reload</button>
                                    </div>
                                </div>

                                <div id="visual-settings-fields" class="prop-editor"></div>

                                <button type="button" id="btn-save-settings" class="s-btn s-btn-gold">Save Properties to OBS</button>

                                <details id="raw-json-details" class="details-raw">
                                    <summary>Advanced: Raw JSON Settings</summary>
                                    <textarea id="source-settings-json" class="s-textarea" rows="4" placeholder="{}" style="inline-size: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem;"></textarea>
                                </details>
                            </div>

                            <div style="display: flex; gap: 0.375rem; padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);">
                                <button type="button" id="btn-remove-scene-item" class="s-btn s-btn-xs" style="flex: 1;">Remove from Scene</button>
                                <button type="button" id="btn-delete-source-forever" class="s-btn s-btn-xs s-btn-danger" style="flex: 1;">Delete Source</button>
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

                <form id="add-source-form" class="stack-grid" style="gap: 0.75rem;">
                    <div class="field-stack">
                        <label for="add-source-mode" class="field-label">How to Add</label>
                        <select id="add-source-mode" class="s-select">
                            <option value="create">Create New OBS Source</option>
                            <option value="existing">Reuse Existing OBS Source</option>
                        </select>
                    </div>

                    <div id="add-source-new-fields" class="stack-grid">
                        <label class="field-stack">
                            <span class="field-label">Source Type</span>
                            <select id="add-source-kind" class="s-select"></select>
                        </label>
                        <label class="field-stack">
                            <span class="field-label">Source Name</span>
                            <input type="text" id="add-source-name" class="s-input" placeholder="e.g. Guest Browser Overlay" required>
                        </label>
                        <div id="add-source-dynamic-fields" class="stack-grid"></div>
                    </div>

                    <div id="add-source-existing-fields" class="stack-grid" hidden>
                        <label class="field-stack">
                            <span class="field-label">Existing OBS Source</span>
                            <select id="add-source-existing" class="s-select"></select>
                        </label>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="s-btn" commandfor="add-source-dialog" command="close">Cancel</button>
                        <button type="submit" id="add-source-submit-btn" class="s-btn s-btn-gold">Add to Scene</button>
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
                <form id="rename-scene-form" class="stack-grid" style="gap: 0.75rem;">
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
                <form id="create-collection-form" class="stack-grid" style="gap: 0.75rem;">
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

        {{-- Native Declarative Modal: Confirm Permanent Source Delete --}}
        <dialog id="delete-source-dialog" class="obs-dialog" aria-labelledby="delete-source-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="delete-source-title" class="dialog-title">Delete OBS Source Permanently</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="delete-source-dialog" command="close">Close</button>
                </div>
                <p id="delete-source-copy" style="margin: 0; color: var(--ink-secondary);"></p>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="s-btn" commandfor="delete-source-dialog" command="close">Cancel</button>
                    <button type="button" id="confirm-delete-source-btn" class="s-btn s-btn-danger">Delete Forever</button>
                </div>
            </div>
        </dialog>

        {{-- Native Declarative Modal: Zero-Code Mac & Windows OBS Agent Installer --}}
        <dialog id="install-agent-dialog" class="obs-dialog" aria-labelledby="install-agent-title" style="max-inline-size: 38rem;">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <div>
                        <h2 id="install-agent-title" class="dialog-title">Connect OBS Studio to Obsisstant</h2>
                        <div class="dock-subtitle">Run our tiny background helper on your Mac or Windows streaming PC — no coding required.</div>
                    </div>
                    <button type="button" class="s-btn s-btn-xs" id="install-agent-close-x" commandfor="install-agent-dialog" command="close">Close</button>
                </div>

                <div
                    id="install-modal-live-pill"
                    style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--hairline-strong); background: var(--bg-raised); font-size: 0.75rem;"
                >
                    <span id="install-modal-live-text" style="color: var(--ink-secondary);">
                        {{ $studio->isOnline()
                            ? 'OBS Studio is Online and synced!'
                            : ($agentReachable
                                ? 'OBS Agent is running on your PC! Open OBS -> Tools -> WebSocket Server Settings to finish.'
                                : 'Waiting for OBS Agent connection...') }}
                    </span>
                    <button type="button" class="s-btn s-btn-xs" id="install-check-status-btn">Check Now</button>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div class="tab-row" style="inline-size: auto; min-inline-size: 13rem;" role="tablist" aria-label="Streaming PC Operating System">
                        <button type="button" class="tab-btn" role="tab" aria-selected="true" data-agent-os="mac">macOS (Apple / Intel)</button>
                        <button type="button" class="tab-btn" role="tab" aria-selected="false" data-agent-os="windows">Windows 10 / 11</button>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; color: var(--ink-muted); cursor: pointer;">
                        <input type="checkbox" id="install-studio-only-toggle">
                        <span>Bind to this Studio only</span>
                    </label>
                </div>

                <div id="agent-panel-mac" style="display: grid; gap: 0.75rem;">
                    <a
                        id="download-mac-installer-btn"
                        href="{{ $installerPayload['mac_download_url'] }}"
                        class="s-btn s-btn-gold"
                        style="justify-content: center; font-size: 0.875rem; padding: 0.65rem 1rem;"
                    >
                        Download Mac Installer (.zip)
                    </a>

                    <ol style="margin: 0; padding-inline-start: 1.25rem; display: grid; gap: 0.35rem; font-size: 0.8125rem; color: var(--ink-secondary);">
                        <li>Unzip <code>Obsisstant-OBS-Agent-Mac.zip</code> inside Downloads.</li>
                        <li><strong>Right-click</strong> (or Control-click) <code>Install &amp; Run Obsisstant Agent.command</code> → click <strong>Open</strong>.</li>
                        <li>In OBS Studio: open <strong>Tools → WebSocket Server Settings</strong> and check <strong>Enable WebSocket server</strong>.</li>
                    </ol>

                    <div style="display: grid; gap: 0.375rem; padding: 0.625rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--hairline); background: var(--bg-raised);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                            <span style="font-size: 0.75rem; font-weight: 500; color: var(--gold-light);">Fastest Option (Skips Mac Gatekeeper Popup)</span>
                            <button type="button" class="s-btn s-btn-xs" id="copy-mac-cmd-btn">Copy Terminal Command</button>
                        </div>
                        <input type="text" id="mac-oneclick-cmd" class="s-input" readonly value="{{ $installerPayload['mac_command'] }}" style="font-size: 0.75rem; color: var(--gold-light);">
                        <span class="dock-subtitle" style="font-size: 0.7rem;">Open Terminal app, paste, and press Return — automatically installs &amp; runs in the background.</span>
                    </div>
                </div>

                <div id="agent-panel-windows" hidden style="display: grid; gap: 0.75rem;">
                    <a
                        id="download-win-installer-btn"
                        href="{{ $installerPayload['windows_download_url'] }}"
                        class="s-btn s-btn-gold"
                        style="justify-content: center; font-size: 0.875rem; padding: 0.65rem 1rem;"
                    >
                        Download Windows Installer (.zip)
                    </a>

                    <ol style="margin: 0; padding-inline-start: 1.25rem; display: grid; gap: 0.35rem; font-size: 0.8125rem; color: var(--ink-secondary);">
                        <li>Right-click <code>Obsisstant-OBS-Agent-Windows.zip</code> → <strong>Extract All</strong>.</li>
                        <li>Double-click <code>Install &amp; Run Obsisstant Agent.bat</code> *(auto-starts silently on Windows boot)*.</li>
                        <li>In OBS Studio: open <strong>Tools → WebSocket Server Settings</strong> and check <strong>Enable WebSocket server</strong>.</li>
                    </ol>

                    <div style="display: grid; gap: 0.375rem; padding: 0.625rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--hairline); background: var(--bg-raised);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                            <span style="font-size: 0.75rem; font-weight: 500; color: var(--gold-light);">Or 1-Click PowerShell Setup</span>
                            <button type="button" class="s-btn s-btn-xs" id="copy-win-cmd-btn">Copy PowerShell Command</button>
                        </div>
                        <input type="text" id="win-oneclick-cmd" class="s-input" readonly value="{{ $installerPayload['windows_command'] }}" style="font-size: 0.75rem; color: var(--gold-light);">
                        <span class="dock-subtitle" style="font-size: 0.7rem;">Open PowerShell on Windows, paste, and press Enter — installs Node.js (if needed) &amp; starts silently.</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; padding-block-start: 0.25rem; border-block-start: 1px solid var(--hairline);">
                    <span class="dock-subtitle">Everything stays pre-configured with your account URL &amp; token.</span>
                    <button type="button" class="s-btn" id="install-agent-dismiss-btn" commandfor="install-agent-dialog" command="close">I'll do this later</button>
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
                    snapshot: @json(route('studio.snapshot', ['studio' => $studio->slug])),
                    previewToggle: @json(route('studio.preview-toggle', ['studio' => $studio->slug])),
                    previewFrame: @json(route('studio.preview-frame', ['studio' => $studio->slug])),
                    mediaUpload: @json(route('studio.media-upload', ['studio' => $studio->slug])),
                    mediaStoragePath: @json(route('studio.media-storage-path', ['studio' => $studio->slug])),
                    bootstrapToken: @json(route('agent-installer.bootstrap-token')),
                    storageSettingsPage: @json(url('/admin/studio-settings')),
                };

                const initialInstallerPayload = @json($installerPayload);
                let configuredMediaDir = @json($mediaStoragePath, JSON_UNESCAPED_SLASHES);
                let liveState = @json($liveState ?: new stdClass());
                let studioStatus = @json($studio->status);
                let agentReachable = @json($agentReachable);
                let editingScene = @json($initialEditingScene);
                let selectedSceneItemId = null;
                let previewLeaseTimer = null;
                let previewFallbackTimer = null;
                let previewControlFallbackTimer = null;
                let studioChannel = null;
                let snapshotInFlight = null;
                let lastEchoPreviewAt = 0;
                let lastEchoStateAt = 0;
                let lastSeenPreviewVersion = null;
                let isDraggingStage = false;
                let vcamStream = null;
                let pendingDeleteScene = null;
                let pendingDeleteSourceName = null;
                let loadedSettingsKey = null;
                let settingsLoadSeq = 0;
                let currentLoadedSettings = {};
                let currentLoadedInputKind = '';
                const pendingCommandResolvers = new Map();
                const optimisticTransforms = new Map();

                const INPUT_KIND_CATALOG = {
                    browser_source: { label: 'Browser Overlay (Web Page / URL)', priority: 1 },
                    image_source: { label: 'Image (PNG / JPG / GIF / WebP)', priority: 2 },
                    ffmpeg_source: { label: 'Media Source (Video / Audio File)', priority: 3 },
                    color_source_v3: { label: 'Solid Color Block', priority: 4 },
                    color_source_v2: { label: 'Solid Color Block (Legacy)', priority: 5 },
                    color_source: { label: 'Solid Color Block (Classic)', priority: 6 },
                    text_ft2_source_v2: { label: 'Text Label', priority: 7 },
                    text_ft2_source: { label: 'Text Label (Classic)', priority: 8 },
                    text_gdiplus_v3: { label: 'Text Label (GDI+)', priority: 9 },
                    text_gdiplus_v2: { label: 'Text Label (GDI+ Classic)', priority: 10 },
                    text_gdiplus: { label: 'Text Label (GDI+)', priority: 11 },
                    slideshow_v2: { label: 'Image Slideshow', priority: 12 },
                    slideshow: { label: 'Image Slideshow (Classic)', priority: 13 },
                    'macos-avcapture': { label: 'Camera / Video Capture (macOS)', priority: 14 },
                    'macos-avcapture-fast': { label: 'Camera / Low-Latency Capture (macOS)', priority: 15 },
                    av_capture_input_v2: { label: 'Camera Capture (macOS Legacy v2)', priority: 16 },
                    av_capture_input: { label: 'Camera Capture (macOS Legacy)', priority: 17 },
                    screen_capture: { label: 'Screen & Display Capture (macOS)', priority: 18 },
                    sck_audio_capture: { label: 'Desktop / App Audio Capture (macOS)', priority: 19 },
                    display_capture: { label: 'Display / Screen Capture', priority: 20 },
                    monitor_capture: { label: 'Display Capture', priority: 21 },
                    window_capture: { label: 'Application Window Capture', priority: 22 },
                    coreaudio_input_capture: { label: 'Microphone / Audio Input (macOS)', priority: 23 },
                    coreaudio_output_capture: { label: 'Desktop Audio Output (macOS)', priority: 24 },
                    wasapi_input_capture: { label: 'Microphone / Audio Input', priority: 25 },
                    wasapi_output_capture: { label: 'Desktop Audio Output', priority: 26 },
                    pulse_input_capture: { label: 'Microphone / Audio Input (PulseAudio)', priority: 27 },
                    pulse_output_capture: { label: 'Desktop Audio Output (PulseAudio)', priority: 28 },
                    'syphon-input': { label: 'Syphon Video Feed (macOS)', priority: 29 },
                    dshow_input: { label: 'Camera / Video Capture Device', priority: 30 },
                    game_capture: { label: 'Game Capture', priority: 31 },
                    vlc_source: { label: 'VLC Media Playlist', priority: 32 },
                };

                const COMMAND_LABELS = {
                    CreateInput: 'Create Source',
                    CreateSceneItem: 'Add Source to Scene',
                    SetInputSettings: 'Save Source Properties',
                    SetInputName: 'Rename Source',
                    RemoveInput: 'Delete Source',
                    RemoveSceneItem: 'Remove from Scene',
                    SetSceneItemTransform: 'Update Position & Size',
                    SetSceneItemIndex: 'Reorder Layer',
                    SetSceneItemEnabled: 'Toggle Visibility',
                    SetSceneItemLocked: 'Toggle Lock',
                    SetInputMute: 'Toggle Audio Mute',
                    SetInputVolume: 'Update Volume',
                    CreateScene: 'Create Scene',
                    SetSceneName: 'Rename Scene',
                    RemoveScene: 'Delete Scene',
                    SetCurrentProgramScene: 'Switch Program Scene',
                    SetCurrentPreviewScene: 'Switch Preview Scene',
                    SetCurrentSceneCollection: 'Switch Collection',
                    CreateSceneCollection: 'Create Collection',
                    PressInputPropertiesButton: 'Refresh Source',
                    'obsisstant.RefreshAllState': 'Sync OBS State',
                    'obsisstant.FetchInputSettings': 'Load Source Properties',
                };

                const formatInputKindLabel = (kind) => {
                    if (!kind) return 'OBS Source';
                    if (INPUT_KIND_CATALOG[kind]) return INPUT_KIND_CATALOG[kind].label;

                    const acronyms = { obs: 'OBS', macos: 'macOS', sck: 'ScreenCaptureKit', av: 'AV', vlc: 'VLC', url: 'URL', fps: 'FPS', id: 'ID' };
                    return String(kind)
                        .replace(/_v\d+$/i, '')
                        .split(/[_-]+/)
                        .filter(Boolean)
                        .map((part) => acronyms[part.toLowerCase()] || (part.charAt(0).toUpperCase() + part.slice(1)))
                        .join(' ');
                };

                const formatCommandLabel = (requestType) => COMMAND_LABELS[requestType] || requestType;

                const humanizeFieldKey = (key) => {
                    const known = {
                        local_file: 'File Path / URL',
                        is_local_file: 'Local File',
                        looping: 'Loop Playback',
                        restart_on_activate: 'Restart When Scene Becomes Active',
                        clear_on_media_end: 'Hide When Finished',
                        speed_percent: 'Playback Speed (%)',
                        hw_decode: 'Hardware Decoding',
                        shutdown: 'Shutdown Source When Hidden',
                        restart_when_active: 'Refresh Page When Scene Becomes Active',
                        css: 'Custom CSS',
                        reroute_audio: 'Control Audio via OBS',
                        unload: 'Unload Image When Hidden',
                        read_from_file: 'Read Text From File',
                    };
                    if (known[key]) return known[key];

                    return String(key)
                        .split(/[_-]+/)
                        .filter(Boolean)
                        .map((word) => (word.toLowerCase() === 'fps' ? 'FPS' : word.toLowerCase() === 'url' ? 'URL' : word.charAt(0).toUpperCase() + word.slice(1)))
                        .join(' ');
                };

                const obsColorToHexAlpha = (obsColorInt) => {
                    const val = Number(obsColorInt ?? 0xff000000) >>> 0;
                    const r = val & 0xff;
                    const g = (val >>> 8) & 0xff;
                    const b = (val >>> 16) & 0xff;
                    const a = (val >>> 24) & 0xff;
                    const hex = `#${[r, g, b].map((c) => c.toString(16).padStart(2, '0')).join('')}`;
                    return { hex, alphaPct: Math.round((a / 255) * 100) };
                };

                const hexAlphaToObsColor = (hexStr, alphaPct = 100) => {
                    const clean = String(hexStr || '#ffffff').replace('#', '');
                    const full = clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean.padEnd(6, '0').slice(0, 6);
                    const r = parseInt(full.slice(0, 2), 16) || 0;
                    const g = parseInt(full.slice(2, 4), 16) || 0;
                    const b = parseInt(full.slice(4, 6), 16) || 0;
                    const clamped = Math.max(0, Math.min(100, Number(alphaPct ?? 100)));
                    const a = Math.round((clamped / 100) * 255);
                    return (((a & 0xff) << 24) | ((b & 0xff) << 16) | ((g & 0xff) << 8) | (r & 0xff)) >>> 0;
                };

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
                const showToast = (msg, tone = 'info') => {
                    if (!toastEl) return;
                    toastEl.textContent = msg;
                    toastEl.classList.remove('is-error', 'is-success');
                    if (tone === 'error') toastEl.classList.add('is-error');
                    if (tone === 'success') toastEl.classList.add('is-success');
                    toastEl.hidden = false;
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => { toastEl.hidden = true; }, tone === 'error' ? 4200 : 2400);
                };

                const renderRecentLogs = (logs) => {
                    const list = document.getElementById('recent-logs-list');
                    if (!list || !Array.isArray(logs)) return;

                    list.innerHTML = '';
                    if (logs.length === 0) {
                        const empty = document.createElement('span');
                        empty.className = 'dock-subtitle';
                        empty.textContent = 'No commands dispatched yet.';
                        list.appendChild(empty);
                        return;
                    }

                    logs.slice(0, 12).forEach((log) => {
                        const row = document.createElement('div');
                        row.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:0.5rem;padding:0.25rem 0.375rem;border-radius:0.375rem;background:var(--bg-raised);';
                        if (log.error_message) row.title = log.error_message;

                        const req = document.createElement('span');
                        req.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                        req.textContent = formatCommandLabel(log.request_type || '');

                        const st = document.createElement('span');
                        const statusVal = String(log.status || 'dispatched');
                        st.style.color = statusVal === 'failed'
                            ? 'var(--danger)'
                            : (statusVal === 'succeeded' ? 'var(--positive)' : 'var(--gold-light)');
                        st.textContent = statusVal.charAt(0).toUpperCase() + statusVal.slice(1);

                        row.append(req, st);
                        list.appendChild(row);
                    });
                };

                const isSnapshotMode = () => {
                    const mode = document.getElementById('live-view-mode')?.value;
                    return mode === 'snapshot' || mode === 'snapshot-fast';
                };

                let lastTransformSettledAt = 0;
                let transformSeq = 0;

                const mergeTransformIntoItem = (item, patch) => {
                    const currentTf = item.sceneItemTransform || {};
                    const nextTf = { ...currentTf, ...patch };
                    const cropW = Number(nextTf.cropLeft || 0) + Number(nextTf.cropRight || 0);
                    const cropH = Number(nextTf.cropTop || 0) + Number(nextTf.cropBottom || 0);
                    const srcW = Math.max(0, Number(nextTf.sourceWidth || 0) - cropW);
                    const srcH = Math.max(0, Number(nextTf.sourceHeight || 0) - cropH);

                    if (patch.boundsWidth !== undefined && nextTf.boundsType && nextTf.boundsType !== 'OBS_BOUNDS_NONE') {
                        nextTf.width = Number(patch.boundsWidth);
                    } else if (patch.scaleX !== undefined && srcW > 0) {
                        nextTf.width = Math.round(srcW * Number(patch.scaleX));
                    }

                    if (patch.boundsHeight !== undefined && nextTf.boundsType && nextTf.boundsType !== 'OBS_BOUNDS_NONE') {
                        nextTf.height = Number(patch.boundsHeight);
                    } else if (patch.scaleY !== undefined && srcH > 0) {
                        nextTf.height = Math.round(srcH * Number(patch.scaleY));
                    }

                    item.sceneItemTransform = nextTf;
                };

                const applyIncomingLiveState = (nextState) => {
                    if (!nextState || typeof nextState !== 'object') return;
                    const now = Date.now();
                    for (const [key, entry] of optimisticTransforms.entries()) {
                        if (entry.expiresAt <= now) {
                            optimisticTransforms.delete(key);
                            continue;
                        }
                        const list = nextState?.sceneItemsByScene?.[entry.sceneName];
                        if (Array.isArray(list)) {
                            const target = list.find((i) => Number(i.sceneItemId) === Number(entry.sceneItemId));
                            if (target) {
                                mergeTransformIntoItem(target, entry.patch);
                            }
                        }
                    }
                    liveState = nextState;
                };

                const refreshStudioSnapshot = async () => {
                    if (snapshotInFlight) return snapshotInFlight;

                    const reqStartedAt = Date.now();
                    snapshotInFlight = (async () => {
                        try {
                            const res = await fetch(endpoints.snapshot, { headers: { Accept: 'application/json' } });
                            if (!res.ok) return null;
                            const data = await res.json();
                            if (Array.isArray(data.recent_logs)) renderRecentLogs(data.recent_logs);

                            // Discard stale HTTP snapshots when fresher Echo or transform updates arrived in-flight
                            if (lastEchoStateAt > reqStartedAt || lastTransformSettledAt > reqStartedAt) {
                                return data;
                            }

                            if (data.status) studioStatus = data.status;
                            if (typeof data.agent_reachable === 'boolean') agentReachable = data.agent_reachable;
                            if (data.live_state) applyIncomingLiveState(data.live_state);
                            renderAll();

                            const previewImgEl = document.getElementById('live-preview-img');
                            if (
                                data.preview_version
                                && data.preview_version !== lastSeenPreviewVersion
                                && isSnapshotMode()
                                && previewImgEl
                                && !window.Echo
                            ) {
                                lastSeenPreviewVersion = data.preview_version;
                                previewImgEl.src = `${endpoints.previewFrame}?v=${encodeURIComponent(data.preview_version)}`;
                                previewImgEl.hidden = false;
                            }
                            return data;
                        } catch {
                            return null;
                        } finally {
                            snapshotInFlight = null;
                        }
                    })();

                    return snapshotInFlight;
                };

                let snapshotDebounceTimer = null;
                const scheduleSnapshotRefresh = (delayMs = 260) => {
                    clearTimeout(snapshotDebounceTimer);
                    snapshotDebounceTimer = setTimeout(() => {
                        snapshotDebounceTimer = null;
                        refreshStudioSnapshot();
                    }, delayMs);
                };

                const waitForCommandResult = (commandUuid, timeoutMs = 4500) => new Promise((resolve) => {
                    let done = false;
                    const finish = (data) => {
                        if (done) return;
                        done = true;
                        pendingCommandResolvers.delete(commandUuid);
                        resolve(data);
                    };

                    pendingCommandResolvers.set(commandUuid, finish);

                    const started = Date.now();
                    const poll = async () => {
                        if (done) return;
                        try {
                            const res = await fetch(endpoints.commandResult.replace('__UUID__', commandUuid), {
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

                    // Let Echo push resolve instantaneously; only fall back to HTTP if delayed
                    setTimeout(poll, window.Echo ? 750 : 220);
                });

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
                        const errPayload = await res.json().catch(() => null);
                        throw new Error(errPayload?.message || `HTTP ${res.status}`);
                    }
                    return res.json();
                };

                const sendObsCommand = async (requestType, requestData = {}, options = {}) => {
                    const { wait = true, silent = false } = options;
                    const label = formatCommandLabel(requestType);
                    if (!silent) showToast(`${label}...`);

                    try {
                        const res = await postJson(endpoints.command, {
                            request_type: requestType,
                            request_data: requestData,
                        });

                        if (!wait) return res;

                        const settled = await waitForCommandResult(res.command_uuid, 4500);
                        if (requestType !== 'obsisstant.FetchInputSettings' && (Date.now() - lastEchoStateAt) > 650) {
                            scheduleSnapshotRefresh(220);
                        }

                        if (settled?.status === 'failed') {
                            showToast(`OBS Error: ${settled.error_message || `${label} failed`}`, 'error');
                        } else if (settled?.status === 'succeeded') {
                            if (!silent) showToast(`${label} complete`, 'success');
                        } else if (requestType !== 'obsisstant.FetchInputSettings') {
                            scheduleSnapshotRefresh(700);
                        }

                        return { ...res, settled };
                    } catch (err) {
                        showToast(`Failed ${label}: ${err.message}`, 'error');
                        throw err;
                    }
                };

                const commitSceneItemTransform = async (sceneItemId, transformPatch) => {
                    if (!editingScene || sceneItemId === null || sceneItemId === undefined) return;
                    const sceneName = editingScene;
                    const lockKey = `${sceneName}::${sceneItemId}`;
                    const items = getItemsForEditingScene();
                    const item = items.find((i) => Number(i.sceneItemId) === Number(sceneItemId));
                    const backupTransform = item?.sceneItemTransform ? { ...item.sceneItemTransform } : null;
                    const mySeq = ++transformSeq;

                    if (item) {
                        mergeTransformIntoItem(item, transformPatch);
                        optimisticTransforms.set(lockKey, {
                            seq: mySeq,
                            sceneName,
                            sceneItemId: Number(sceneItemId),
                            patch: { ...transformPatch },
                            expiresAt: Date.now() + 5000,
                        });
                        renderStageLayers();
                        renderLayersInspector();
                    }

                    try {
                        const res = await sendObsCommand('SetSceneItemTransform', {
                            sceneName,
                            sceneItemId: Number(sceneItemId),
                            sceneItemTransform: transformPatch,
                        });

                        const currentLock = optimisticTransforms.get(lockKey);
                        if (currentLock?.seq === mySeq) {
                            optimisticTransforms.delete(lockKey);
                        }

                        if (res?.settled?.status === 'failed') {
                            if (backupTransform && (!currentLock || currentLock.seq === mySeq)) {
                                const cur = (liveState?.sceneItemsByScene?.[sceneName] || []).find(
                                    (i) => Number(i.sceneItemId) === Number(sceneItemId)
                                );
                                if (cur) {
                                    cur.sceneItemTransform = backupTransform;
                                    renderStageLayers();
                                    renderLayersInspector();
                                }
                            }
                        } else {
                            lastTransformSettledAt = Date.now();
                        }
                    } catch {
                        const currentLock = optimisticTransforms.get(lockKey);
                        if (currentLock?.seq === mySeq) {
                            optimisticTransforms.delete(lockKey);
                            if (backupTransform) {
                                const cur = (liveState?.sceneItemsByScene?.[sceneName] || []).find(
                                    (i) => Number(i.sceneItemId) === Number(sceneItemId)
                                );
                                if (cur) {
                                    cur.sceneItemTransform = backupTransform;
                                    renderStageLayers();
                                    renderLayersInspector();
                                }
                            }
                        }
                    }
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

                    if (typeof updateAgentOnboardingState === 'function') {
                        updateAgentOnboardingState();
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
                            if (isSnapshotMode()) {
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

                const buildDimensionTransformPatch = (item, targetW, targetH) => {
                    const tf = item?.sceneItemTransform || {};
                    const srcW = Number(tf.sourceWidth || 0) - Number(tf.cropLeft || 0) - Number(tf.cropRight || 0);
                    const srcH = Number(tf.sourceHeight || 0) - Number(tf.cropTop || 0) - Number(tf.cropBottom || 0);

                    if (srcW > 0 && srcH > 0) {
                        return {
                            boundsType: 'OBS_BOUNDS_NONE',
                            scaleX: Number(Math.max(0.01, targetW / srcW).toFixed(4)),
                            scaleY: Number(Math.max(0.01, targetH / srcH).toFixed(4)),
                        };
                    }

                    return {
                        boundsType: 'OBS_BOUNDS_SCALE_INNER',
                        boundsWidth: Math.max(16, Math.round(targetW)),
                        boundsHeight: Math.max(16, Math.round(targetH)),
                    };
                };

                let visualDraftSettings = {};
                let rawJsonEdited = false;

                const syncRawJsonTextarea = (settingsObj) => {
                    const box = document.getElementById('source-settings-json');
                    if (box) {
                        box.value = JSON.stringify(settingsObj || {}, null, 2);
                    }
                    rawJsonEdited = false;
                };

                const refreshStorageFolderLabels = () => {
                    document.querySelectorAll('[data-storage-folder-label]').forEach((el) => {
                        el.textContent = configuredMediaDir;
                        el.title = configuredMediaDir;
                    });
                };

                const uploadImageToDesktopFolder = async (file, setStatus) => {
                    const formData = new FormData();
                    formData.append('image', file);
                    if (setStatus) setStatus(`Uploading ${file.name} to ${configuredMediaDir}...`);

                    const res = await fetch(endpoints.mediaUpload, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: formData,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                        throw new Error(data.message || `Upload failed (HTTP ${res.status})`);
                    }

                    if (data.mode === 'direct' && data.file) {
                        if (setStatus) setStatus(`Saved to ${data.file}`);
                        return data.file;
                    }

                    if (setStatus) setStatus(`Saving on desktop (${data.target_dir})...`);
                    const settled = await waitForCommandResult(data.command_uuid, 15000);
                    if (settled?.status === 'failed') {
                        throw new Error(settled.error_message || 'OBS Agent could not save image into local folder.');
                    }
                    if (!settled?.response_data?.file) {
                        throw new Error('OBS Agent did not respond yet. Ensure obs-agent is running on your computer.');
                    }
                    if (setStatus) setStatus(`Saved to ${settled.response_data.file}`);
                    return settled.response_data.file;
                };

                const browseNativeDesktopFolder = async (btnEl) => {
                    if (studioStatus === 'offline') {
                        window.open(endpoints.storageSettingsPage, '_blank');
                        return;
                    }

                    if (btnEl) btnEl.disabled = true;
                    showToast('Opening native Finder / desktop folder chooser...');
                    try {
                        const res = await postJson(endpoints.command, {
                            request_type: 'obsisstant.PickLocalDirectory',
                            request_data: {
                                prompt: 'Select Obsisstant Image Storage Folder',
                                initialPath: configuredMediaDir,
                            },
                        });
                        const settled = await waitForCommandResult(res.command_uuid, 60000);
                        if (settled?.status === 'failed') {
                            showToast(settled.error_message || 'Could not open native folder picker', 'error');
                            return;
                        }
                        if (settled?.response_data?.cancelled) {
                            showToast('Folder selection cancelled');
                            return;
                        }
                        const picked = String(settled?.response_data?.path || '').trim();
                        if (!picked) {
                            showToast('Folder picker timed out — open Storage Settings to type path manually.', 'error');
                            return;
                        }
                        await postJson(endpoints.mediaStoragePath, { path: picked });
                        configuredMediaDir = picked;
                        refreshStorageFolderLabels();
                        showToast(`Storage folder updated: ${picked}`, 'success');
                    } catch (err) {
                        showToast(`Folder pick failed: ${err.message}`, 'error');
                    } finally {
                        if (btnEl) btnEl.disabled = false;
                    }
                };

                const buildImageUploadWidget = ({ fileInputId, onPathResolved }) => {
                    const box = document.createElement('div');
                    box.className = 'upload-box';

                    const head = document.createElement('div');
                    head.className = 'upload-box-head';
                    const title = document.createElement('span');
                    title.className = 'field-label';
                    title.style.color = 'var(--ink-primary)';
                    title.textContent = 'Upload Image File';

                    const actions = document.createElement('div');
                    actions.style.cssText = 'display:inline-flex;gap:0.25rem;align-items:center;';
                    const pickDirBtn = document.createElement('button');
                    pickDirBtn.type = 'button';
                    pickDirBtn.className = 's-btn s-btn-xs';
                    pickDirBtn.textContent = 'Choose Folder';
                    pickDirBtn.addEventListener('click', () => browseNativeDesktopFolder(pickDirBtn));

                    const settingsLink = document.createElement('a');
                    settingsLink.href = endpoints.storageSettingsPage;
                    settingsLink.target = '_blank';
                    settingsLink.className = 's-btn s-btn-xs';
                    settingsLink.textContent = 'Settings';

                    actions.append(pickDirBtn, settingsLink);
                    head.append(title, actions);

                    const folderInfo = document.createElement('div');
                    folderInfo.className = 'dock-subtitle';
                    folderInfo.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                    folderInfo.textContent = 'Folder: ';
                    const folderPathSpan = document.createElement('span');
                    folderPathSpan.setAttribute('data-storage-folder-label', '');
                    folderPathSpan.textContent = configuredMediaDir;
                    folderInfo.appendChild(folderPathSpan);

                    const fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    if (fileInputId) fileInput.id = fileInputId;
                    fileInput.accept = 'image/png,image/jpeg,image/gif,image/webp,image/bmp';
                    fileInput.className = 's-input';
                    fileInput.style.padding = '0.25rem 0.5rem';

                    const thumb = document.createElement('img');
                    thumb.className = 'upload-preview-thumb';
                    thumb.alt = 'Uploaded image preview';
                    thumb.hidden = true;

                    const statusNote = document.createElement('div');
                    statusNote.className = 'dock-subtitle';
                    statusNote.hidden = true;

                    fileInput.addEventListener('change', async () => {
                        const file = fileInput.files?.[0];
                        if (!file) return;

                        thumb.src = URL.createObjectURL(file);
                        thumb.hidden = false;
                        statusNote.hidden = false;
                        fileInput.disabled = true;

                        try {
                            const savedPath = await uploadImageToDesktopFolder(file, (txt) => {
                                statusNote.textContent = txt;
                            });
                            showToast(`Image saved to ${savedPath}`, 'success');
                            await onPathResolved(savedPath, file);
                        } catch (err) {
                            statusNote.textContent = err.message;
                            showToast(`Upload error: ${err.message}`, 'error');
                        } finally {
                            fileInput.disabled = false;
                        }
                    });

                    box.append(head, folderInfo, fileInput, thumb, statusNote);
                    return box;
                };

                const renderVisualSettingsEditor = (inputKind, settings) => {
                    const container = document.getElementById('visual-settings-fields');
                    const refreshBrowserBtn = document.getElementById('btn-refresh-browser');
                    if (!container) return;

                    const kind = String(inputKind || currentLoadedInputKind || '');
                    const sourceSettings = (settings && typeof settings === 'object' && !Array.isArray(settings)) ? settings : {};
                    visualDraftSettings = JSON.parse(JSON.stringify(sourceSettings));
                    currentLoadedSettings = sourceSettings;
                    currentLoadedInputKind = kind;
                    syncRawJsonTextarea(visualDraftSettings);

                    if (refreshBrowserBtn) {
                        refreshBrowserBtn.hidden = !kind.includes('browser');
                    }

                    container.innerHTML = '';
                    const handledKeys = new Set();
                    const dims = getVideoDims();

                    const commitDraft = () => {
                        syncRawJsonTextarea(visualDraftSettings);
                    };

                    const appendTextField = ({ key, label, value, type = 'text', placeholder = '', multiline = false, onChange }) => {
                        handledKeys.add(key);
                        const wrap = document.createElement('label');
                        wrap.className = 'field-stack';
                        const span = document.createElement('span');
                        span.className = 'field-label';
                        span.textContent = label;
                        const input = document.createElement(multiline ? 'textarea' : 'input');
                        if (!multiline) input.type = type;
                        else input.rows = 3;
                        input.className = multiline ? 's-textarea' : 's-input';
                        input.setAttribute('data-prop-key', key);
                        input.placeholder = placeholder;
                        input.value = value ?? '';
                        input.addEventListener('input', () => {
                            onChange(type === 'number' ? Number(input.value || 0) : input.value);
                            commitDraft();
                        });
                        wrap.append(span, input);
                        container.appendChild(wrap);
                    };

                    const appendCheckField = ({ key, label, checked, onChange }) => {
                        handledKeys.add(key);
                        const wrap = document.createElement('label');
                        wrap.className = 'prop-check';
                        const span = document.createElement('span');
                        span.textContent = label;
                        const cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.checked = Boolean(checked);
                        cb.addEventListener('change', () => {
                            onChange(cb.checked);
                            commitDraft();
                        });
                        wrap.append(span, cb);
                        container.appendChild(wrap);
                    };

                    const appendColorField = ({ key, label, initialInt }) => {
                        handledKeys.add(key);
                        const parsed = obsColorToHexAlpha(initialInt);
                        const wrap = document.createElement('div');
                        wrap.className = 'field-stack';
                        const lbl = document.createElement('span');
                        lbl.className = 'field-label';
                        lbl.textContent = label;

                        const row = document.createElement('div');
                        row.className = 'color-picker-row';

                        const colorInput = document.createElement('input');
                        colorInput.type = 'color';
                        colorInput.value = parsed.hex;

                        const hexReadout = document.createElement('input');
                        hexReadout.type = 'text';
                        hexReadout.className = 's-input';
                        hexReadout.value = parsed.hex.toUpperCase();
                        hexReadout.style.fontFamily = 'ui-monospace, SFMono-Regular, Menlo, monospace';

                        const alphaInput = document.createElement('input');
                        alphaInput.type = 'number';
                        alphaInput.className = 's-input';
                        alphaInput.min = '0';
                        alphaInput.max = '100';
                        alphaInput.step = '5';
                        alphaInput.style.inlineSize = '4.75rem';
                        alphaInput.title = 'Opacity (%)';
                        alphaInput.value = String(parsed.alphaPct);

                        const syncColor = (hex) => {
                            const nextInt = hexAlphaToObsColor(hex, alphaInput.value);
                            visualDraftSettings[key] = nextInt;
                            colorInput.value = hex;
                            hexReadout.value = hex.toUpperCase();
                            commitDraft();
                        };

                        colorInput.addEventListener('input', () => syncColor(colorInput.value));
                        alphaInput.addEventListener('input', () => syncColor(colorInput.value));
                        hexReadout.addEventListener('change', () => {
                            const val = hexReadout.value.trim();
                            if (/^#?[0-9a-f]{6}$/i.test(val)) {
                                syncColor(val.startsWith('#') ? val : `#${val}`);
                            }
                        });

                        row.append(colorInput, hexReadout, alphaInput);
                        wrap.append(lbl, row);
                        container.appendChild(wrap);
                    };

                    if (kind.includes('color_source')) {
                        appendColorField({ key: 'color', label: 'Fill Color & Opacity (%)', initialInt: sourceSettings.color ?? 4291939689 });
                        appendTextField({ key: 'width', label: 'Width (px)', value: sourceSettings.width ?? dims.width, type: 'number', onChange: (v) => { visualDraftSettings.width = Math.max(1, v); } });
                        appendTextField({ key: 'height', label: 'Height (px)', value: sourceSettings.height ?? dims.height, type: 'number', onChange: (v) => { visualDraftSettings.height = Math.max(1, v); } });
                    } else if (kind.includes('browser')) {
                        appendTextField({ key: 'url', label: 'Web Page URL', value: sourceSettings.url ?? '', placeholder: 'https://ob/screens/...', onChange: (v) => { visualDraftSettings.url = v; } });
                        appendTextField({ key: 'width', label: 'Page Width (px)', value: sourceSettings.width ?? dims.width, type: 'number', onChange: (v) => { visualDraftSettings.width = Math.max(1, v); } });
                        appendTextField({ key: 'height', label: 'Page Height (px)', value: sourceSettings.height ?? dims.height, type: 'number', onChange: (v) => { visualDraftSettings.height = Math.max(1, v); } });
                        appendTextField({ key: 'fps', label: 'Frame Rate (FPS)', value: sourceSettings.fps ?? 60, type: 'number', onChange: (v) => { visualDraftSettings.fps = Math.max(1, v); } });
                        appendTextField({ key: 'css', label: 'Custom CSS', value: sourceSettings.css ?? '', multiline: true, onChange: (v) => { visualDraftSettings.css = v; } });
                        appendCheckField({ key: 'shutdown', label: 'Shutdown source when hidden', checked: Boolean(sourceSettings.shutdown), onChange: (v) => { visualDraftSettings.shutdown = v; } });
                        appendCheckField({ key: 'restart_when_active', label: 'Refresh browser when scene activates', checked: Boolean(sourceSettings.restart_when_active), onChange: (v) => { visualDraftSettings.restart_when_active = v; } });
                    } else if (kind.includes('image')) {
                        container.appendChild(buildImageUploadWidget({
                            fileInputId: 'prop-image-upload-input',
                            onPathResolved: async (savedPath) => {
                                const pathInputEl = container.querySelector('[data-prop-key="file"]');
                                if (pathInputEl) pathInputEl.value = savedPath;
                                visualDraftSettings.file = savedPath;
                                commitDraft();
                                const itemNow = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                                if (itemNow?.sourceName) {
                                    await sendObsCommand('SetInputSettings', {
                                        inputName: itemNow.sourceName,
                                        inputSettings: { ...visualDraftSettings },
                                        overlay: true,
                                    });
                                }
                            },
                        }));
                        appendTextField({ key: 'file', label: 'Image File Path', value: sourceSettings.file ?? '', placeholder: '/path/to/image.png', onChange: (v) => { visualDraftSettings.file = v; } });
                        appendCheckField({ key: 'unload', label: 'Unload image when hidden', checked: Boolean(sourceSettings.unload), onChange: (v) => { visualDraftSettings.unload = v; } });
                    } else if (kind.includes('ffmpeg')) {
                        appendCheckField({ key: 'is_local_file', label: 'Local File on OBS Computer', checked: sourceSettings.is_local_file !== false, onChange: (v) => { visualDraftSettings.is_local_file = v; } });
                        appendTextField({ key: 'local_file', label: 'Media File Path', value: sourceSettings.local_file ?? '', placeholder: '/path/to/video.mp4', onChange: (v) => { visualDraftSettings.local_file = v; } });
                        if ('input' in sourceSettings) {
                            appendTextField({ key: 'input', label: 'Stream / Network URL', value: sourceSettings.input ?? '', onChange: (v) => { visualDraftSettings.input = v; } });
                        }
                        appendCheckField({ key: 'looping', label: 'Loop Playback', checked: Boolean(sourceSettings.looping), onChange: (v) => { visualDraftSettings.looping = v; } });
                        appendCheckField({ key: 'restart_on_activate', label: 'Restart playback when scene becomes active', checked: sourceSettings.restart_on_activate !== false, onChange: (v) => { visualDraftSettings.restart_on_activate = v; } });
                        appendCheckField({ key: 'clear_on_media_end', label: 'Hide source when finished', checked: Boolean(sourceSettings.clear_on_media_end), onChange: (v) => { visualDraftSettings.clear_on_media_end = v; } });
                        appendTextField({ key: 'speed_percent', label: 'Playback Speed (%)', value: sourceSettings.speed_percent ?? 100, type: 'number', onChange: (v) => { visualDraftSettings.speed_percent = Math.max(1, Math.min(200, v)); } });
                    } else if (kind.includes('text')) {
                        appendTextField({ key: 'text', label: 'Display Text', value: sourceSettings.text ?? '', multiline: true, onChange: (v) => { visualDraftSettings.text = v; } });
                        if ('color1' in sourceSettings || kind.includes('ft2')) {
                            appendColorField({ key: 'color1', label: 'Text Color & Opacity (%)', initialInt: sourceSettings.color1 ?? 4294967295 });
                        } else {
                            appendColorField({ key: 'color', label: 'Text Color & Opacity (%)', initialInt: sourceSettings.color ?? 4294967295 });
                        }
                        if (sourceSettings.font && typeof sourceSettings.font === 'object') {
                            handledKeys.add('font');
                            appendTextField({
                                key: 'font_size_virtual',
                                label: 'Font Size (pt)',
                                value: sourceSettings.font.size ?? 48,
                                type: 'number',
                                onChange: (v) => {
                                    visualDraftSettings.font = { ...(visualDraftSettings.font || sourceSettings.font), size: Math.max(8, v) };
                                },
                            });
                        }
                        if ('outline' in sourceSettings) {
                            appendCheckField({ key: 'outline', label: 'Text Outline', checked: Boolean(sourceSettings.outline), onChange: (v) => { visualDraftSettings.outline = v; } });
                        }
                    }

                    Object.entries(sourceSettings).forEach(([key, val]) => {
                        if (handledKeys.has(key)) return;
                        if (typeof val === 'boolean') {
                            appendCheckField({ key, label: humanizeFieldKey(key), checked: val, onChange: (v) => { visualDraftSettings[key] = v; } });
                        } else if (typeof val === 'number') {
                            if (key.toLowerCase().includes('color') && val > 65535) {
                                appendColorField({ key, label: `${humanizeFieldKey(key)} & Opacity (%)`, initialInt: val });
                            } else {
                                appendTextField({ key, label: humanizeFieldKey(key), value: val, type: 'number', onChange: (v) => { visualDraftSettings[key] = v; } });
                            }
                        } else if (typeof val === 'string') {
                            appendTextField({ key, label: humanizeFieldKey(key), value: val, multiline: val.includes('\n'), onChange: (v) => { visualDraftSettings[key] = v; } });
                        }
                    });

                    if (container.children.length === 0) {
                        const note = document.createElement('div');
                        note.className = 'dock-subtitle';
                        note.textContent = 'This source uses OBS defaults. Device or window selection dialogs configured on desktop OBS appear here when set.';
                        container.appendChild(note);
                    }
                };

                const loadSelectedSourceSettings = async (item, force = false) => {
                    if (!item?.sourceName) return;
                    const key = `${editingScene}::${item.sceneItemId}::${item.sourceName}`;
                    if (!force && loadedSettingsKey === key) return;

                    loadedSettingsKey = key;
                    const seq = ++settingsLoadSeq;
                    const statusEl = document.getElementById('settings-load-status');
                    if (statusEl) statusEl.textContent = 'Loading live properties from OBS...';

                    try {
                        const res = await postJson(endpoints.command, {
                            request_type: 'obsisstant.FetchInputSettings',
                            request_data: { inputName: item.sourceName },
                        });

                        const settled = await waitForCommandResult(res.command_uuid, 4000);
                        if (seq !== settingsLoadSeq) return;

                        if (settled?.status === 'succeeded') {
                            const payload = settled.response_data || {};
                            renderVisualSettingsEditor(payload.inputKind || item.inputKind || '', payload.inputSettings || {});
                            if (statusEl) statusEl.textContent = 'Live properties synced from OBS';
                        } else {
                            renderVisualSettingsEditor(item.inputKind || '', currentLoadedSettings);
                            if (statusEl) {
                                statusEl.textContent = settled?.error_message
                                    ? `OBS error: ${settled.error_message}`
                                    : 'Showing cached defaults (OBS did not reply yet)';
                            }
                        }
                    } catch {
                        if (seq !== settingsLoadSeq) return;
                        renderVisualSettingsEditor(item.inputKind || '', {});
                        if (statusEl) statusEl.textContent = 'Could not reach server to fetch properties';
                    }
                };

                const renderStageLayers = () => {
                    if (isDraggingStage) return;

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
                            isDraggingStage = true;

                            box.setPointerCapture(ev.pointerId);

                            const onMove = (moveEv) => {
                                const dxPx = moveEv.clientX - startClientX;
                                const dyPx = moveEv.clientY - startClientY;
                                const dxObs = (dxPx / canvasRect.width) * rect.baseW;
                                const dyObs = (dyPx / canvasRect.height) * rect.baseH;

                                if (isResize) {
                                    const nextW = Math.max(40, Math.round(startTf.w + dxObs));
                                    const nextH = Math.max(24, Math.round(startTf.h + dyObs));
                                    box.style.width = `${Math.min(100, (nextW / rect.baseW) * 100)}%`;
                                    box.style.height = `${Math.min(100, (nextH / rect.baseH) * 100)}%`;
                                    document.getElementById('tf-w').value = String(nextW);
                                    document.getElementById('tf-h').value = String(nextH);
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

                            const finishDrag = () => {
                                isDraggingStage = false;
                                box.removeEventListener('pointermove', onMove);
                                box.removeEventListener('pointerup', onUp);
                                box.removeEventListener('pointercancel', finishDrag);
                            };

                            const onUp = (upEv) => {
                                finishDrag();

                                const dxObs = ((upEv.clientX - startClientX) / canvasRect.width) * rect.baseW;
                                const dyObs = ((upEv.clientY - startClientY) / canvasRect.height) * rect.baseH;
                                if (Math.abs(dxObs) < 2 && Math.abs(dyObs) < 2) return;

                                let patch = {};
                                if (isResize) {
                                    const nextW = Math.max(40, startTf.w + dxObs);
                                    const nextH = Math.max(24, startTf.h + dyObs);
                                    patch = buildDimensionTransformPatch(item, nextW, nextH);
                                } else {
                                    patch = {
                                        positionX: Math.round(startTf.posX + dxObs),
                                        positionY: Math.round(startTf.posY + dyObs),
                                    };
                                }

                                commitSceneItemTransform(itemId, patch);
                            };

                            box.addEventListener('pointermove', onMove);
                            box.addEventListener('pointerup', onUp);
                            box.addEventListener('pointercancel', finishDrag);
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
                        loadedSettingsKey = null;
                        if (selCard) selCard.hidden = true;
                        return;
                    }

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
                        upBtn.disabled = idx >= items.length - 1;
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
                        downBtn.disabled = idx <= 0;
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
                        rmBtn.addEventListener('click', async () => {
                            rmBtn.disabled = true;
                            if (selectedSceneItemId === itemId) selectedSceneItemId = null;
                            await sendObsCommand('RemoveSceneItem', {
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
                        loadedSettingsKey = null;
                        if (selCard) selCard.hidden = true;
                        return;
                    }

                    selCard.hidden = false;
                    document.getElementById('sel-source-name').textContent = selectedItem.sourceName || '';
                    document.getElementById('sel-source-kind').textContent = formatInputKindLabel(selectedItem.inputKind);

                    const renameInput = document.getElementById('rename-source-input');
                    if (renameInput && document.activeElement !== renameInput) {
                        renameInput.value = selectedItem.sourceName || '';
                    }

                    const tf = selectedItem.sceneItemTransform || {};
                    const rect = computeItemRect(selectedItem);
                    const setTransformFieldIfIdle = (id, val) => {
                        const field = document.getElementById(id);
                        if (field && !isDraggingStage && document.activeElement !== field) {
                            field.value = String(val);
                        }
                    };
                    setTransformFieldIfIdle('tf-x', Math.round(Number(tf.positionX ?? 0)));
                    setTransformFieldIfIdle('tf-y', Math.round(Number(tf.positionY ?? 0)));
                    setTransformFieldIfIdle('tf-w', Math.round(rect.w));
                    setTransformFieldIfIdle('tf-h', Math.round(rect.h));
                    setTransformFieldIfIdle('tf-rot', Math.round(Number(tf.rotation ?? 0)));

                    loadSelectedSourceSettings(selectedItem);
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

                const renderAddSourceFields = (kind) => {
                    const container = document.getElementById('add-source-dynamic-fields');
                    if (!container) return;

                    container.innerHTML = '';
                    const dims = getVideoDims();
                    const k = String(kind || '');

                    if (k.includes('color_source')) {
                        container.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Block Color</span>
                                <div class="color-picker-row">
                                    <input type="color" id="add-src-color" value="#cdad69">
                                    <input type="text" id="add-src-color-hex" class="s-input" value="#CDAD69" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                                    <input type="number" id="add-src-alpha" class="s-input" min="0" max="100" step="5" value="100" title="Opacity (%)" style="inline-size: 4.75rem;">
                                </div>
                            </label>
                            <div class="transform-grid">
                                <label class="field-stack">
                                    <span class="field-label">Width (px)</span>
                                    <input type="number" id="add-src-w" class="s-input" min="16" value="${dims.width}">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Height (px)</span>
                                    <input type="number" id="add-src-h" class="s-input" min="16" value="${dims.height}">
                                </label>
                            </div>
                        `;
                        const colorEl = document.getElementById('add-src-color');
                        const hexEl = document.getElementById('add-src-color-hex');
                        colorEl?.addEventListener('input', () => {
                            if (hexEl) hexEl.value = colorEl.value.toUpperCase();
                        });
                        hexEl?.addEventListener('change', () => {
                            const val = hexEl.value.trim();
                            if (/^#?[0-9a-f]{6}$/i.test(val) && colorEl) {
                                colorEl.value = val.startsWith('#') ? val : `#${val}`;
                            }
                        });
                        return;
                    }

                    if (k.includes('browser')) {
                        container.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Web Page URL</span>
                                <input type="text" id="add-src-url" class="s-input" placeholder="https://ob/screens/... or any webpage URL">
                            </label>
                            <div class="transform-grid">
                                <label class="field-stack">
                                    <span class="field-label">Width (px)</span>
                                    <input type="number" id="add-src-w" class="s-input" min="16" value="${dims.width}">
                                </label>
                                <label class="field-stack">
                                    <span class="field-label">Height (px)</span>
                                    <input type="number" id="add-src-h" class="s-input" min="16" value="${dims.height}">
                                </label>
                            </div>
                        `;
                        return;
                    }

                    if (k.includes('image')) {
                        container.appendChild(buildImageUploadWidget({
                            fileInputId: 'add-src-image-upload',
                            onPathResolved: async (savedPath, file) => {
                                const fileInputEl = document.getElementById('add-src-file');
                                if (fileInputEl) fileInputEl.value = savedPath;
                                const nameInputEl = document.getElementById('add-source-name');
                                if (nameInputEl && !nameInputEl.value.trim() && file?.name) {
                                    nameInputEl.value = file.name.replace(/\.[^.]+$/, '');
                                }
                            },
                        }));
                        const wrap = document.createElement('div');
                        wrap.className = 'stack-grid';
                        wrap.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Or Existing Image File Path</span>
                                <input type="text" id="add-src-file" class="s-input" placeholder="/Users/you/Pictures/overlay.png">
                            </label>
                            <label class="prop-check">
                                <span>Unload image when hidden</span>
                                <input type="checkbox" id="add-src-unload">
                            </label>
                        `;
                        container.appendChild(wrap);
                        return;
                    }

                    if (k.includes('ffmpeg') || k.includes('vlc')) {
                        container.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Video / Audio File Path (Optional)</span>
                                <input type="text" id="add-src-file" class="s-input" placeholder="/Users/you/Videos/intro.mp4">
                            </label>
                            <label class="prop-check">
                                <span>Loop Playback</span>
                                <input type="checkbox" id="add-src-loop" checked>
                            </label>
                        `;
                        return;
                    }

                    if (k.includes('text')) {
                        container.innerHTML = `
                            <label class="field-stack">
                                <span class="field-label">Display Text</span>
                                <textarea id="add-src-text" class="s-textarea" rows="3" placeholder="Enter overlay text..."></textarea>
                            </label>
                            <label class="field-stack">
                                <span class="field-label">Text Color</span>
                                <div class="color-picker-row">
                                    <input type="color" id="add-src-color" value="#faf7ee">
                                    <input type="text" class="s-input" value="#FAF7EE" readonly style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                                    <input type="number" id="add-src-alpha" class="s-input" min="0" max="100" step="5" value="100" title="Opacity (%)" style="inline-size: 4.75rem;">
                                </div>
                            </label>
                        `;
                        return;
                    }

                    container.innerHTML = '<div class="dock-subtitle">Ready to create in OBS — you can adjust device or capture settings in Source Properties right after adding.</div>';
                };

                const populateAddSourceOptions = () => {
                    const kindSelect = document.getElementById('add-source-kind');
                    const existingSelect = document.getElementById('add-source-existing');
                    if (kindSelect && kindSelect.options.length === 0) {
                        const rawKinds = Array.isArray(liveState?.inputKinds) && liveState.inputKinds.length > 0
                            ? [...liveState.inputKinds]
                            : ['browser_source', 'image_source', 'ffmpeg_source', 'color_source_v3', 'text_ft2_source_v2'];

                        rawKinds.sort((a, b) => {
                            const pa = INPUT_KIND_CATALOG[a]?.priority ?? 99;
                            const pb = INPUT_KIND_CATALOG[b]?.priority ?? 99;
                            if (pa !== pb) return pa - pb;
                            return formatInputKindLabel(a).localeCompare(formatInputKindLabel(b));
                        });

                        rawKinds.forEach((kind) => {
                            const opt = document.createElement('option');
                            opt.value = kind;
                            opt.textContent = formatInputKindLabel(kind);
                            kindSelect.appendChild(opt);
                        });
                        renderAddSourceFields(kindSelect.value);
                    }

                    if (existingSelect) {
                        const prev = existingSelect.value;
                        existingSelect.innerHTML = '';
                        const existingInputs = Array.isArray(liveState?.allInputs) ? liveState.allInputs : [];
                        if (existingInputs.length === 0) {
                            const opt = document.createElement('option');
                            opt.value = '';
                            opt.textContent = 'No existing OBS sources synced yet';
                            existingSelect.appendChild(opt);
                        } else {
                            existingInputs.forEach((inp) => {
                                const opt = document.createElement('option');
                                opt.value = inp.inputName;
                                opt.textContent = `${inp.inputName} (${formatInputKindLabel(inp.inputKind)})`;
                                existingSelect.appendChild(opt);
                            });
                        }
                        if (prev) existingSelect.value = prev;
                    }
                };

                document.getElementById('add-source-kind')?.addEventListener('change', (ev) => {
                    renderAddSourceFields(ev.target.value);
                });

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
                    loadedSettingsKey = null;
                    sendObsCommand('SetCurrentSceneCollection', { sceneCollectionName: collectionName });
                });

                document.getElementById('create-collection-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const input = document.getElementById('create-collection-name');
                    const name = input?.value?.trim();
                    if (!name) return;
                    editingScene = '';
                    selectedSceneItemId = null;
                    loadedSettingsKey = null;
                    const res = await sendObsCommand('CreateSceneCollection', { sceneCollectionName: name });
                    if (res?.settled?.status !== 'failed') {
                        input.value = '';
                        closeDialog(document.getElementById('create-collection-dialog'));
                    }
                });

                document.getElementById('create-scene-btn')?.addEventListener('click', async () => {
                    const input = document.getElementById('new-scene-name');
                    const sceneName = input?.value?.trim();
                    if (!sceneName) return;
                    const res = await sendObsCommand('CreateScene', { sceneName });
                    if (res?.settled?.status !== 'failed') {
                        editingScene = sceneName;
                        selectedSceneItemId = null;
                        input.value = '';
                        renderAll();
                    }
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
                        showToast(`Macro triggered: ${btn.textContent.trim()}`, 'success');
                        setTimeout(refreshStudioSnapshot, 450);
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

                // Selected Source Rename, Transform, Visual Settings & Delete Controls
                document.getElementById('btn-rename-source')?.addEventListener('click', async () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    const nextName = document.getElementById('rename-source-input')?.value?.trim();
                    if (!item?.sourceName || !nextName || nextName === item.sourceName) return;

                    loadedSettingsKey = null;
                    await sendObsCommand('SetInputName', {
                        inputName: item.sourceName,
                        newInputName: nextName,
                    });
                });

                document.getElementById('btn-apply-transform')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item || !editingScene) return;

                    const targetW = Math.max(16, Number(document.getElementById('tf-w').value || 100));
                    const targetH = Math.max(16, Number(document.getElementById('tf-h').value || 100));
                    const dimPatch = buildDimensionTransformPatch(item, targetW, targetH);

                    commitSceneItemTransform(selectedSceneItemId, {
                        positionX: Number(document.getElementById('tf-x').value || 0),
                        positionY: Number(document.getElementById('tf-y').value || 0),
                        rotation: Number(document.getElementById('tf-rot').value || 0),
                        ...dimPatch,
                    });
                });

                document.getElementById('btn-fit-source')?.addEventListener('click', () => {
                    if (!editingScene || selectedSceneItemId === null) return;
                    const dims = getVideoDims();
                    commitSceneItemTransform(selectedSceneItemId, {
                        positionX: 0,
                        positionY: 0,
                        rotation: 0,
                        boundsType: 'OBS_BOUNDS_SCALE_INNER',
                        boundsWidth: dims.width,
                        boundsHeight: dims.height,
                    });
                });

                document.getElementById('btn-center-source')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item || !editingScene) return;
                    const rect = computeItemRect(item);
                    const align = Number(item.sceneItemTransform?.alignment ?? 5);
                    const hAlign = align & 3;
                    const vAlign = align & 12;

                    const posX = hAlign === 0
                        ? Math.round(rect.baseW / 2)
                        : hAlign === 2
                            ? Math.round((rect.baseW + rect.w) / 2)
                            : Math.round((rect.baseW - rect.w) / 2);

                    const posY = vAlign === 0
                        ? Math.round(rect.baseH / 2)
                        : vAlign === 8
                            ? Math.round((rect.baseH + rect.h) / 2)
                            : Math.round((rect.baseH - rect.h) / 2);

                    commitSceneItemTransform(selectedSceneItemId, {
                        positionX: posX,
                        positionY: posY,
                    });
                });

                document.getElementById('source-settings-json')?.addEventListener('input', () => {
                    rawJsonEdited = true;
                });

                document.getElementById('btn-inspect-settings')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (item) loadSelectedSourceSettings(item, true);
                });

                document.getElementById('btn-refresh-browser')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item?.sourceName) return;
                    sendObsCommand('PressInputPropertiesButton', {
                        inputName: item.sourceName,
                        propertyName: 'refreshnocache',
                    });
                });

                document.getElementById('btn-save-settings')?.addEventListener('click', async () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item?.sourceName) return;

                    let payload = visualDraftSettings;
                    if (rawJsonEdited) {
                        const box = document.getElementById('source-settings-json');
                        try {
                            payload = JSON.parse(box?.value || '{}');
                        } catch {
                            showToast('Invalid JSON in Advanced Raw Settings', 'error');
                            return;
                        }
                    }

                    const res = await sendObsCommand('SetInputSettings', {
                        inputName: item.sourceName,
                        inputSettings: payload && typeof payload === 'object' ? payload : {},
                        overlay: true,
                    });

                    if (res?.settled?.status !== 'failed') {
                        loadSelectedSourceSettings(item, true);
                    }
                });

                document.getElementById('btn-remove-scene-item')?.addEventListener('click', async () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item || !editingScene) return;
                    const itemId = Number(item.sceneItemId);
                    selectedSceneItemId = null;
                    loadedSettingsKey = null;
                    await sendObsCommand('RemoveSceneItem', {
                        sceneName: editingScene,
                        sceneItemId: itemId,
                    });
                });

                document.getElementById('btn-delete-source-forever')?.addEventListener('click', () => {
                    const item = getItemsForEditingScene().find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!item?.sourceName) return;
                    pendingDeleteSourceName = item.sourceName;
                    document.getElementById('delete-source-copy').textContent = `Permanently delete OBS source "${item.sourceName}" across all scenes inside OBS Studio? This cannot be undone.`;
                    openDialog(document.getElementById('delete-source-dialog'));
                });

                document.getElementById('confirm-delete-source-btn')?.addEventListener('click', async () => {
                    if (!pendingDeleteSourceName) return;
                    const targetName = pendingDeleteSourceName;
                    pendingDeleteSourceName = null;
                    selectedSceneItemId = null;
                    loadedSettingsKey = null;
                    const res = await sendObsCommand('RemoveInput', { inputName: targetName });
                    if (res?.settled?.status !== 'failed') {
                        closeDialog(document.getElementById('delete-source-dialog'));
                    }
                });

                // Add Source / Rename / Delete Scene Dialogs
                document.getElementById('add-source-mode')?.addEventListener('change', (ev) => {
                    const isNew = ev.target.value === 'create';
                    document.getElementById('add-source-new-fields').hidden = !isNew;
                    document.getElementById('add-source-existing-fields').hidden = isNew;
                    document.getElementById('add-source-name').required = isNew;
                });

                document.getElementById('add-source-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    if (!editingScene) {
                        showToast('Select or create a scene first', 'error');
                        return;
                    }

                    const submitBtn = document.getElementById('add-source-submit-btn');
                    if (submitBtn) submitBtn.disabled = true;

                    try {
                        const mode = document.getElementById('add-source-mode').value;
                        if (mode === 'create') {
                            const kind = document.getElementById('add-source-kind').value;
                            const name = document.getElementById('add-source-name').value.trim();
                            if (!name) {
                                showToast('Please enter a source name', 'error');
                                return;
                            }

                            const settings = {};
                            if (kind.includes('color_source')) {
                                const hex = document.getElementById('add-src-color')?.value || '#cdad69';
                                const alpha = document.getElementById('add-src-alpha')?.value ?? 100;
                                settings.color = hexAlphaToObsColor(hex, alpha);
                                settings.width = Math.max(16, Number(document.getElementById('add-src-w')?.value || getVideoDims().width));
                                settings.height = Math.max(16, Number(document.getElementById('add-src-h')?.value || getVideoDims().height));
                            } else if (kind.includes('browser')) {
                                const url = document.getElementById('add-src-url')?.value?.trim();
                                if (url) settings.url = url;
                                settings.width = Math.max(16, Number(document.getElementById('add-src-w')?.value || getVideoDims().width));
                                settings.height = Math.max(16, Number(document.getElementById('add-src-h')?.value || getVideoDims().height));
                            } else if (kind.includes('image')) {
                                const file = document.getElementById('add-src-file')?.value?.trim();
                                if (file) settings.file = file;
                                settings.unload = Boolean(document.getElementById('add-src-unload')?.checked);
                            } else if (kind.includes('ffmpeg') || kind.includes('vlc')) {
                                const file = document.getElementById('add-src-file')?.value?.trim();
                                if (file) {
                                    settings.local_file = file;
                                    settings.is_local_file = true;
                                }
                                settings.looping = Boolean(document.getElementById('add-src-loop')?.checked);
                            } else if (kind.includes('text')) {
                                const txt = document.getElementById('add-src-text')?.value ?? '';
                                if (txt) settings.text = txt;
                                const hex = document.getElementById('add-src-color')?.value;
                                if (hex) {
                                    const alpha = document.getElementById('add-src-alpha')?.value ?? 100;
                                    const colorKey = kind.includes('ft2') ? 'color1' : 'color';
                                    settings[colorKey] = hexAlphaToObsColor(hex, alpha);
                                }
                            }

                            const res = await sendObsCommand('CreateInput', {
                                sceneName: editingScene,
                                inputName: name,
                                inputKind: kind,
                                inputSettings: settings,
                                sceneItemEnabled: true,
                            });

                            if (res?.settled?.status === 'failed') return;

                            if (res?.settled?.response_data?.sceneItemId) {
                                selectedSceneItemId = Number(res.settled.response_data.sceneItemId);
                                loadedSettingsKey = null;
                                renderAll();
                            }
                            document.getElementById('add-source-name').value = '';
                        } else {
                            const existingName = document.getElementById('add-source-existing').value;
                            if (!existingName) {
                                showToast('No existing source available to add', 'error');
                                return;
                            }
                            const res = await sendObsCommand('CreateSceneItem', {
                                sceneName: editingScene,
                                sourceName: existingName,
                                sceneItemEnabled: true,
                            });
                            if (res?.settled?.status === 'failed') return;
                            if (res?.settled?.response_data?.sceneItemId) {
                                selectedSceneItemId = Number(res.settled.response_data.sceneItemId);
                                loadedSettingsKey = null;
                                renderAll();
                            }
                        }

                        closeDialog(document.getElementById('add-source-dialog'));
                    } finally {
                        if (submitBtn) submitBtn.disabled = false;
                    }
                });

                document.getElementById('rename-scene-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const oldName = document.getElementById('rename-scene-original').value;
                    const newName = document.getElementById('rename-scene-new').value.trim();
                    if (!oldName || !newName) return;
                    const res = await sendObsCommand('SetSceneName', { sceneName: oldName, newSceneName: newName });
                    if (res?.settled?.status !== 'failed') {
                        if (editingScene === oldName) editingScene = newName;
                        closeDialog(document.getElementById('rename-scene-dialog'));
                        renderAll();
                    }
                });

                document.getElementById('confirm-delete-scene-btn')?.addEventListener('click', async () => {
                    if (!pendingDeleteScene) return;
                    const sceneToDelete = pendingDeleteScene;
                    const res = await sendObsCommand('RemoveScene', { sceneName: sceneToDelete });
                    if (res?.settled?.status !== 'failed') {
                        if (editingScene === sceneToDelete) editingScene = '';
                        pendingDeleteScene = null;
                        closeDialog(document.getElementById('delete-scene-dialog'));
                    }
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
                    showToast('Overlay pushed live via Reverb', 'success');
                };

                document.getElementById('btn-overlay-show')?.addEventListener('click', () => pushOverlayState(true));
                document.getElementById('btn-overlay-hide')?.addEventListener('click', () => pushOverlayState(false));
                document.getElementById('btn-push-overlay-scene')?.addEventListener('click', async () => {
                    const selId = Number(document.getElementById('overlay-screen-select')?.value);
                    if (!selId) return;
                    await postJson(`/studio/${studioSlug}/screens/${selId}/push`, {
                        scene_name: editingScene || null,
                    });
                    showToast('Pushing screen to OBS scene', 'success');
                    setTimeout(refreshStudioSnapshot, 650);
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

                const getSnapshotConfig = () => {
                    const mode = document.getElementById('live-view-mode')?.value;
                    if (mode === 'snapshot-fast') {
                        return { active: true, intervalMs: 1200, width: 768, label: 'OBS preview via WebSocket (1.2s · change-synced)' };
                    }
                    if (mode === 'snapshot') {
                        return { active: true, intervalMs: 2500, width: 640, label: 'OBS preview via WebSocket (2.5s · change-synced)' };
                    }
                    return { active: false, intervalMs: 2500, width: 640, label: 'Layout mode' };
                };

                const sendPreviewControl = (controlPayload, allowHttpFallback = false) => {
                    if (studioChannel?.subscription?.subscribed) {
                        try {
                            studioChannel.whisper('obs.preview.subscribe', controlPayload);
                            return Promise.resolve({ via: 'websocket' });
                        } catch {
                            // Fall through to HTTP toggle fallback when whisper fails
                        }
                    }

                    if (!allowHttpFallback) {
                        return Promise.resolve({ via: 'pending' });
                    }

                    return postJson(endpoints.previewToggle, {
                        active: Boolean(controlPayload.active),
                        scene_name: controlPayload.sceneName ?? null,
                        interval_ms: controlPayload.intervalMs ?? 2500,
                        width: controlPayload.width ?? 640,
                    }).catch(() => {});
                };

                const enableSnapshotPreview = async (active) => {
                    clearInterval(previewLeaseTimer);
                    clearInterval(previewFallbackTimer);
                    clearTimeout(previewControlFallbackTimer);

                    if (!active || document.hidden) {
                        if (!active && previewImg) previewImg.hidden = true;
                        await sendPreviewControl({ active: false }, !studioChannel?.subscription?.subscribed);
                        return;
                    }

                    const cfg = getSnapshotConfig();
                    if (!cfg.active) return;
                    if (previewStatusLabel) previewStatusLabel.textContent = cfg.label;

                    const controlPayload = {
                        active: true,
                        sceneName: editingScene || null,
                        intervalMs: cfg.intervalMs,
                        width: cfg.width,
                    };

                    if (studioChannel?.subscription?.subscribed) {
                        await sendPreviewControl(controlPayload, false);
                    } else if (!window.Echo) {
                        await sendPreviewControl(controlPayload, true);
                    } else {
                        // Wait briefly for Echo channel auth to complete, only using HTTP if WebSocket never joined
                        previewControlFallbackTimer = setTimeout(() => {
                            if (!studioChannel?.subscription?.subscribed && !document.hidden && isSnapshotMode()) {
                                sendPreviewControl(controlPayload, true);
                            }
                        }, 1600);
                    }

                    if (previewImg) {
                        previewImg.onerror = () => { previewImg.hidden = true; };
                        previewImg.onload = () => { previewImg.hidden = false; };
                        if (!window.Echo) {
                            previewImg.src = `${endpoints.previewFrame}?t=${Date.now()}`;
                        }
                    }

                    previewLeaseTimer = setInterval(() => {
                        if (!document.hidden && isSnapshotMode()) {
                            sendPreviewControl({
                                active: true,
                                sceneName: editingScene || null,
                                intervalMs: cfg.intervalMs,
                                width: cfg.width,
                            }, !studioChannel?.subscription?.subscribed);
                        }
                    }, 25000);

                    if (!window.Echo) {
                        previewFallbackTimer = setInterval(() => {
                            if (!document.hidden && isSnapshotMode()) refreshStudioSnapshot();
                        }, Math.max(3000, cfg.intervalMs));
                    }
                };

                document.getElementById('live-view-mode')?.addEventListener('change', async (ev) => {
                    const mode = ev.target.value;
                    stopVirtualCam();
                    await enableSnapshotPreview(false);

                    if (mode === 'snapshot' || mode === 'snapshot-fast') {
                        await enableSnapshotPreview(true);
                    } else if (mode === 'vcam') {
                        if (!navigator.mediaDevices?.getUserMedia) {
                            showToast('WebRTC getUserMedia not supported in this browser', 'error');
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
                            showToast('Camera / Virtual Cam permission denied', 'error');
                            ev.target.value = 'layout';
                            if (previewStatusLabel) previewStatusLabel.textContent = 'Layout mode';
                        }
                    } else if (previewStatusLabel) {
                        previewStatusLabel.textContent = 'Layout mode';
                    }
                });

                document.addEventListener('visibilitychange', () => {
                    if (!isSnapshotMode()) return;
                    if (document.hidden) {
                        clearInterval(previewLeaseTimer);
                        clearInterval(previewFallbackTimer);
                        clearTimeout(previewControlFallbackTimer);
                        sendPreviewControl({ active: false }, !studioChannel?.subscription?.subscribed);
                    } else {
                        enableSnapshotPreview(true);
                    }
                });

                window.addEventListener('beforeunload', () => {
                    stopVirtualCam();
                    clearInterval(previewLeaseTimer);
                    clearInterval(previewFallbackTimer);
                    clearTimeout(previewControlFallbackTimer);
                });

                const applyIncomingPreviewFrame = (payload) => {
                    lastEchoPreviewAt = Date.now();
                    if (payload?.version) lastSeenPreviewVersion = payload.version;
                    if (isSnapshotMode() && previewImg) {
                        previewImg.src = payload?.image_data
                            ? payload.image_data
                            : `${endpoints.previewFrame}?v=${encodeURIComponent(payload?.version || Date.now())}`;
                        previewImg.hidden = false;
                    }
                };

                const installDialog = document.getElementById('install-agent-dialog');
                const installBanner = document.getElementById('agent-offline-banner');
                const installModalText = document.getElementById('install-modal-live-text');
                const installBannerTitle = document.getElementById('agent-banner-title');
                const installBannerCopy = document.getElementById('agent-banner-copy');
                const installBannerCta = document.getElementById('agent-banner-cta');
                const topbarInstallBtn = document.getElementById('open-install-agent-btn');
                let installPollTimer = null;
                let wasStudioOnline = studioStatus !== 'offline';

                function updateAgentOnboardingState() {
                    const isOnline = studioStatus !== 'offline';
                    if (installBanner) {
                        installBanner.hidden = isOnline;
                    }
                    if (topbarInstallBtn) {
                        topbarInstallBtn.textContent = isOnline ? 'Add PC' : 'Install Agent';
                    }

                    if (!isOnline) {
                        if (installBannerTitle) {
                            installBannerTitle.textContent = agentReachable
                                ? 'OBS Agent connected — open OBS Studio'
                                : 'Connect OBS Studio on your Mac or Windows PC';
                        }
                        if (installBannerCopy) {
                            installBannerCopy.textContent = agentReachable
                                ? 'In OBS Studio, open Tools -> WebSocket Server Settings and enable WebSocket server (Port 4455).'
                                : 'Install the pre-configured OBS Agent in one click — no terminal coding or token setup needed.';
                        }
                        if (installBannerCta) {
                            installBannerCta.textContent = agentReachable ? 'Install on Another PC' : 'Install OBS Agent';
                        }
                        if (installModalText) {
                            installModalText.textContent = agentReachable
                                ? 'OBS Agent is running on your PC! Open OBS -> Tools -> WebSocket Server Settings to finish.'
                                : 'Waiting for OBS Agent connection...';
                        }
                    } else if (installModalText) {
                        installModalText.textContent = 'OBS Studio is Online and synced!';
                    }

                    if (!wasStudioOnline && isOnline) {
                        clearInterval(installPollTimer);
                        installPollTimer = null;
                        if (installDialog?.open) {
                            closeDialog(installDialog);
                            showToast('OBS Studio connected! Syncing your scenes...', 'success');
                        }
                    }
                    wasStudioOnline = isOnline;
                }

                const selectInstallerOs = (os) => {
                    const normalized = os === 'windows' ? 'windows' : 'mac';
                    document.querySelectorAll('[data-agent-os]').forEach((tab) => {
                        tab.setAttribute('aria-selected', tab.getAttribute('data-agent-os') === normalized ? 'true' : 'false');
                    });
                    const macPanel = document.getElementById('agent-panel-mac');
                    const winPanel = document.getElementById('agent-panel-windows');
                    if (macPanel) macPanel.hidden = normalized !== 'mac';
                    if (winPanel) winPanel.hidden = normalized !== 'windows';
                };

                const detectedOs = /Win/i.test(navigator.platform || navigator.userAgent || '') ? 'windows' : 'mac';
                selectInstallerOs(detectedOs);

                document.querySelectorAll('[data-agent-os]').forEach((tab) => {
                    tab.addEventListener('click', () => {
                        selectInstallerOs(tab.getAttribute('data-agent-os') || 'mac');
                    });
                });

                const applyInstallerPayload = (payload) => {
                    if (!payload) return;
                    const macBtn = document.getElementById('download-mac-installer-btn');
                    const winBtn = document.getElementById('download-win-installer-btn');
                    const macCmd = document.getElementById('mac-oneclick-cmd');
                    const winCmd = document.getElementById('win-oneclick-cmd');
                    if (macBtn && payload.mac_download_url) macBtn.href = payload.mac_download_url;
                    if (winBtn && payload.windows_download_url) winBtn.href = payload.windows_download_url;
                    if (macCmd && payload.mac_command) macCmd.value = payload.mac_command;
                    if (winCmd && payload.windows_command) winCmd.value = payload.windows_command;
                };

                document.getElementById('install-studio-only-toggle')?.addEventListener('change', async (ev) => {
                    const scoped = Boolean(ev.target.checked);
                    if (!scoped) {
                        applyInstallerPayload(initialInstallerPayload);
                        return;
                    }
                    try {
                        const fresh = await postJson(endpoints.bootstrapToken, { studio: studioSlug });
                        applyInstallerPayload(fresh);
                    } catch {
                        showToast('Could not generate studio-scoped installer link', 'error');
                    }
                });

                const copyCommandInput = async (inputId, label) => {
                    const input = document.getElementById(inputId);
                    if (!input?.value) return;
                    try {
                        await navigator.clipboard.writeText(input.value);
                        showToast(`${label} copied — paste it into your streaming PC!`, 'success');
                    } catch {
                        input.select();
                        document.execCommand('copy');
                        showToast(`${label} selected — press Copy`, 'success');
                    }
                };

                document.getElementById('copy-mac-cmd-btn')?.addEventListener('click', () => copyCommandInput('mac-oneclick-cmd', 'Mac setup command'));
                document.getElementById('copy-win-cmd-btn')?.addEventListener('click', () => copyCommandInput('win-oneclick-cmd', 'Windows setup command'));
                document.getElementById('install-check-status-btn')?.addEventListener('click', () => refreshStudioSnapshot());

                const markInstallDismissed = () => {
                    try {
                        sessionStorage.setItem(`obs_install_dismissed_${studioSlug}`, '1');
                    } catch {
                        // Ignore storage quota / privacy mode errors
                    }
                    clearInterval(installPollTimer);
                    installPollTimer = null;
                };

                document.getElementById('install-agent-dismiss-btn')?.addEventListener('click', markInstallDismissed);
                document.getElementById('install-agent-close-x')?.addEventListener('click', markInstallDismissed);
                installDialog?.addEventListener('close', markInstallDismissed);

                const startInstallDialogPolling = () => {
                    clearInterval(installPollTimer);
                    installPollTimer = setInterval(() => {
                        if (installDialog?.open && studioStatus === 'offline') {
                            refreshStudioSnapshot();
                        } else if (!installDialog?.open) {
                            clearInterval(installPollTimer);
                            installPollTimer = null;
                        }
                    }, 4000);
                };

                topbarInstallBtn?.addEventListener('click', startInstallDialogPolling);
                installBannerCta?.addEventListener('click', startInstallDialogPolling);

                // Real-time Reverb Echo + Pure WebSocket Whisper Sync
                const echoCfg = @json($echoConfig);
                if (window.EchoFactory && echoCfg?.key) {
                    window.Echo = new window.EchoFactory(echoCfg);
                    studioChannel = window.Echo.private(`obs.studio.${studioId}`)
                        .subscribed(() => {
                            if (isSnapshotMode() && !document.hidden) {
                                enableSnapshotPreview(true);
                            }
                        })
                        .listen('.obs.studio.state', (payload) => {
                            lastEchoStateAt = Date.now();
                            if (payload.status) {
                                studioStatus = payload.status;
                                agentReachable = true;
                            }
                            if (payload.live_state) applyIncomingLiveState(payload.live_state);
                            renderAll();
                        })
                        .listenForWhisper('obs.preview.frame', applyIncomingPreviewFrame)
                        .listen('.obs.preview.frame', applyIncomingPreviewFrame)
                        .listen('.obs.command.acknowledged', (payload) => {
                            const resolver = pendingCommandResolvers.get(payload.command_uuid);
                            if (resolver) {
                                resolver(payload);
                            }
                            if (payload.status === 'failed' && payload.error_message) {
                                showToast(`OBS Error: ${payload.error_message}`, 'error');
                            }
                            if (
                                payload.request_type !== 'obsisstant.FetchInputSettings'
                                && (Date.now() - lastEchoStateAt) > 650
                            ) {
                                scheduleSnapshotRefresh(400);
                            }
                        });
                }

                renderAll();

                // Prompt user to install OBS Agent automatically once per session if never connected
                let alreadyDismissed = false;
                try {
                    alreadyDismissed = sessionStorage.getItem(`obs_install_dismissed_${studioSlug}`) === '1';
                } catch {
                    alreadyDismissed = false;
                }
                if (studioStatus === 'offline' && !agentReachable && !alreadyDismissed && installDialog) {
                    openDialog(installDialog);
                    startInstallDialogPolling();
                }

                // Auto-start Balanced OBS WebSocket Preview when Studio is online
                if (isSnapshotMode() && !document.hidden) {
                    enableSnapshotPreview(true);
                }
            })();
        </script>
    </body>
</html>
