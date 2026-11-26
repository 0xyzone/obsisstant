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

            .visually-hidden:where(:not(:focus-within, :active)) {
                position: absolute !important;
                clip-path: inset(50%) !important;
                overflow: hidden !important;
                inline-size: 1px !important;
                block-size: 1px !important;
                margin: -1px !important;
                padding: 0 !important;
                border: 0 !important;
                white-space: nowrap !important;
            }

            /* Fullscreen Broadcast Console Shell & 2-Column Workstation */
            .studio-shell {
                padding: 0.75rem clamp(0.75rem, 1.8vw, 1.5rem) 1.75rem;
                display: grid;
                gap: 0.75rem;
                max-inline-size: 120rem;
                margin-inline: auto;
            }

            .broadcast-strip {
                background: linear-gradient(180deg, #141411 0%, #0F0F0D 100%);
                border: 1px solid var(--hairline);
                border-radius: 0.75rem;
                padding: 0.5rem 0.875rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.625rem 1rem;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
            }

            .strip-group {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.375rem;
            }

            .strip-label {
                font-size: 0.6875rem;
                font-weight: 600;
                color: var(--ink-muted);
                margin-inline-end: 0.25rem;
                white-space: nowrap;
            }

            .workspace-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                align-items: start;
            }

            @media (min-width: 68rem) {
                .workspace-grid {
                    grid-template-columns: 20rem minmax(0, 1fr);
                }
            }

            .studio-column {
                display: grid;
                gap: 0.875rem;
                min-inline-size: 0;
            }

            .main-workstation {
                display: grid;
                gap: 0.875rem;
                min-inline-size: 0;
            }

            .lower-console-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                align-items: stretch;
            }

            @media (min-width: 60rem) {
                .lower-console-grid {
                    grid-template-columns: minmax(0, 1.45fr) minmax(19.5rem, 1fr);
                }
            }

            .dock-panel {
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.75rem;
                align-content: start;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
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

            /* Center Broadcast Sources Manager & Editor */
            .sources-workspace-panel {
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.875rem;
                min-inline-size: 0;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.035);
            }

            .sources-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-block-end: 0.625rem;
                border-block-end: 1px solid var(--hairline);
            }

            .sources-split-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.875rem;
                align-items: start;
            }

            @media (min-width: 54rem) {
                .sources-split-grid {
                    grid-template-columns: minmax(17.5rem, 0.88fr) minmax(22rem, 1.12fr);
                }
            }

            .source-inspector-card {
                background-color: var(--bg-chrome);
                border: 1px solid var(--hairline);
                border-radius: 0.75rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.75rem;
                align-content: start;
            }

            .source-empty-state {
                border: 1px dashed var(--hairline-strong);
                border-radius: 0.75rem;
                padding: 2rem 1.25rem;
                text-align: center;
                color: var(--ink-muted);
                font-size: 0.8125rem;
                background: rgba(255, 255, 255, 0.015);
                display: grid;
                place-content: center;
                gap: 0.375rem;
                min-block-size: 14rem;
            }

            .source-inspector-card[hidden],
            .source-empty-state[hidden] {
                display: none;
            }

            /* Dialog OS Selector Tabs & Layer Stack */
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

            .layer-list {
                display: grid;
                gap: 0.5rem;
                max-block-size: 34rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
            }

            .layer-row {
                display: grid;
                gap: 0.375rem;
                padding: 0.5rem;
                border-radius: 0.5rem;
                background-color: var(--bg-raised);
                border: 1px solid transparent;
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out);
            }

            .layer-row.is-selected {
                border-color: var(--gold-accent);
                background-color: rgba(205, 173, 105, 0.12);
            }

            .layer-row-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                min-inline-size: 0;
            }

            .layer-row-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.25rem;
            }

            .layer-kind-tag {
                flex-shrink: 0;
                padding: 0.0625rem 0.375rem;
                border-radius: 0.25rem;
                background-color: var(--bg-chrome);
                border: 1px solid var(--hairline);
                color: var(--ink-muted);
                font-size: 0.625rem;
                font-weight: 500;
                max-inline-size: 9rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
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
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                max-block-size: 13.75rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
                align-content: start;
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
                .sources-workspace-panel,
                .source-inspector-card,
                .scene-row,
                .layer-row,
                .audio-item,
                dialog.obs-dialog,
                .obs-reverb-pill,
                .obs-brand-version-pill,
                .view-pad-btn {
                    background: Canvas;
                    color: CanvasText;
                    border: 1px solid CanvasText;
                }
            }

            :root {
                --hairline: rgba(255, 255, 255, 0.085);
                --hairline-strong: rgba(205, 173, 105, 0.28);
            }

            .s-btn {
                white-space: nowrap;
                flex-shrink: 0;
            }

            .scene-list {
                max-block-size: calc(100dvh - 18.5rem);
                min-block-size: 20rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
                padding: 0.125rem 0.25rem 0.125rem 0;
                align-content: start;
            }

            .audio-mixer-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(13.25rem, 1fr));
                gap: 0.5rem;
                align-content: start;
                max-block-size: 16.5rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
                padding: 0.125rem 0.25rem 0.125rem 0;
            }

            .audio-mixer-grid .audio-item {
                padding: 0.5rem 0.65rem;
                border-radius: 0.55rem;
                background-color: var(--bg-chrome);
                border: 1px solid var(--hairline);
                gap: 0.325rem;
            }

            .logs-scroll {
                display: grid;
                gap: 0.3rem;
                font-size: 0.75rem;
                max-block-size: 15.5rem;
                overflow-y: auto;
                scrollbar-gutter: stable;
                overscroll-behavior: contain;
                align-content: start;
            }

            .utility-tab-bar {
                display: inline-flex;
                align-items: center;
                gap: 0.2rem;
                padding: 0.2rem;
                border-radius: 0.5rem;
                background: var(--bg-chrome);
                border: 1px solid var(--hairline);
            }

            .stage-meta-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.4rem;
            }

            .stage-meta-item {
                padding: 0.45rem 0.55rem;
                border-radius: 0.5rem;
                background: var(--bg-chrome);
                border: 1px solid var(--hairline);
                display: grid;
                gap: 0.1rem;
                min-inline-size: 0;
            }

            .stage-meta-val {
                font-size: 0.75rem;
                font-weight: 500;
                color: var(--ink-primary);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mode-pill-group {
                display: inline-flex;
                align-items: center;
                gap: 0.2rem;
                padding: 0.2rem;
                border-radius: 0.55rem;
                border: 1px solid var(--hairline);
                background: var(--bg-chrome);
            }

            .step-block {
                display: grid;
                gap: 0.4rem;
                padding: 0.65rem 0.75rem;
                border-radius: 0.625rem;
                background: var(--bg-chrome);
                border: 1px solid var(--hairline);
            }

            .step-badge-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .step-num {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--ink-primary);
            }

            .step-num-dot {
                inline-size: 1.25rem;
                block-size: 1.25rem;
                border-radius: 9999px;
                display: inline-grid;
                place-items: center;
                background: rgba(205, 173, 105, 0.16);
                border: 1px solid var(--hairline-strong);
                color: var(--gold-light);
                font-size: 0.6875rem;
                font-weight: 600;
                flex-shrink: 0;
            }

            .view-pads-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
                gap: 0.7rem;
            }

            .view-pad-btn {
                display: grid;
                gap: 0.5rem;
                text-align: start;
                padding: 0.5rem;
                border-radius: 0.75rem;
                border: 1px solid var(--hairline);
                background: var(--bg-chrome);
                color: var(--ink-primary);
                font: inherit;
                cursor: pointer;
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out);
            }

            .view-pad-btn:hover {
                border-color: var(--hairline-strong);
                background: var(--bg-raised);
            }

            .view-pad-btn.is-active {
                border-color: var(--positive);
                background: rgba(76, 195, 138, 0.08);
            }

            .view-pad-thumb {
                position: relative;
                inline-size: 100%;
                aspect-ratio: 16 / 9;
                border-radius: 0.45rem;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.07);
                background: #0B0A08;
            }

            .view-pad-thumb.is-transparent {
                background-color: #12110F;
                background-image:
                    linear-gradient(45deg, #1d1c19 25%, transparent 25%),
                    linear-gradient(-45deg, #1d1c19 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #1d1c19 75%),
                    linear-gradient(-45deg, transparent 75%, #1d1c19 75%);
                background-size: 12px 12px;
                background-position: 0 0, 0 6px, 6px -6px, -6px 0;
            }

            .mini-layer {
                position: absolute;
                border-radius: 2px;
                pointer-events: none;
            }

            .mini-layer--cutout {
                background-image:
                    linear-gradient(45deg, rgba(255,255,255,0.14) 25%, transparent 25%),
                    linear-gradient(-45deg, rgba(255,255,255,0.14) 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.14) 75%),
                    linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.14) 75%);
                background-size: 8px 8px;
                background-color: rgba(0, 0, 0, 0.4);
                border: 1px dashed rgba(205, 173, 105, 0.72);
            }

            .mini-layer--accent {
                background: rgba(205, 173, 105, 0.28);
                border: 1px solid rgba(205, 173, 105, 0.45);
            }

            .mini-layer--block {
                background: rgba(250, 247, 238, 0.14);
                border: 1px solid rgba(250, 247, 238, 0.18);
            }

            .on-air-pill {
                position: absolute;
                inset-block-start: 0.35rem;
                inset-inline-end: 0.35rem;
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.1rem 0.45rem;
                border-radius: 9999px;
                background: rgba(7, 7, 6, 0.88);
                border: 1px solid rgba(76, 195, 138, 0.55);
                color: #9EE6C3;
                font-size: 0.625rem;
                font-weight: 600;
            }

            .on-air-dot {
                inline-size: 0.375rem;
                block-size: 0.375rem;
                border-radius: 50%;
                background: var(--positive);
            }

            .console-card {
                background: var(--bg-chrome);
                border: 1px solid var(--hairline);
                border-radius: 0.875rem;
                padding: 0.875rem;
                display: grid;
                gap: 0.875rem;
            }

            .console-tab-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.3rem;
                padding: 0.25rem;
                border-radius: 0.55rem;
                background: var(--bg-canvas);
                border: 1px solid var(--hairline);
            }

            .console-tab-btn {
                flex: 1 1 6.5rem;
                min-block-size: 2rem;
                padding: 0.25rem 0.65rem;
                border-radius: 0.4rem;
                border: 0;
                background: transparent;
                color: var(--ink-muted);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
                white-space: nowrap;
            }

            .console-tab-btn[aria-selected="true"] {
                background: var(--bg-raised);
                color: var(--ink-primary);
                box-shadow: inset 0 -2px 0 var(--gold-accent);
            }

            .timer-console-wrap {
                display: grid;
                gap: 0.85rem;
                grid-template-columns: minmax(0, 1fr);
                align-items: center;
            }

            @media (min-width: 50rem) {
                .timer-console-wrap {
                    grid-template-columns: 12rem minmax(0, 1fr);
                }
            }

            .timer-big-box {
                display: grid;
                place-items: center;
                gap: 0.25rem;
                padding: 1rem;
                border-radius: 0.75rem;
                background: var(--bg-raised);
                border: 1px solid var(--hairline);
            }

            .timer-big-digits {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 2.15rem;
                font-weight: 600;
                line-height: 1.05;
                font-variant-numeric: tabular-nums;
                color: var(--gold-light);
            }

            .sb-desk-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: start;
            }

            @media (min-width: 54rem) {
                .sb-desk-grid {
                    grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr) minmax(0, 1fr);
                }
            }

            .sb-team-card {
                display: grid;
                gap: 0.5rem;
                padding: 0.75rem;
                border-radius: 0.65rem;
                background: var(--bg-raised);
                border: 1px solid var(--hairline);
            }

            .sb-score-row {
                display: grid;
                grid-template-columns: 2.5rem minmax(0, 1fr) 2.5rem;
                gap: 0.4rem;
                align-items: center;
            }

            .sb-score-val {
                text-align: center;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 1.35rem;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
                color: var(--ink-primary);
            }

            #classic-scenes-panel[hidden],
            #unified-collections-panel[hidden],
            #classic-sources-workspace[hidden],
            #unified-screen-workspace[hidden],
            #unified-no-collection-card[hidden],
            #unified-active-stage-body[hidden],
            #legacy-overlay-push-panel[hidden],
            #recent-logs-panel[hidden],
            #utility-tab-bar[hidden],
            #utility-single-heading[hidden],
            [data-source-tab-panel][hidden],
            [data-uni-console-panel][hidden] {
                display: none !important;
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
                <div class="mode-pill-group" role="group" aria-label="Studio Control Mode">
                    <button
                        type="button"
                        class="s-btn s-btn-xs{{ ($controlMode ?? 'obs') !== 'screens' ? ' is-active' : '' }}"
                        data-studio-mode="obs"
                    >
                        OBS Scenes
                    </button>
                    <button
                        type="button"
                        class="s-btn s-btn-xs{{ ($controlMode ?? 'obs') === 'screens' ? ' is-active' : '' }}"
                        data-studio-mode="screens"
                    >
                        Unified Screen
                    </button>
                </div>
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
                <a href="{{ url('/admin') }}" class="s-btn">Admin Panel</a>
            </div>
        </header>

        <div class="studio-shell">
            {{-- Full-Width Broadcast Transport & Macros Bar --}}
            <section class="broadcast-strip" aria-label="Broadcast Outputs and Quick Macros">
                <div class="strip-group">
                    <span class="strip-label">Broadcast Outputs</span>
                    <button type="button" id="btn-stream" class="s-btn s-btn-xs">Start Stream</button>
                    <button type="button" id="btn-record" class="s-btn s-btn-xs">Start Record</button>
                    <button type="button" id="btn-pause-rec" class="s-btn s-btn-xs">Pause Rec</button>
                    <button type="button" id="btn-replay" class="s-btn s-btn-xs">Save Replay</button>
                    <button type="button" id="btn-vcam" class="s-btn s-btn-xs">Virtual Cam</button>
                    <button type="button" id="btn-studio-mode" class="s-btn s-btn-xs">Studio Mode</button>
                    <button type="button" id="btn-transition" class="s-btn s-btn-gold s-btn-xs" hidden>
                        Transition
                    </button>
                    <span id="studio-mode-label" class="dock-subtitle" style="margin-inline-start: 0.25rem;"></span>
                </div>

                <div class="strip-group">
                    <span class="strip-label">Macros</span>
                    @forelse ($macros as $macro)
                        <button type="button" class="s-btn s-btn-xs" data-run-macro="{{ $macro->id }}">
                            {{ $macro->name }}
                        </button>
                    @empty
                        <span class="dock-subtitle">No macros</span>
                    @endforelse
                    <a href="{{ url('/admin/obs-macros/create') }}" class="s-btn s-btn-xs">+ New</a>
                </div>
            </section>

            <main class="workspace-grid">
                <h1 class="visually-hidden">Studio Control Center — {{ $studio->name }}</h1>

                {{-- Left Rail: OBS Scenes OR Unified Screen Collections Setup --}}
                <aside class="studio-column" aria-label="Scenes and Screen Collections">
                    <div id="classic-scenes-panel" class="dock-panel" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
                        <div class="dock-head">
                            <div>
                                <h2 class="dock-title">Scenes &amp; Collections</h2>
                                <div class="dock-subtitle">Click a scene to inspect · Go Live cuts OBS</div>
                            </div>
                            <button type="button" class="s-btn s-btn-xs" id="sync-obs-btn">Sync</button>
                        </div>

                        <div style="display: grid; gap: 0.3rem; padding-block-end: 0.5rem; border-block-end: 1px solid var(--hairline);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.375rem;">
                                <label for="scene-collection-select" class="field-label">OBS Scene Collection</label>
                                <button type="button" class="s-btn s-btn-xs" commandfor="create-collection-dialog" command="show-modal">+ Collection</button>
                            </div>
                            <select id="scene-collection-select" class="s-select" style="inline-size: 100%; min-block-size: 2rem; font-size: 0.75rem;"></select>
                        </div>

                        <div style="display: flex; gap: 0.375rem;">
                            <input id="new-scene-name" type="text" class="s-input" placeholder="New scene name" aria-label="New scene name" style="flex: 1; min-inline-size: 0;">
                            <button type="button" id="create-scene-btn" class="s-btn">Add</button>
                        </div>

                        <div id="scenes-list" class="scene-list"></div>
                    </div>

                    <div id="unified-collections-panel" class="dock-panel" @if (($controlMode ?? 'obs') !== 'screens') hidden @endif>
                        <div class="dock-head">
                            <div>
                                <h2 class="dock-title">Screen Collections</h2>
                                <div class="dock-subtitle">3-step single-scene OBS setup</div>
                            </div>
                            <button type="button" class="s-btn s-btn-xs" id="btn-new-unified-col" commandfor="create-unified-collection-dialog" command="show-modal">+ New</button>
                        </div>

                        <div class="step-block">
                            <div class="step-badge-row">
                                <span class="step-num"><span class="step-num-dot">1</span> Choose Event</span>
                            </div>
                            <select id="unified-collection-select" class="s-select" aria-label="Active Event Screen Collection" style="inline-size: 100%; min-block-size: 2rem; font-size: 0.75rem;"></select>
                        </div>

                        <div class="step-block">
                            <div class="step-badge-row">
                                <span class="step-num"><span class="step-num-dot">2</span> Design Views &amp; Assets</span>
                            </div>
                            <a id="unified-builder-link" href="#" class="s-btn" style="inline-size: 100%;">Open Canvas Builder</a>
                        </div>

                        <div class="step-block">
                            <div class="step-badge-row">
                                <span class="step-num"><span class="step-num-dot">3</span> Connect 1 OBS Scene</span>
                            </div>
                            <button type="button" id="btn-push-unified-obs" class="s-btn s-btn-gold" style="inline-size: 100%;">Push Stage to OBS</button>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.35rem;">
                                <button type="button" id="btn-copy-unified-url" class="s-btn s-btn-xs">Copy URL</button>
                                <a id="unified-open-stage-link" href="#" target="_blank" class="s-btn s-btn-xs">Preview Stage</a>
                            </div>
                        </div>

                        <div id="unified-stage-summary" class="stage-meta-grid">
                            <div class="stage-meta-item">
                                <span class="field-label">OBS Scene</span>
                                <span id="uni-meta-scene" class="stage-meta-val">Obsisstant Stage</span>
                            </div>
                            <div class="stage-meta-item">
                                <span class="field-label">Browser Source</span>
                                <span id="uni-meta-source" class="stage-meta-val">Unified Screen</span>
                            </div>
                            <div class="stage-meta-item">
                                <span class="field-label">Stage Canvas</span>
                                <span id="uni-meta-res" class="stage-meta-val">1920 × 1080</span>
                            </div>
                            <div class="stage-meta-item">
                                <span class="field-label">Default Transition</span>
                                <span id="uni-meta-tx" class="stage-meta-val">Fade · 450ms</span>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Main Broadcast Workstation: Top Primary Stage + Bottom Audio & Utility Deck --}}
                <div class="main-workstation">
                    <section class="sources-workspace-panel" aria-label="Scene Sources and Source Editor">
                <div
                    id="agent-offline-banner"
                    @if ($studio->isOnline() || ($controlMode ?? 'obs') === 'screens') hidden @endif
                    style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem 0.875rem; border-radius: 0.5rem; border: 1px solid rgba(205, 173, 105, 0.35); background: rgba(205, 173, 105, 0.08);"
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
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;">
                        <button type="button" id="agent-banner-cta" class="s-btn s-btn-gold s-btn-xs" commandfor="install-agent-dialog" command="show-modal">
                            {{ $agentReachable ? 'Install on Another PC' : 'Install OBS Agent' }}
                        </button>
                        <button type="button" id="agent-banner-dismiss-btn" class="s-btn s-btn-xs" aria-label="Dismiss OBS Agent banner">
                            Dismiss
                        </button>
                    </div>
                </div>

                {{-- Classic OBS Scene Sources & Source Editor --}}
                <div id="classic-sources-workspace" class="stack-grid" style="gap: 0.875rem;" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
                    <div class="sources-toolbar">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                            <span class="stat-chip" id="stage-editing-label">Scene: {{ $initialEditingScene ?: 'None' }}</span>
                            <span id="studio-canvas-meta" class="dock-subtitle">Canvas: {{ $dims['width'] }} × {{ $dims['height'] }}</span>
                            <span id="layer-count-meta" class="dock-subtitle">0 sources</span>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;">
                            <button type="button" id="cut-editing-scene-btn" class="s-btn s-btn-xs">Go Live</button>
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

                    <div class="sources-split-grid">
                        {{-- Left Pane: Scene Sources List with Edit / Remove / Delete --}}
                        <div class="dock-panel" style="background-color: var(--bg-chrome);">
                            <div class="dock-head">
                                <div>
                                    <h2 class="dock-title">Scene Sources</h2>
                                    <div class="dock-subtitle">Order, visibility, lock, edit or delete</div>
                                </div>
                            </div>
                            <div id="layers-list" class="layer-list"></div>
                        </div>

                        {{-- Right Pane: Source Editor (Rename, Transform, Properties, Remove & Permanent Delete) --}}
                        <div>
                            <div id="no-source-selected-card" class="source-empty-state">
                                <strong style="color: var(--ink-primary); font-weight: 600;">No source selected</strong>
                                <span>Select any source on the left or click <strong>Edit</strong> to rename, adjust position/size, configure OBS properties, or delete it.</span>
                                <div style="margin-block-start: 0.45rem;">
                                    <button type="button" class="s-btn s-btn-xs" commandfor="add-source-dialog" command="show-modal">+ Add Source to Scene</button>
                                </div>
                            </div>

                            <div id="selected-source-card" class="source-inspector-card" hidden>
                                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <div style="min-inline-size: 0;">
                                        <div style="display: flex; align-items: baseline; gap: 0.45rem; flex-wrap: wrap;">
                                            <h2 class="dock-title">Source Editor</h2>
                                            <span id="sel-source-name" style="font-weight: 600; font-size: 0.85rem; color: var(--gold-light); overflow: hidden; text-overflow: ellipsis;"></span>
                                        </div>
                                        <div id="sel-source-kind" class="dock-subtitle"></div>
                                    </div>
                                    <div class="utility-tab-bar" role="tablist" aria-label="Source editor sections">
                                        <button type="button" class="s-btn s-btn-xs is-active" role="tab" aria-selected="true" data-source-tab="properties">OBS Properties</button>
                                        <button type="button" class="s-btn s-btn-xs" role="tab" aria-selected="false" data-source-tab="transform">Position &amp; Size</button>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 0.375rem; align-items: center;">
                                    <input type="text" id="rename-source-input" class="s-input" placeholder="Source name" aria-label="Rename selected source" style="flex: 1; min-inline-size: 0; min-block-size: 2rem;">
                                    <button type="button" id="btn-rename-source" class="s-btn s-btn-xs">Rename</button>
                                </div>

                                {{-- Tab 1: OBS Source Properties --}}
                                <div data-source-tab-panel="properties" class="stack-grid" style="gap: 0.5rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                        <div id="settings-load-status" class="dock-subtitle">Select a source to inspect</div>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button type="button" id="btn-refresh-browser" class="s-btn s-btn-xs" hidden>Refresh Page</button>
                                            <button type="button" id="btn-inspect-settings" class="s-btn s-btn-xs">Reload</button>
                                        </div>
                                    </div>

                                    <div id="visual-settings-fields" class="prop-editor"></div>

                                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                        <details id="raw-json-details" class="details-raw" style="flex: 1 1 12rem;">
                                            <summary>Raw JSON Settings</summary>
                                            <textarea id="source-settings-json" class="s-textarea" rows="3" placeholder="{}" style="inline-size: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem;"></textarea>
                                        </details>
                                        <button type="button" id="btn-save-settings" class="s-btn s-btn-gold">Save Properties to OBS</button>
                                    </div>
                                </div>

                                {{-- Tab 2: Position, Dimensions & Rotation --}}
                                <div data-source-tab-panel="transform" class="stack-grid" style="gap: 0.5rem;" hidden>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                        <span class="dock-subtitle">Exact canvas coordinates inside OBS Studio</span>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button type="button" id="btn-fit-source" class="s-btn s-btn-xs">Fit 16:9</button>
                                            <button type="button" id="btn-center-source" class="s-btn s-btn-xs">Center</button>
                                        </div>
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
                                </div>

                                <div style="display: flex; gap: 0.375rem; padding-block-start: 0.5rem; border-block-start: 1px solid var(--hairline);">
                                    <button type="button" id="btn-remove-scene-item" class="s-btn s-btn-xs" style="flex: 1;">Remove from Scene</button>
                                    <button type="button" id="btn-delete-source-forever" class="s-btn s-btn-xs s-btn-danger" style="flex: 1;">Delete Source</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unified Single-Browser Screen Remote Control Deck --}}
                <div id="unified-screen-workspace" class="stack-grid" style="gap: 1rem;" @if (($controlMode ?? 'obs') !== 'screens') hidden @endif>
                    <div id="unified-no-collection-card" class="source-empty-state" hidden>
                        <strong style="color: var(--ink-primary); font-weight: 600;">Create your first Event Screen Collection</strong>
                        <span>Build Starting Soon, Camera Cutout, Gameplay PiP, and Scoreboard views once, then switch them live inside a single OBS Browser Source.</span>
                        <div style="margin-block-start: 0.5rem;">
                            <button type="button" class="s-btn s-btn-gold" commandfor="create-unified-collection-dialog" command="show-modal">+ Create Screen Collection</button>
                        </div>
                    </div>

                    <div id="unified-active-stage-body" class="stack-grid" style="gap: 1rem;">
                        {{-- Program Header + Transition Bar --}}
                        <div class="sources-toolbar">
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                <span class="stat-chip is-live" id="unified-active-view-label">Live View: —</span>
                                <span class="stat-chip" id="unified-active-col-label">Collection</span>
                                <span class="dock-subtitle" id="unified-views-count">0 views</span>
                            </div>

                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem;">
                                <label for="unified-tx-type" class="field-label">Transition</label>
                                <select id="unified-tx-type" class="s-select" style="inline-size: auto; min-block-size: 1.9rem; font-size: 0.75rem;">
                                    <option value="default">View Default</option>
                                    @foreach ($transitionTypes as $txKey => $txLabel)
                                        <option value="{{ $txKey }}">{{ $txLabel }}</option>
                                    @endforeach
                                </select>
                                <input type="number" id="unified-tx-dur" class="s-input" min="0" max="3000" step="50" value="450" style="inline-size: 4.5rem; min-block-size: 1.9rem; font-size: 0.75rem;" aria-label="Transition duration in milliseconds" title="Transition duration (up to 3000 ms)">
                                <span class="dock-subtitle">ms</span>
                            </div>
                        </div>

                        {{-- Screen Views Deck with Mini Schematic Thumbnails --}}
                        <div class="stack-grid" style="gap: 0.5rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <div>
                                    <h2 class="dock-title">Screen Views</h2>
                                    <div class="dock-subtitle">Click any view to transition your live OBS Browser Source</div>
                                </div>
                                <a id="unified-center-builder-link" href="#" class="s-btn s-btn-xs">Customize Views</a>
                            </div>
                            <div id="unified-views-grid" class="view-pads-grid"></div>
                        </div>

                        {{-- Tabbed Live Show Console (Timer · Scoreboard · Lower Third & Alerts · Layers) --}}
                        <div class="console-card">
                            <div class="console-tab-row" role="tablist" aria-label="Live Show Remote Controls">
                                <button type="button" class="console-tab-btn" role="tab" aria-selected="true" data-uni-console-tab="timer">Timer &amp; Clock</button>
                                <button type="button" class="console-tab-btn" role="tab" aria-selected="false" data-uni-console-tab="scoreboard">Scoreboard</button>
                                <button type="button" class="console-tab-btn" role="tab" aria-selected="false" data-uni-console-tab="graphics">Lower Third &amp; Alerts</button>
                                <button type="button" class="console-tab-btn" role="tab" aria-selected="false" data-uni-console-tab="layers">View Layers</button>
                            </div>

                            {{-- Tab 1: Timer & Clock --}}
                            <div data-uni-console-panel="timer">
                                <div class="timer-console-wrap">
                                    <div class="timer-big-box">
                                        <span class="field-label" id="uni-timer-status-label">Stopped</span>
                                        <span id="uni-timer-readout" class="timer-big-digits">05:00</span>
                                    </div>
                                    <div class="stack-grid" style="gap: 0.6rem;">
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                                            <button type="button" id="uni-timer-start" class="s-btn s-btn-gold">Start</button>
                                            <button type="button" id="uni-timer-pause" class="s-btn">Pause</button>
                                            <button type="button" id="uni-timer-reset" class="s-btn">Reset</button>
                                            <button type="button" id="uni-timer-minus" class="s-btn">-1m</button>
                                            <button type="button" id="uni-timer-plus" class="s-btn">+1m</button>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem;">
                                            <span class="field-label">Quick Set:</span>
                                            <button type="button" class="s-btn s-btn-xs" data-uni-timer-preset="60">1m</button>
                                            <button type="button" class="s-btn s-btn-xs" data-uni-timer-preset="180">3m</button>
                                            <button type="button" class="s-btn s-btn-xs" data-uni-timer-preset="300">5m</button>
                                            <button type="button" class="s-btn s-btn-xs" data-uni-timer-preset="600">10m</button>
                                            <input type="number" id="uni-timer-seconds" class="s-input" min="0" step="30" value="300" aria-label="Custom timer seconds" style="inline-size: 5.5rem; min-block-size: 1.85rem;">
                                            <button type="button" id="uni-timer-set" class="s-btn s-btn-xs">Set Secs</button>
                                        </div>
                                        <div id="uni-timer-rule-hint" class="dock-subtitle">Configure auto-switch rules when timer hits 00:00 inside Canvas Builder.</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 2: Tournament Scoreboard --}}
                            <div data-uni-console-panel="scoreboard" hidden>
                                <div class="sb-desk-grid">
                                    <div class="sb-team-card">
                                        <label class="field-stack">
                                            <span class="field-label">Team A</span>
                                            <input type="text" id="uni-sb-a-name" class="s-input" value="Team Alpha">
                                        </label>
                                        <div class="sb-score-row">
                                            <button type="button" id="uni-sb-a-minus" class="s-btn" aria-label="Decrease Team A score">-</button>
                                            <span id="uni-sb-a-score" class="sb-score-val">0</span>
                                            <button type="button" id="uni-sb-a-plus" class="s-btn s-btn-gold" aria-label="Increase Team A score">+</button>
                                        </div>
                                    </div>

                                    <div class="sb-team-card">
                                        <label class="field-stack">
                                            <span class="field-label">Match Title</span>
                                            <input type="text" id="uni-sb-match" class="s-input" placeholder="Grand Finals">
                                        </label>
                                        <label class="field-stack">
                                            <span class="field-label">Round / Format</span>
                                            <input type="text" id="uni-sb-round" class="s-input" placeholder="Game 1 · BO3">
                                        </label>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                            <button type="button" id="uni-sb-save" class="s-btn s-btn-xs" style="flex: 1;">Apply Names</button>
                                            <button type="button" id="uni-sb-swap" class="s-btn s-btn-xs">Swap</button>
                                            <button type="button" id="uni-sb-reset" class="s-btn s-btn-xs">0 - 0</button>
                                        </div>
                                    </div>

                                    <div class="sb-team-card">
                                        <label class="field-stack">
                                            <span class="field-label">Team B</span>
                                            <input type="text" id="uni-sb-b-name" class="s-input" value="Team Omega">
                                        </label>
                                        <div class="sb-score-row">
                                            <button type="button" id="uni-sb-b-minus" class="s-btn" aria-label="Decrease Team B score">-</button>
                                            <span id="uni-sb-b-score" class="sb-score-val">0</span>
                                            <button type="button" id="uni-sb-b-plus" class="s-btn s-btn-gold" aria-label="Increase Team B score">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 3: Lower Third, Alerts & Ticker --}}
                            <div data-uni-console-panel="graphics" hidden>
                                <div class="sources-split-grid">
                                    <div class="sb-team-card">
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <strong style="font-size: 0.8125rem;">Lower Third Banner</strong>
                                            <span id="uni-l3-status" class="dock-subtitle">Visible</span>
                                        </div>
                                        <label class="field-stack">
                                            <span class="field-label">Speaker / Headline</span>
                                            <input type="text" id="uni-l3-title" class="s-input" placeholder="Broadcast Host">
                                        </label>
                                        <label class="field-stack">
                                            <span class="field-label">Role / Subtitle</span>
                                            <input type="text" id="uni-l3-sub" class="s-input" placeholder="Live From Studio">
                                        </label>
                                        <div style="display: flex; gap: 0.375rem;">
                                            <button type="button" id="uni-l3-show" class="s-btn s-btn-gold" style="flex: 1;">Show Lower Third</button>
                                            <button type="button" id="uni-l3-hide" class="s-btn" style="flex: 1;">Hide</button>
                                        </div>
                                    </div>

                                    <div class="sb-team-card">
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <strong style="font-size: 0.8125rem;">Pop-Up Alert &amp; Ticker</strong>
                                            <button type="button" id="uni-ticker-toggle" class="s-btn s-btn-xs">Ticker: On</button>
                                        </div>
                                        <label class="field-stack">
                                            <span class="field-label">Alert Headline</span>
                                            <input type="text" id="uni-alert-title" class="s-input" placeholder="Live Update">
                                        </label>
                                        <label class="field-stack">
                                            <span class="field-label">Alert Message</span>
                                            <input type="text" id="uni-alert-msg" class="s-input" placeholder="Next match starts in 2 minutes">
                                        </label>
                                        <div style="display: flex; gap: 0.375rem;">
                                            <button type="button" id="uni-alert-show" class="s-btn s-btn-gold" style="flex: 1;">Push Alert</button>
                                            <button type="button" id="uni-alert-hide" class="s-btn" style="flex: 1;">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 4: Active View Runtime Layer Toggles --}}
                            <div data-uni-console-panel="layers" hidden>
                                <div class="stack-grid" style="gap: 0.5rem;">
                                    <div class="dock-subtitle">Toggle individual elements on the currently live Screen View without switching views or editing saved layouts.</div>
                                    <div id="uni-layer-toggles" style="display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

                    {{-- Lower Broadcast Console: Wide Audio Mixer Grid + Tabbed Overlay Push / Command Log --}}
                    <section class="lower-console-grid" aria-label="Audio Mixer, Overlays and Command Log">
                        <div class="dock-panel">
                            <div class="dock-head" style="flex-wrap: wrap;">
                                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                                    <h2 class="dock-title">Audio Mixer</h2>
                                    <span id="audio-count-meta" class="dock-subtitle">Live OBS faders</span>
                                </div>
                                <input type="search" id="audio-mixer-filter" class="s-input" placeholder="Filter audio inputs" aria-label="Filter audio inputs" style="inline-size: min(100%, 14rem); min-block-size: 1.85rem; font-size: 0.75rem;">
                            </div>
                            <div id="audio-mixer-list" class="audio-mixer-grid"></div>
                        </div>

                        <div class="dock-panel">
                            <div class="dock-head" style="flex-wrap: wrap;">
                                <div id="utility-tab-bar" class="utility-tab-bar" role="tablist" aria-label="Studio Utilities" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
                                    <button type="button" id="utility-tab-overlay" class="s-btn s-btn-xs is-active" role="tab" aria-selected="true" data-utility-tab="overlay">Live Overlay Push</button>
                                    <button type="button" id="utility-tab-logs" class="s-btn s-btn-xs" role="tab" aria-selected="false" data-utility-tab="logs">Command Log</button>
                                </div>
                                <h2 id="utility-single-heading" class="dock-title" @if (($controlMode ?? 'obs') !== 'screens') hidden @endif>Recent Command Log</h2>
                                <span class="dock-subtitle" id="utility-sub-meta">Live telemetry</span>
                            </div>

                            <div id="legacy-overlay-push-panel" style="display: grid; gap: 0.5rem;" @if (($controlMode ?? 'obs') === 'screens') hidden @endif>
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
                                    <button type="button" id="btn-push-overlay-scene" class="s-btn" style="flex: 1;">Push to Scene</button>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.375rem;">
                                    <input type="text" id="overlay-guest-name" class="s-input" placeholder="Lower-third name" aria-label="Lower-third name">
                                    <input type="text" id="overlay-guest-role" class="s-input" placeholder="Role / subtitle" aria-label="Role or subtitle">
                                </div>
                                <div style="display: flex; gap: 0.375rem;">
                                    <button type="button" id="btn-overlay-show" class="s-btn" style="flex: 1;">Show Lower Third</button>
                                    <button type="button" id="btn-overlay-hide" class="s-btn" style="flex: 1;">Hide</button>
                                </div>
                            </div>

                            <div id="recent-logs-panel" @if (($controlMode ?? 'obs') !== 'screens') hidden @endif>
                                <div id="recent-logs-list" class="logs-scroll">
                                    @forelse ($recentLogs as $log)
                                        <div style="display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.3rem 0.5rem; border-radius: 0.45rem; background: var(--bg-chrome); border: 1px solid var(--hairline);">
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
                        </div>
                    </section>
                </div>
            </main>
        </div>

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
                        <input type="checkbox" id="install-studio-only-toggle" @checked($defaultStudioScopedInstaller ?? false)>
                        <span>Bind to this Studio only ({{ $studio->name }})</span>
                    </label>
                </div>

                <div id="agent-panel-mac" style="display: grid; gap: 0.75rem;">
                    <button
                        type="button"
                        class="s-btn s-btn-gold"
                        id="copy-mac-cmd-btn"
                        style="justify-content: center; font-size: 0.875rem; padding: 0.65rem 1rem;"
                    >
                        Copy 1-Click Mac Setup (No Apple Warnings)
                    </button>

                    <input type="text" id="mac-oneclick-cmd" class="s-input" readonly value="{{ $installerPayload['mac_command'] }}" onclick="this.select();" style="font-size: 0.75rem; color: var(--gold-light);">

                    <ol style="margin: 0; padding-inline-start: 1.25rem; display: grid; gap: 0.35rem; font-size: 0.8125rem; color: var(--ink-secondary);">
                        <li>Click <strong>Copy 1-Click Mac Setup</strong> above.</li>
                        <li>Press <strong>⌘ Space</strong>, type <strong>Terminal</strong>, press <strong>Return</strong>, then paste (<strong>⌘ V</strong>) &amp; press <strong>Return</strong>.</li>
                        <li>In OBS Studio: open <strong>Tools → WebSocket Server Settings</strong> and check <strong>Enable WebSocket server</strong>.</li>
                    </ol>

                    <details style="padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid var(--hairline); background: var(--bg-raised);">
                        <summary style="cursor: pointer; font-size: 0.75rem; color: var(--ink-muted);">
                            Prefer manual .zip download? (Requires approving in macOS Privacy &amp; Security)
                        </summary>
                        <div style="display: grid; gap: 0.5rem; margin-block-start: 0.5rem;">
                            <a
                                id="download-mac-installer-btn"
                                href="{{ $installerPayload['mac_download_url'] }}"
                                class="s-btn s-btn-xs"
                                style="justify-content: center;"
                            >
                                Download Mac Installer (.zip)
                            </a>
                            <span class="dock-subtitle" style="font-size: 0.7rem;">
                                On macOS Sequoia/Tahoe 15+, browser-downloaded scripts trigger an Apple security block. If you see <em>&ldquo;Not Opened&rdquo;</em>, click <strong>Done</strong> → open <strong>System Settings → Privacy &amp; Security</strong> → click <strong>Open Anyway</strong> (or use the 1-Click button above to skip it completely).
                            </span>
                        </div>
                    </details>
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
                        <li>Double-click <code>Install &amp; Run Obsisstant Agent.bat</code> <em>(auto-starts silently on Windows boot)</em>.</li>
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

        {{-- Native Declarative Modal: Create Event / Tournament Screen Collection --}}
        <dialog id="create-unified-collection-dialog" class="obs-dialog" aria-labelledby="create-unified-col-title">
            <div class="dialog-inner">
                <div class="dialog-head">
                    <h2 id="create-unified-col-title" class="dialog-title">Create Screen Collection</h2>
                    <button type="button" class="s-btn s-btn-xs" commandfor="create-unified-collection-dialog" command="close">Close</button>
                </div>
                <form id="create-unified-collection-form" class="stack-grid" style="gap: 0.75rem;">
                    <label class="field-stack">
                        <span class="field-label">Event / Tournament Name</span>
                        <input type="text" id="new-unified-col-name" class="s-input" placeholder="e.g. Winter Championship 2026" required>
                    </label>
                    <div class="transform-grid">
                        <label class="field-stack">
                            <span class="field-label">OBS Single Scene Name</span>
                            <input type="text" id="new-unified-col-scene" class="s-input" value="Obsisstant Stage">
                        </label>
                        <label class="field-stack">
                            <span class="field-label">OBS Browser Source Name</span>
                            <input type="text" id="new-unified-col-source" class="s-input" value="Obsisstant - Unified Screen">
                        </label>
                    </div>
                    <label class="prop-check">
                        <span>Include starter views (Starting Soon, Main Cam, Gameplay PiP, Dual Cam, BRB)</span>
                        <input type="checkbox" id="new-unified-col-starter" checked>
                    </label>
                    <label class="prop-check">
                        <span>Open Visual Canvas Builder right after creating</span>
                        <input type="checkbox" id="new-unified-col-open-builder">
                    </label>
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="s-btn" commandfor="create-unified-collection-dialog" command="close">Cancel</button>
                        <button type="submit" class="s-btn s-btn-gold">Create Collection</button>
                    </div>
                </form>
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
        @php
            $controlCenterEndpoints = [
                'command' => route('studio.command', ['studio' => $studio->slug], false),
                'commandResult' => route('studio.command-result', ['studio' => $studio->slug, 'commandUuid' => '__UUID__'], false),
                'snapshot' => route('studio.snapshot', ['studio' => $studio->slug], false),
                'mediaUpload' => route('studio.media-upload', ['studio' => $studio->slug], false),
                'mediaStoragePath' => route('studio.media-storage-path', ['studio' => $studio->slug], false),
                'bootstrapToken' => route('agent-installer.bootstrap-token', [], false),
                'storageSettingsPage' => '/admin/studio-settings',
                'controlMode' => route('studio.control-mode', ['studio' => $studio->slug], false),
                'collectionsStore' => route('studio.collections.store', ['studio' => $studio->slug], false),
                'collectionBuilderTpl' => route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
                'collectionSwitchTpl' => route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
                'collectionRuntimeTpl' => route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
                'collectionPushTpl' => route('studio.collections.push', ['studio' => $studio->slug, 'collection' => '__COL__'], false),
            ];
        @endphp
        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const studioSlug = @json($studio->slug);
                const studioId = @json($studio->id);
                const screensData = @json($screensPayload);
                let controlMode = @json($controlMode ?? 'obs');
                let collectionsData = @json($collectionsPayload ?? []);
                let activeCollectionId = @json($activeCollectionId);

                const endpoints = @json($controlCenterEndpoints);

                const initialInstallerPayload = @json($installerPayload);
                const relayInstallerPayload = @json($relayInstallerPayload ?? $installerPayload);
                const defaultStudioScopedInstaller = @json($defaultStudioScopedInstaller ?? false);
                let configuredMediaDir = @json($mediaStoragePath, JSON_UNESCAPED_SLASHES);
                let liveState = @json($liveState ?: new stdClass());
                let studioStatus = @json($studio->status);
                let agentReachable = @json($agentReachable);
                const hasAnyConnectedStudio = @json($hasAnyConnectedStudio ?? false);
                let editingScene = @json($initialEditingScene);
                let selectedSceneItemId = null;
                let studioChannel = null;
                let snapshotInFlight = null;
                let lastEchoStateAt = 0;
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
                    if (!Array.isArray(nextState.scenes) && liveState && typeof liveState === 'object') {
                        liveState = { ...liveState, ...nextState };
                        if (nextState.currentProgramScene && Array.isArray(nextState.sceneItems)) {
                            liveState.sceneItemsByScene = {
                                ...(liveState.sceneItemsByScene || {}),
                                [nextState.currentProgramScene]: nextState.sceneItems,
                            };
                        }
                        return;
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

                const createCommandUuid = () => {
                    if (window.crypto?.randomUUID) {
                        return window.crypto.randomUUID();
                    }
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                        const r = (Math.random() * 16) | 0;
                        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
                    });
                };

                const isEchoReady = () => Boolean(
                    studioChannel?.subscription?.subscribed
                    && window.Echo?.connector?.pusher?.connection?.state === 'connected'
                );

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

                    // Let Echo whisper or server broadcast resolve instantaneously; only poll HTTP if delayed
                    setTimeout(poll, isEchoReady() ? 1150 : 250);
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
                    const needsFullResponse = requestType === 'obsisstant.FetchInputSettings'
                        || requestType === 'obsisstant.PickLocalDirectory';
                    const commandUuid = createCommandUuid();
                    if (!silent) showToast(`${label}...`);

                    try {
                        const waitPromise = wait ? waitForCommandResult(commandUuid, 4500) : null;
                        let usedWhisper = false;
                        if (!needsFullResponse && isEchoReady()) {
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

                        const httpPromise = postJson(endpoints.command, {
                            command_uuid: commandUuid,
                            request_type: requestType,
                            request_data: requestData,
                        });

                        if (!wait) {
                            return await httpPromise;
                        }

                        const httpGuard = httpPromise.catch((err) => {
                            if (pendingCommandResolvers.has(commandUuid)) {
                                pendingCommandResolvers.get(commandUuid)({ status: 'failed', error_message: err.message });
                            }
                            throw err;
                        });

                        const settled = await waitPromise;
                        if (!usedWhisper && (Date.now() - lastEchoStateAt) > 650 && !needsFullResponse) {
                            scheduleSnapshotRefresh(220);
                        }

                        if (settled?.status === 'failed') {
                            showToast(`OBS Error: ${settled.error_message || `${label} failed`}`, 'error');
                        } else if (settled?.status === 'succeeded') {
                            if (!silent) showToast(`${label} complete`, 'success');
                        } else {
                            if (!needsFullResponse) {
                                scheduleSnapshotRefresh(400);
                            }
                            if (!silent) {
                                showToast(
                                    studioStatus === 'offline'
                                        ? 'OBS Agent offline — install or start OBS Agent on your PC'
                                        : 'OBS Agent did not respond — check OBS WebSocket & restart OBS Agent',
                                    'error',
                                );
                            }
                        }

                        const res = await httpGuard.catch(() => ({ ok: usedWhisper, command_uuid: commandUuid, request_type: requestType }));
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
                    const canvasMeta = document.getElementById('studio-canvas-meta');
                    if (canvasMeta) {
                        canvasMeta.textContent = `Canvas: ${dims.width} × ${dims.height}`;
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

                    const cutEditingBtn = document.getElementById('cut-editing-scene-btn');
                    if (cutEditingBtn) {
                        const isEditingProgram = Boolean(editingScene && editingScene === liveState?.currentProgramScene);
                        cutEditingBtn.disabled = !editingScene;
                        cutEditingBtn.textContent = liveState?.studioModeEnabled
                            ? 'Send to Preview'
                            : (isEditingProgram ? 'On Air' : 'Go Live');
                        cutEditingBtn.classList.toggle('is-active', isEditingProgram);
                        cutEditingBtn.classList.toggle('s-btn-gold', !isEditingProgram);
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
                        const cutSceneLiveOrPreview = () => {
                            const cmd = liveState?.studioModeEnabled ? 'SetCurrentPreviewScene' : 'SetCurrentProgramScene';
                            sendObsCommand(cmd, { sceneName: sName });
                        };

                        selectBtn.addEventListener('click', () => {
                            editingScene = sName;
                            selectedSceneItemId = null;
                            renderAll();
                        });
                        selectBtn.addEventListener('dblclick', cutSceneLiveOrPreview);

                        const actions = document.createElement('div');
                        actions.className = 'scene-actions';

                        const cutBtn = document.createElement('button');
                        cutBtn.type = 'button';
                        cutBtn.className = `s-btn s-btn-xs${isProgram ? ' is-active' : ''}`;
                        cutBtn.textContent = liveState?.studioModeEnabled ? 'Preview' : (isProgram ? 'On Air' : 'Go Live');
                        cutBtn.title = liveState?.studioModeEnabled
                            ? `Cue "${sName}" in Preview`
                            : (isProgram ? `"${sName}" is currently Live in OBS` : `Switch OBS Live to "${sName}"`);
                        cutBtn.addEventListener('click', cutSceneLiveOrPreview);

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

                const selectSourceForEditing = (itemId, focusEditor = false) => {
                    selectedSceneItemId = Number(itemId);
                    renderLayersInspector();
                    if (focusEditor) {
                        const selCard = document.getElementById('selected-source-card');
                        selCard?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        document.getElementById('rename-source-input')?.focus();
                    }
                };

                const renderLayersInspector = () => {
                    const listEl = document.getElementById('layers-list');
                    const countEl = document.getElementById('layer-count-meta');
                    const selCard = document.getElementById('selected-source-card');
                    const emptyCard = document.getElementById('no-source-selected-card');
                    if (!listEl) return;

                    const items = [...getItemsForEditingScene()];
                    if (countEl) countEl.textContent = `${items.length} source${items.length === 1 ? '' : 's'}`;

                    if (items.length === 0) {
                        listEl.innerHTML = '<div class="dock-subtitle">No sources in this scene yet. Click + Add Source above.</div>';
                        loadedSettingsKey = null;
                        if (selCard) selCard.hidden = true;
                        if (emptyCard) emptyCard.hidden = false;
                        return;
                    }

                    const ordered = [...items].reverse();
                    if (
                        ordered.length > 0
                        && (selectedSceneItemId === null || !items.some((i) => Number(i.sceneItemId) === selectedSceneItemId))
                    ) {
                        selectedSceneItemId = Number(ordered[0].sceneItemId);
                    }
                    listEl.innerHTML = '';

                    ordered.forEach((item) => {
                        const itemId = Number(item.sceneItemId);
                        const idx = Number(item.sceneItemIndex ?? 0);
                        const sourceLabel = item.sourceName || `Source #${itemId}`;
                        const row = document.createElement('div');
                        row.className = `layer-row${itemId === selectedSceneItemId ? ' is-selected' : ''}`;

                        const topRow = document.createElement('div');
                        topRow.className = 'layer-row-top';

                        const selectBtn = document.createElement('button');
                        selectBtn.type = 'button';
                        selectBtn.className = 'scene-row-btn';
                        selectBtn.textContent = sourceLabel;
                        selectBtn.addEventListener('click', () => selectSourceForEditing(itemId, false));

                        const kindTag = document.createElement('span');
                        kindTag.className = 'layer-kind-tag';
                        kindTag.textContent = formatInputKindLabel(item.inputKind);

                        topRow.append(selectBtn, kindTag);

                        const btns = document.createElement('div');
                        btns.className = 'layer-row-actions';

                        const upBtn = document.createElement('button');
                        upBtn.type = 'button';
                        upBtn.className = 's-btn s-btn-xs';
                        upBtn.textContent = '↑';
                        upBtn.title = 'Bring forward';
                        upBtn.setAttribute('aria-label', `Bring ${sourceLabel} forward`);
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
                        downBtn.setAttribute('aria-label', `Send ${sourceLabel} backward`);
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
                        visBtn.setAttribute('aria-label', `${item.sceneItemEnabled ? 'On' : 'Off'} visibility for ${sourceLabel}`);
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
                        lockBtn.setAttribute('aria-label', `${item.sceneItemLocked ? 'Locked' : 'Free'} lock for ${sourceLabel}`);
                        lockBtn.addEventListener('click', () => {
                            sendObsCommand('SetSceneItemLocked', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                                sceneItemLocked: !item.sceneItemLocked,
                            });
                        });

                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = `s-btn s-btn-xs${itemId === selectedSceneItemId ? ' is-active' : ''}`;
                        editBtn.textContent = 'Edit';
                        editBtn.setAttribute('aria-label', `Edit ${sourceLabel}`);
                        editBtn.addEventListener('click', () => selectSourceForEditing(itemId, true));

                        const rmBtn = document.createElement('button');
                        rmBtn.type = 'button';
                        rmBtn.className = 's-btn s-btn-xs';
                        rmBtn.textContent = 'Remove';
                        rmBtn.title = 'Remove from this scene';
                        rmBtn.setAttribute('aria-label', `Remove ${sourceLabel} from scene`);
                        rmBtn.addEventListener('click', async () => {
                            rmBtn.disabled = true;
                            if (selectedSceneItemId === itemId) {
                                selectedSceneItemId = null;
                                loadedSettingsKey = null;
                            }
                            await sendObsCommand('RemoveSceneItem', {
                                sceneName: editingScene,
                                sceneItemId: itemId,
                            });
                        });

                        const delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 's-btn s-btn-xs s-btn-danger';
                        delBtn.textContent = 'Delete';
                        delBtn.title = 'Delete source permanently across OBS';
                        delBtn.setAttribute('aria-label', `Delete ${sourceLabel}`);
                        delBtn.addEventListener('click', () => {
                            if (!item.sourceName) return;
                            selectedSceneItemId = itemId;
                            pendingDeleteSourceName = item.sourceName;
                            document.getElementById('delete-source-copy').textContent = `Permanently delete OBS source "${item.sourceName}" across all scenes inside OBS Studio? This cannot be undone.`;
                            openDialog(document.getElementById('delete-source-dialog'));
                        });

                        btns.append(upBtn, downBtn, visBtn, lockBtn, editBtn, rmBtn, delBtn);
                        row.append(topRow, btns);
                        listEl.appendChild(row);
                    });

                    const selectedItem = items.find((i) => Number(i.sceneItemId) === selectedSceneItemId);
                    if (!selectedItem || !selCard) {
                        loadedSettingsKey = null;
                        if (selCard) selCard.hidden = true;
                        if (emptyCard) emptyCard.hidden = false;
                        return;
                    }

                    if (emptyCard) emptyCard.hidden = true;
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
                        if (field && document.activeElement !== field) {
                            field.value = String(val);
                        }
                    };
                    setTransformFieldIfIdle('tf-x', Math.round(Number(tf.positionX ?? 0)));
                    setTransformFieldIfIdle('tf-y', Math.round(Number(tf.positionY ?? 0)));
                    setTransformFieldIfIdle('tf-w', Math.round(rect.w));
                    setTransformFieldIfIdle('tf-h', Math.round(rect.h));
                    setTransformFieldIfIdle('tf-rot', Math.round(Number(tf.rotation ?? 0)));

                    if (controlMode !== 'screens') {
                        loadSelectedSourceSettings(selectedItem);
                    }
                };

                const renderAudioMixer = () => {
                    const container = document.getElementById('audio-mixer-list');
                    if (!container) return;

                    const inputs = Array.isArray(liveState?.audioInputs) ? liveState.audioInputs : [];
                    const filterInput = document.getElementById('audio-mixer-filter');
                    const query = (filterInput?.value || '').trim().toLowerCase();
                    const filtered = query
                        ? inputs.filter((inp) => String(inp.inputName || '').toLowerCase().includes(query))
                        : inputs;

                    const countMeta = document.getElementById('audio-count-meta');
                    if (countMeta) {
                        if (inputs.length === 0) {
                            countMeta.textContent = 'Live OBS faders';
                        } else if (query) {
                            countMeta.textContent = `${filtered.length} of ${inputs.length} faders`;
                        } else {
                            countMeta.textContent = `${inputs.length} live ${inputs.length === 1 ? 'fader' : 'faders'}`;
                        }
                    }

                    if (inputs.length === 0) {
                        container.innerHTML = '<div class="dock-subtitle">No OBS audio inputs detected yet.</div>';
                        return;
                    }

                    if (filtered.length === 0) {
                        container.innerHTML = '<div class="dock-subtitle">No audio inputs match your filter.</div>';
                        return;
                    }

                    container.innerHTML = '';
                    filtered.forEach((inp) => {
                        const name = inp.inputName || '';
                        const muted = Boolean(inp.inputMuted);
                        const db = Math.round(Number(inp.inputVolumeDb ?? 0) * 10) / 10;

                        const card = document.createElement('div');
                        card.className = 'audio-item';

                        const top = document.createElement('div');
                        top.style.cssText = 'display:flex;justify-content:space-between;align-items:center;gap:0.45rem;min-inline-size:0;';

                        const title = document.createElement('span');
                        title.style.cssText = 'font-weight:500;font-size:0.8125rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-inline-size:0;';
                        title.title = name;
                        title.textContent = name;

                        const right = document.createElement('div');
                        right.style.cssText = 'display:inline-flex;align-items:center;gap:0.35rem;flex-shrink:0;';

                        const dbTag = document.createElement('span');
                        dbTag.className = 'dock-subtitle';
                        dbTag.style.cssText = 'white-space:nowrap;font-variant-numeric:tabular-nums;';
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

                const installDialog = document.getElementById('install-agent-dialog');
                const installBanner = document.getElementById('agent-offline-banner');
                const installBannerDismissBtn = document.getElementById('agent-banner-dismiss-btn');
                const installModalText = document.getElementById('install-modal-live-text');
                const installBannerTitle = document.getElementById('agent-banner-title');
                const installBannerCopy = document.getElementById('agent-banner-copy');
                const installBannerCta = document.getElementById('agent-banner-cta');
                const topbarInstallBtn = document.getElementById('open-install-agent-btn');
                const INSTALL_DIALOG_DISMISS_KEY = 'obs_install_dialog_dismissed_v2';
                const INSTALL_BANNER_DISMISS_KEY = `obs_install_banner_dismissed_v2_${studioSlug}`;
                let installPollTimer = null;
                let wasStudioOnline = studioStatus !== 'offline';

                const isInstallDialogDismissed = () => {
                    try {
                        return localStorage.getItem(INSTALL_DIALOG_DISMISS_KEY) === '1';
                    } catch {
                        return false;
                    }
                };

                const isInstallBannerDismissed = () => {
                    try {
                        return localStorage.getItem(INSTALL_BANNER_DISMISS_KEY) === '1';
                    } catch {
                        return false;
                    }
                };

                function updateAgentOnboardingState() {
                    const isOnline = studioStatus !== 'offline';
                    const isScreensMode = controlMode === 'screens';
                    if (installBanner) {
                        installBanner.hidden = isOnline || isScreensMode || isInstallBannerDismissed();
                    }
                    if (topbarInstallBtn) {
                        topbarInstallBtn.textContent = (isOnline || hasAnyConnectedStudio) ? 'Add PC' : 'Install Agent';
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
                        applyInstallerPayload(relayInstallerPayload);
                        return;
                    }
                    if (defaultStudioScopedInstaller) {
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
                        localStorage.setItem(INSTALL_DIALOG_DISMISS_KEY, '1');
                    } catch {
                        // Ignore storage quota / privacy mode errors
                    }
                    clearInterval(installPollTimer);
                    installPollTimer = null;
                };

                installBannerDismissBtn?.addEventListener('click', () => {
                    try {
                        localStorage.setItem(INSTALL_BANNER_DISMISS_KEY, '1');
                    } catch {
                        // Ignore storage quota / privacy mode errors
                    }
                    if (installBanner) {
                        installBanner.hidden = true;
                    }
                });

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
                    const runtimeCfg = { ...echoCfg };
                    if (['ob', 'localhost', '127.0.0.1'].includes(runtimeCfg.wsHost) && window.location.hostname) {
                        runtimeCfg.wsHost = window.location.hostname;
                        runtimeCfg.forceTLS = window.location.protocol === 'https:';
                    }
                    window.Echo = new window.EchoFactory(runtimeCfg);
                    const handleStudioStateEvent = (payload) => {
                        lastEchoStateAt = Date.now();
                        if (payload?.status) {
                            studioStatus = payload.status;
                            agentReachable = true;
                        }
                        if (payload?.live_state) applyIncomingLiveState(payload.live_state);
                        renderAll();
                    };
                    const handleCommandAckEvent = (payload) => {
                        const isWhisperPayload = payload && !('response_data' in payload);
                        if (
                            isWhisperPayload
                            && payload?.status === 'failed'
                            && studioStatus !== 'offline'
                            && String(payload?.error_message || '').toLowerCase().includes('not currently connected')
                        ) {
                            return;
                        }
                        const needsFullResponse = payload?.request_type === 'obsisstant.FetchInputSettings'
                            || payload?.request_type === 'obsisstant.PickLocalDirectory';
                        const resolver = pendingCommandResolvers.get(payload?.command_uuid);
                        if (resolver && !(isWhisperPayload && needsFullResponse)) {
                            resolver(payload);
                        }
                        if (payload?.status === 'failed' && payload?.error_message) {
                            showToast(`OBS Error: ${payload.error_message}`, 'error');
                        }
                        if (
                            !isWhisperPayload
                            && payload?.request_type !== 'obsisstant.FetchInputSettings'
                            && (Date.now() - lastEchoStateAt) > 650
                        ) {
                            scheduleSnapshotRefresh(400);
                        }
                    };
                    studioChannel = window.Echo.private(`obs.studio.${studioId}`)
                        .listen('.obs.studio.state', handleStudioStateEvent)
                        .listenForWhisper('obs.studio.state', handleStudioStateEvent)
                        .listen('.obs.command.acknowledged', handleCommandAckEvent)
                        .listenForWhisper('obs.command.acknowledged', handleCommandAckEvent);
                }

                // Unified Single-Browser Screen Collection Remote Controls
                let subscribedColToken = null;
                let subscribedColChannel = null;

                const stampCollectionPerf = (col) => {
                    if (col && typeof col === 'object') {
                        col._receivedAtPerf = performance.now();
                    }
                    return col;
                };
                collectionsData.forEach(stampCollectionPerf);

                const getActiveCollection = () => collectionsData.find((c) => Number(c.collection_id) === Number(activeCollectionId)) || collectionsData[0] || null;

                const upsertCollectionData = (updated) => {
                    if (!updated?.collection_id) return;
                    stampCollectionPerf(updated);
                    const idx = collectionsData.findIndex((c) => Number(c.collection_id) === Number(updated.collection_id));
                    if (idx >= 0) collectionsData[idx] = updated;
                    else collectionsData.push(updated);
                    if (!activeCollectionId) activeCollectionId = updated.collection_id;
                };

                const subscribeUnifiedEcho = (col) => {
                    if (!window.Echo || !col?.public_token || col.public_token === subscribedColToken) return;
                    if (subscribedColChannel && subscribedColToken) {
                        try { window.Echo.leave(`obs.collection.${subscribedColToken}`); } catch {}
                    }
                    subscribedColToken = col.public_token;
                    subscribedColChannel = window.Echo.channel(`obs.collection.${col.public_token}`)
                        .listen('.obs.collection.updated', (payload) => {
                            upsertCollectionData(payload);
                            if (Number(payload.collection_id) === Number(activeCollectionId)) {
                                renderUnifiedWorkspace();
                            }
                        });
                };

                const fmtStageClock = (secs) => {
                    const safe = Math.max(0, Math.floor(secs));
                    return `${String(Math.floor(safe / 60)).padStart(2, '0')}:${String(safe % 60).padStart(2, '0')}`;
                };

                const updateUnifiedTimerReadout = () => {
                    const col = getActiveCollection();
                    const el = document.getElementById('uni-timer-readout');
                    if (!col || !el) return;
                    const t = col.runtime_state?.timer || {};
                    const mode = t.mode || 'countdown';
                    const status = String(t.status || 'stopped');
                    const elapsedLocalMs = Math.max(0, performance.now() - Number(col._receivedAtPerf ?? performance.now()));

                    if (mode === 'clock') {
                        const d = new Date();
                        el.textContent = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
                    } else if (mode === 'countup') {
                        let elapsedMs = Number.isFinite(Number(t.elapsed_ms))
                            ? Number(t.elapsed_ms)
                            : Number(t.elapsed_base_seconds || 0) * 1000;
                        if (status === 'running') elapsedMs += elapsedLocalMs;
                        el.textContent = fmtStageClock(Math.floor(elapsedMs / 1000));
                    } else {
                        let remMs = Number.isFinite(Number(t.remaining_ms))
                            ? Number(t.remaining_ms)
                            : Number(t.remaining_seconds ?? t.duration_seconds ?? 0) * 1000;
                        if (status === 'running') remMs = Math.max(0, remMs - elapsedLocalMs);
                        el.textContent = fmtStageClock(Math.ceil(remMs / 1000));
                    }

                    const statusEl = document.getElementById('uni-timer-status-label');
                    if (statusEl) {
                        statusEl.textContent = mode === 'clock'
                            ? 'Live Clock'
                            : (status === 'running' ? 'Running' : (status === 'paused' ? 'Paused' : 'Stopped'));
                        statusEl.style.color = (status === 'running' || mode === 'clock')
                            ? 'var(--positive)'
                            : (status === 'paused' ? 'var(--gold-light)' : 'var(--ink-muted)');
                    }
                };

                let activeUtilityTab = 'overlay';

                const syncUtilityDock = () => {
                    const isScreens = controlMode === 'screens';
                    const tabBar = document.getElementById('utility-tab-bar');
                    const singleHeading = document.getElementById('utility-single-heading');
                    const overlayPanel = document.getElementById('legacy-overlay-push-panel');
                    const logsPanel = document.getElementById('recent-logs-panel');
                    const subMeta = document.getElementById('utility-sub-meta');

                    if (tabBar) tabBar.hidden = isScreens;
                    if (singleHeading) singleHeading.hidden = !isScreens;

                    const effectiveTab = isScreens ? 'logs' : activeUtilityTab;
                    if (overlayPanel) overlayPanel.hidden = effectiveTab !== 'overlay';
                    if (logsPanel) logsPanel.hidden = effectiveTab !== 'logs';
                    if (subMeta) subMeta.textContent = effectiveTab === 'overlay' ? 'Standalone screens' : 'Live telemetry';

                    document.querySelectorAll('[data-utility-tab]').forEach((b) => {
                        const active = b.dataset.utilityTab === effectiveTab;
                        b.classList.toggle('is-active', active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                };

                const renderUnifiedWorkspace = () => {
                    const isScreens = controlMode === 'screens';
                    document.querySelectorAll('[data-studio-mode]').forEach((btn) => {
                        btn.classList.toggle('is-active', btn.dataset.studioMode === controlMode);
                    });

                    const classicLeft = document.getElementById('classic-scenes-panel');
                    const uniLeft = document.getElementById('unified-collections-panel');
                    const classicCenter = document.getElementById('classic-sources-workspace');
                    const uniCenter = document.getElementById('unified-screen-workspace');
                    if (classicLeft) classicLeft.hidden = isScreens;
                    if (uniLeft) uniLeft.hidden = !isScreens;
                    if (classicCenter) classicCenter.hidden = isScreens;
                    if (uniCenter) uniCenter.hidden = !isScreens;
                    syncUtilityDock();
                    updateAgentOnboardingState();

                    const colSel = document.getElementById('unified-collection-select');
                    const col = getActiveCollection();
                    if (col) activeCollectionId = col.collection_id;

                    if (colSel) {
                        colSel.innerHTML = collectionsData.length === 0 ? '<option value="">No collections created yet</option>' : '';
                        collectionsData.forEach((c) => {
                            const o = document.createElement('option');
                            o.value = String(c.collection_id);
                            o.textContent = c.name;
                            o.selected = Number(c.collection_id) === Number(activeCollectionId);
                            colSel.appendChild(o);
                        });
                    }

                    const noColCard = document.getElementById('unified-no-collection-card');
                    const activeBody = document.getElementById('unified-active-stage-body');
                    if (!col) {
                        if (noColCard) noColCard.hidden = false;
                        if (activeBody) activeBody.hidden = true;
                        return;
                    }
                    if (noColCard) noColCard.hidden = true;
                    if (activeBody) activeBody.hidden = false;

                    subscribeUnifiedEcho(col);

                    const builderHref = endpoints.collectionBuilderTpl.replace('__COL__', col.collection_id);
                    const bLink = document.getElementById('unified-builder-link');
                    const cbLink = document.getElementById('unified-center-builder-link');
                    const sLink = document.getElementById('unified-open-stage-link');
                    if (bLink) bLink.href = builderHref;
                    if (cbLink) cbLink.href = builderHref;
                    if (sLink) sLink.href = col.stage_url || '#';

                    const activeView = (col.views || []).find((v) => Number(v.id) === Number(col.active_view_id)) || null;
                    document.getElementById('unified-active-col-label').textContent = col.name || 'Collection';
                    document.getElementById('unified-active-view-label').textContent = `Live View: ${activeView?.name || 'None'}`;
                    document.getElementById('unified-views-count').textContent = `${(col.views || []).length} views`;

                    const metaScene = document.getElementById('uni-meta-scene');
                    const metaSource = document.getElementById('uni-meta-source');
                    const metaRes = document.getElementById('uni-meta-res');
                    const metaTx = document.getElementById('uni-meta-tx');
                    if (metaScene) metaScene.textContent = col.obs_scene_name || 'Obsisstant Stage';
                    if (metaSource) metaSource.textContent = col.obs_source_name || 'Unified Screen';
                    if (metaRes) metaRes.textContent = `${col.width || 1920} × ${col.height || 1080}`;
                    if (metaTx) {
                        const txLabel = String(col.default_transition?.type || 'fade').replaceAll('_', ' ');
                        metaTx.textContent = `${txLabel.charAt(0).toUpperCase()}${txLabel.slice(1)} · ${col.default_transition?.duration_ms ?? 450}ms`;
                    }

                    const grid = document.getElementById('unified-views-grid');
                    if (grid) {
                        grid.innerHTML = '';
                        const stageW = Math.max(320, Number(col.width || 1920));
                        const stageH = Math.max(180, Number(col.height || 1080));

                        (col.views || []).forEach((view) => {
                            const isActive = Number(view.id) === Number(col.active_view_id);
                            const viewLayers = Array.isArray(view.layers) ? view.layers : [];
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = `view-pad-btn${isActive ? ' is-active' : ''}`;
                            btn.dataset.screenViewId = String(view.id);

                            const thumb = document.createElement('div');
                            thumb.className = `view-pad-thumb${view.background_mode === 'transparent' ? ' is-transparent' : ''}`;
                            if (view.background_mode === 'solid' && view.background_color) {
                                thumb.style.background = view.background_color;
                            } else if (view.background_mode === 'theme' && col.theme?.background_color) {
                                thumb.style.background = col.theme.background_color;
                            }

                            viewLayers
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
                                pill.className = 'on-air-pill';
                                pill.innerHTML = '<span class="on-air-dot" aria-hidden="true"></span>ON AIR';
                                thumb.appendChild(pill);
                            }

                            const info = document.createElement('div');
                            info.style.cssText = 'display:grid;gap:0.125rem;min-inline-size:0;';

                            const title = document.createElement('strong');
                            title.style.cssText = 'font-size:0.8125rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                            title.textContent = view.name;

                            const meta = document.createElement('span');
                            meta.className = 'dock-subtitle';
                            meta.style.fontSize = '0.6875rem';
                            const txName = String(view.transition_override?.type || col.default_transition?.type || 'fade').replaceAll('_', ' ');
                            meta.textContent = `${viewLayers.length} ${viewLayers.length === 1 ? 'layer' : 'layers'} · ${txName}`;

                            info.append(title, meta);
                            btn.append(thumb, info);
                            btn.addEventListener('click', async () => {
                                const txType = document.getElementById('unified-tx-type')?.value || 'default';
                                const txDurEl = document.getElementById('unified-tx-dur');
                                const txDur = txType === 'cut'
                                    ? 0
                                    : Math.min(3000, Math.max(80, Math.round(Number(txDurEl?.value || 450))));
                                if (txDurEl && txType !== 'default') txDurEl.value = String(txDur);
                                const res = await postJson(endpoints.collectionSwitchTpl.replace('__COL__', col.collection_id), {
                                    view_id: view.id,
                                    transition: txType === 'default' ? null : { type: txType, duration_ms: txDur },
                                });
                                upsertCollectionData(res.collection);
                                renderUnifiedWorkspace();
                                showToast(`Live View: ${view.name}`, 'success');
                            });
                            grid.appendChild(btn);
                        });
                    }

                    const ruleHint = document.getElementById('uni-timer-rule-hint');
                    if (ruleHint) {
                        const rule = activeView?.logic_rules || {};
                        if (rule.on_timer_end_action === 'switch_view' && rule.on_timer_end_target_view_id) {
                            const targetView = (col.views || []).find((v) => Number(v.id) === Number(rule.on_timer_end_target_view_id));
                            ruleHint.textContent = targetView
                                ? `Auto-rule: switches to "${targetView.name}" when countdown hits 00:00.`
                                : 'Auto-rule: switches view when countdown hits 00:00.';
                        } else if (rule.on_timer_end_action === 'show_alert') {
                            ruleHint.textContent = 'Auto-rule: displays alert banner when countdown hits 00:00.';
                        } else {
                            ruleHint.textContent = 'Configure auto-switch rules when timer hits 00:00 inside Canvas Builder.';
                        }
                    }

                    const sb = col.runtime_state?.scoreboard || {};
                    const setIfIdle = (id, val) => {
                        const inp = document.getElementById(id);
                        if (inp && document.activeElement !== inp) inp.value = val ?? '';
                    };
                    setIfIdle('uni-sb-a-name', sb.team_a_name || 'Team Alpha');
                    setIfIdle('uni-sb-b-name', sb.team_b_name || 'Team Omega');
                    setIfIdle('uni-sb-match', sb.match_label || '');
                    setIfIdle('uni-sb-round', sb.round_label || '');
                    document.getElementById('uni-sb-a-score').textContent = String(sb.team_a_score ?? 0);
                    document.getElementById('uni-sb-b-score').textContent = String(sb.team_b_score ?? 0);

                    const l3 = col.runtime_state?.lower_third || {};
                    setIfIdle('uni-l3-title', l3.title || '');
                    setIfIdle('uni-l3-sub', l3.subtitle || '');
                    const l3Status = document.getElementById('uni-l3-status');
                    if (l3Status) {
                        l3Status.textContent = l3.visible ? 'On Air' : 'Hidden';
                        l3Status.style.color = l3.visible ? 'var(--positive)' : 'var(--ink-muted)';
                    }

                    const alertState = col.runtime_state?.alert || {};
                    setIfIdle('uni-alert-title', alertState.title || '');
                    setIfIdle('uni-alert-msg', alertState.message || '');

                    const tickerBtn = document.getElementById('uni-ticker-toggle');
                    if (tickerBtn) {
                        const tickerOn = col.runtime_state?.ticker?.visible !== false;
                        tickerBtn.textContent = tickerOn ? 'Ticker: On' : 'Ticker: Off';
                        tickerBtn.classList.toggle('is-active', tickerOn);
                    }

                    const layerHost = document.getElementById('uni-layer-toggles');
                    if (layerHost) {
                        const hiddenSet = new Set(Array.isArray(col.runtime_state?.hidden_layers) ? col.runtime_state.hidden_layers : []);
                        layerHost.innerHTML = '';
                        const activeLayers = Array.isArray(activeView?.layers) ? activeView.layers : [];
                        if (activeLayers.length === 0) {
                            layerHost.innerHTML = '<span class="dock-subtitle">No layers on this view yet. Add elements in Canvas Builder.</span>';
                        } else {
                            activeLayers.forEach((layer) => {
                                const visible = layer.visible !== false && !hiddenSet.has(layer.id);
                                const pill = document.createElement('button');
                                pill.type = 'button';
                                pill.className = `s-btn s-btn-xs${visible ? ' is-active' : ''}`;
                                pill.textContent = `${visible ? 'On' : 'Off'}: ${layer.name || layer.type}`;
                                pill.addEventListener('click', () => sendUnifiedRuntime({
                                    toggle_layer: { layer_id: layer.id, visible: !visible },
                                }));
                                layerHost.appendChild(pill);
                            });
                        }
                    }

                    updateUnifiedTimerReadout();
                };

                const sendUnifiedRuntime = async (patch) => {
                    const col = getActiveCollection();
                    if (!col) return;
                    const res = await postJson(endpoints.collectionRuntimeTpl.replace('__COL__', col.collection_id), patch);
                    upsertCollectionData(res.collection);
                    renderUnifiedWorkspace();
                };

                document.querySelectorAll('[data-studio-mode]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const nextMode = btn.dataset.studioMode;
                        const res = await postJson(endpoints.controlMode, { control_mode: nextMode });
                        controlMode = res.control_mode || nextMode;
                        if (res.collection) {
                            upsertCollectionData(res.collection);
                            activeCollectionId = res.collection.collection_id;
                        }
                        renderUnifiedWorkspace();
                        if (controlMode !== 'screens') {
                            renderLayersInspector();
                        }
                        showToast(controlMode === 'screens' ? 'Switched to Unified Screen Stage Mode' : 'Switched to Classic OBS Scenes Mode', 'success');
                    });
                });

                document.querySelectorAll('[data-utility-tab]').forEach((tabBtn) => {
                    tabBtn.addEventListener('click', () => {
                        activeUtilityTab = tabBtn.dataset.utilityTab || 'overlay';
                        syncUtilityDock();
                    });
                });

                document.querySelectorAll('[data-source-tab]').forEach((tabBtn) => {
                    tabBtn.addEventListener('click', () => {
                        const target = tabBtn.dataset.sourceTab || 'properties';
                        document.querySelectorAll('[data-source-tab]').forEach((b) => {
                            const active = b === tabBtn;
                            b.classList.toggle('is-active', active);
                            b.setAttribute('aria-selected', active ? 'true' : 'false');
                        });
                        document.querySelectorAll('[data-source-tab-panel]').forEach((panel) => {
                            panel.hidden = panel.dataset.sourceTabPanel !== target;
                        });
                    });
                });

                document.getElementById('unified-collection-select')?.addEventListener('change', async (ev) => {
                    if (!ev.target.value) return;
                    activeCollectionId = Number(ev.target.value);
                    await postJson(endpoints.controlMode, { active_screen_collection_id: activeCollectionId });
                    renderUnifiedWorkspace();
                });

                document.getElementById('create-unified-collection-form')?.addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const name = document.getElementById('new-unified-col-name')?.value?.trim();
                    if (!name) return;
                    const openBuilder = Boolean(document.getElementById('new-unified-col-open-builder')?.checked);
                    const res = await postJson(endpoints.collectionsStore, {
                        name,
                        obs_scene_name: document.getElementById('new-unified-col-scene')?.value?.trim() || 'Obsisstant Stage',
                        obs_source_name: document.getElementById('new-unified-col-source')?.value?.trim() || 'Obsisstant - Unified Screen',
                        with_starter_views: Boolean(document.getElementById('new-unified-col-starter')?.checked),
                        activate: true,
                    });
                    if (res.collection) {
                        upsertCollectionData(res.collection);
                        activeCollectionId = res.collection.collection_id;
                        if (openBuilder) {
                            window.location.href = endpoints.collectionBuilderTpl.replace('__COL__', activeCollectionId);
                            return;
                        }
                        renderUnifiedWorkspace();
                        closeDialog(document.getElementById('create-unified-collection-dialog'));
                        showToast(`Created collection: ${res.collection.name}`, 'success');
                    }
                });

                document.getElementById('btn-copy-unified-url')?.addEventListener('click', async () => {
                    const col = getActiveCollection();
                    if (!col?.stage_url) return;
                    try {
                        await navigator.clipboard.writeText(col.stage_url);
                        showToast('Stage Browser URL copied', 'success');
                    } catch {
                        prompt('Copy OBS Browser Source URL:', col.stage_url);
                    }
                });

                document.getElementById('btn-push-unified-obs')?.addEventListener('click', async () => {
                    const col = getActiveCollection();
                    if (!col) return;
                    await postJson(endpoints.collectionPushTpl.replace('__COL__', col.collection_id), {});
                    showToast('Pushing Single Scene + Browser Source to OBS', 'success');
                });

                document.getElementById('audio-mixer-filter')?.addEventListener('input', renderAudioMixer);

                document.querySelectorAll('[data-uni-console-tab]').forEach((tabBtn) => {
                    tabBtn.addEventListener('click', () => {
                        const target = tabBtn.dataset.uniConsoleTab;
                        document.querySelectorAll('[data-uni-console-tab]').forEach((b) => {
                            b.setAttribute('aria-selected', b === tabBtn ? 'true' : 'false');
                        });
                        document.querySelectorAll('[data-uni-console-panel]').forEach((panel) => {
                            panel.hidden = panel.dataset.uniConsolePanel !== target;
                        });
                    });
                });

                document.querySelectorAll('[data-uni-timer-preset]').forEach((presetBtn) => {
                    presetBtn.addEventListener('click', () => {
                        const secs = Math.max(0, Number(presetBtn.dataset.uniTimerPreset || 300));
                        const secsInput = document.getElementById('uni-timer-seconds');
                        if (secsInput) secsInput.value = String(secs);
                        sendUnifiedRuntime({ timer: { action: 'set', duration_seconds: secs } });
                    });
                });

                document.getElementById('uni-timer-start')?.addEventListener('click', () => sendUnifiedRuntime({ timer: { action: 'start' } }));
                document.getElementById('uni-timer-pause')?.addEventListener('click', () => sendUnifiedRuntime({ timer: { action: 'pause' } }));
                document.getElementById('uni-timer-reset')?.addEventListener('click', () => sendUnifiedRuntime({ timer: { action: 'reset' } }));
                document.getElementById('uni-timer-plus')?.addEventListener('click', () => sendUnifiedRuntime({ timer: { action: 'add_seconds', seconds: 60 } }));
                document.getElementById('uni-timer-minus')?.addEventListener('click', () => sendUnifiedRuntime({ timer: { action: 'add_seconds', seconds: -60 } }));
                document.getElementById('uni-timer-set')?.addEventListener('click', () => {
                    const secs = Math.max(0, Number(document.getElementById('uni-timer-seconds')?.value || 300));
                    sendUnifiedRuntime({ timer: { action: 'set', duration_seconds: secs } });
                });

                document.getElementById('uni-sb-a-minus')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { delta_a: -1 } }));
                document.getElementById('uni-sb-a-plus')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { delta_a: 1 } }));
                document.getElementById('uni-sb-b-minus')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { delta_b: -1 } }));
                document.getElementById('uni-sb-b-plus')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { delta_b: 1 } }));
                document.getElementById('uni-sb-swap')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { swap: true } }));
                document.getElementById('uni-sb-reset')?.addEventListener('click', () => sendUnifiedRuntime({ scoreboard: { reset_scores: true } }));
                document.getElementById('uni-sb-save')?.addEventListener('click', () => sendUnifiedRuntime({
                    scoreboard: {
                        team_a_name: document.getElementById('uni-sb-a-name')?.value || 'Team Alpha',
                        team_b_name: document.getElementById('uni-sb-b-name')?.value || 'Team Omega',
                        match_label: document.getElementById('uni-sb-match')?.value || '',
                        round_label: document.getElementById('uni-sb-round')?.value || '',
                    },
                }));

                document.getElementById('uni-l3-show')?.addEventListener('click', () => sendUnifiedRuntime({
                    lower_third: {
                        visible: true,
                        title: document.getElementById('uni-l3-title')?.value || '',
                        subtitle: document.getElementById('uni-l3-sub')?.value || '',
                    },
                }));
                document.getElementById('uni-l3-hide')?.addEventListener('click', () => sendUnifiedRuntime({ lower_third: { visible: false } }));
                document.getElementById('uni-ticker-toggle')?.addEventListener('click', () => {
                    const col = getActiveCollection();
                    const currentlyOn = col?.runtime_state?.ticker?.visible !== false;
                    sendUnifiedRuntime({ ticker: { visible: !currentlyOn } });
                });
                document.getElementById('uni-alert-show')?.addEventListener('click', () => sendUnifiedRuntime({
                    alert: {
                        visible: true,
                        title: document.getElementById('uni-alert-title')?.value?.trim() || 'Live Update',
                        message: document.getElementById('uni-alert-msg')?.value?.trim() || '',
                    },
                }));
                document.getElementById('uni-alert-hide')?.addEventListener('click', () => sendUnifiedRuntime({ alert: { visible: false } }));

                setInterval(updateUnifiedTimerReadout, 250);
                renderUnifiedWorkspace();
                renderAll();

                // Only auto-open the Install Agent dialog for brand-new workspaces in Classic OBS mode that have never connected any studio
                if (
                    studioStatus === 'offline'
                    && !agentReachable
                    && !hasAnyConnectedStudio
                    && controlMode !== 'screens'
                    && !isInstallDialogDismissed()
                    && installDialog
                ) {
                    openDialog(installDialog);
                    startInstallDialogPolling();
                }
            })();
        </script>
    </body>
</html>
