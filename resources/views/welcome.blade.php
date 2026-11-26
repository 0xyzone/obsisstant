<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ config('app.name', 'Obsisstant') }} · OBS Broadcast Relay &amp; Overlay Studio</title>
        <meta name="description" content="Real-time wireless OBS Studio deck, hardware relay agent, and live Browser Source overlays synced via Laravel Reverb.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:200,300,400,500,600&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;

                /* Matte obsidian surfaces */
                --bg-canvas: #070706;
                --bg-chrome: #0B0B0A;
                --bg-card: #121210;
                --bg-raised: #1B1A17;
                --bg-slate: #292C31;
                --bg-bronze: #2D1F11;

                /* Brushed metallic gold accent tokens */
                --gold-dark: #89612B;
                --gold-satin: #AC8B56;
                --gold-accent: #CDAD69;
                --gold-light: #F4E185;

                /* Hairlines & text inks */
                --hairline: rgba(205, 173, 105, 0.14);
                --hairline-strong: rgba(205, 173, 105, 0.34);
                --hairline-neutral: rgba(255, 255, 255, 0.08);
                --ink-primary: #FAF7EE;
                --ink-secondary: #C8C0AF;
                --ink-muted: #8E8777;

                /* Status inks */
                --status-live: #4CC38A;
                --status-live-ink: #9EE6C3;
                --status-offline: #E5484D;
                --status-offline-ink: #F4A3A6;

                /* Typography & motion */
                --font-sans: 'Poppins', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                --ease-out: cubic-bezier(0.22, 1, 0.36, 1);

                /* Strict nested radii & 4px spacing scale */
                --radius-tag: 0.375rem;   /* 6px */
                --radius-ctl: 0.5rem;     /* 8px */
                --radius-inner: 0.75rem;  /* 12px */
                --radius-card: 1rem;      /* 16px */
                --radius-frame: 1.25rem;  /* 20px */

                --space-1: 0.25rem;   /* 4px */
                --space-2: 0.5rem;    /* 8px */
                --space-3: 0.75rem;   /* 12px */
                --space-4: 1rem;      /* 16px */
                --space-5: 1.25rem;   /* 20px */
                --space-6: 1.5rem;    /* 24px */
                --space-8: 2rem;      /* 32px */
                --space-12: 3rem;     /* 48px */
                --space-16: 4rem;     /* 64px */
                --space-20: 5rem;     /* 80px */
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                font-family: var(--font-sans);
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            @media (prefers-reduced-motion: reduce) {
                html {
                    scroll-behavior: auto;
                }
            }

            body {
                margin: 0;
                min-block-size: 100dvb;
                background:
                    radial-gradient(circle at 12% 0%, rgba(205, 173, 105, 0.16), transparent 42%),
                    radial-gradient(circle at 88% 14%, rgba(244, 225, 133, 0.08), transparent 40%),
                    radial-gradient(circle at 50% 82%, rgba(205, 173, 105, 0.07), transparent 48%),
                    var(--bg-canvas);
                color: var(--ink-primary);
                font-size: 0.9375rem;
                font-weight: 400;
                line-height: 1.55;
                overflow-x: clip;
            }

            .skip-link {
                position: fixed;
                inset-inline-start: var(--space-4);
                inset-block-start: -100%;
                z-index: 60;
                padding: var(--space-2) var(--space-4);
                border-radius: var(--radius-ctl);
                background-color: var(--gold-accent);
                color: #070706;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
            }

            .skip-link:focus {
                inset-block-start: var(--space-4);
            }

            :is(a, button, input, select, textarea, summary, [tabindex]):focus-visible {
                outline: 2px solid var(--gold-light);
                outline-offset: 2px;
            }

            [hidden] {
                display: none !important;
            }

            /* Main Page Container */
            .container {
                inline-size: min(100%, 76rem);
                margin-inline: auto;
                padding-inline: max(var(--space-4), env(safe-area-inset-left)) max(var(--space-4), env(safe-area-inset-right));
            }

            /* Sticky Frosted Topbar Navigation */
            .site-header {
                position: sticky;
                inset-block-start: 0;
                z-index: 40;
                background: rgba(12, 11, 9, 0.72);
                -webkit-backdrop-filter: blur(20px) saturate(145%);
                backdrop-filter: blur(20px) saturate(145%);
                border-block-end: 1px solid rgba(205, 173, 105, 0.24);
                box-shadow: inset 0 -1px 0 rgba(244, 225, 133, 0.08), 0 12px 32px -18px rgba(0, 0, 0, 0.85);
            }

            .header-inner {
                inline-size: min(100%, 76rem);
                margin-inline: auto;
                min-block-size: 4rem;
                padding-inline: max(var(--space-4), env(safe-area-inset-left)) max(var(--space-4), env(safe-area-inset-right));
                padding-block: var(--space-2);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-4);
            }

            .header-inner > * {
                flex-shrink: 0;
                white-space: nowrap;
            }

            .brand-cluster {
                display: inline-flex;
                align-items: center;
                gap: var(--space-2);
            }

            .brand-lockup {
                display: inline-flex;
                align-items: center;
                gap: var(--space-2);
                color: var(--ink-primary);
                text-decoration: none;
                font-size: 1rem;
                font-weight: 600;
                letter-spacing: -0.01em;
                transition: color 140ms var(--ease-out);
            }

            .brand-lockup:hover {
                color: var(--gold-light);
            }

            .obs-brand-version-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.125rem var(--space-2);
                border-radius: 9999px;
                border: 1px solid var(--hairline-strong);
                background-color: rgba(205, 173, 105, 0.12);
                color: var(--gold-light);
                font-variant-numeric: tabular-nums;
                font-size: 0.6875rem;
                font-weight: 500;
                line-height: 1.35;
                text-decoration: none;
                transition: border-color 140ms var(--ease-out), background-color 140ms var(--ease-out);
            }

            .obs-brand-version-pill:hover {
                border-color: var(--gold-light);
                background-color: rgba(205, 173, 105, 0.2);
            }

            .header-end {
                display: flex;
                align-items: center;
                gap: var(--space-3);
            }

            /* Site Navigation (Narrow Popover Default -> Wide Inline) */
            .site-nav {
                display: flex;
                align-items: center;
                gap: var(--space-2);
            }

            .menu-button {
                anchor-name: --menu-button;
                display: inline-flex;
                align-items: center;
                gap: var(--space-2);
                min-block-size: 2.375rem;
                padding: var(--space-1) var(--space-3);
                border-radius: var(--radius-ctl);
                border: 1px solid var(--hairline-strong);
                background-color: var(--bg-raised);
                color: var(--ink-primary);
                font: inherit;
                font-size: 0.8125rem;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out);
            }

            .menu-button:hover {
                background-color: #23221E;
                border-color: var(--gold-accent);
            }

            .site-menu {
                position: fixed;
                inset: auto;
                /* Static fallback offset for browsers without CSS Anchor Positioning */
                inset-block-start: 4.5rem;
                inset-block-start: anchor(--menu-button bottom);
                inset-inline-end: max(var(--space-4), env(safe-area-inset-right));
                inline-size: min(82dvw, 19rem);
                max-inline-size: calc(100dvw - 2rem);
                block-size: fit-content;
                max-block-size: calc(100dvh - 6rem);
                overflow: auto;
                margin: var(--space-2) 0 0;
                padding: var(--space-2);
                list-style: none;
                border-radius: var(--radius-card);
                background: rgba(18, 18, 16, 0.94);
                -webkit-backdrop-filter: blur(18px);
                backdrop-filter: blur(18px);
                border: 1px solid var(--hairline-strong);
                box-shadow: 0 18px 40px -12px rgba(0, 0, 0, 0.78);
                opacity: 0;
                transition-property: opacity, transform, display, overlay;
                transition-duration: 180ms;
                transition-timing-function: var(--ease-out);
                /* Declared separately so older browsers still run opacity/transform */
                transition-behavior: allow-discrete;
            }

            @media (prefers-reduced-motion: no-preference) {
                .site-menu {
                    transform: translateY(-6px);
                }
            }

            .site-menu:is(:popover-open, .\:popover-open):where(:not([data-closing])) {
                display: grid;
                gap: var(--space-1);
                opacity: 1;

                @starting-style {
                    opacity: 0;
                }

                @media (prefers-reduced-motion: no-preference) {
                    transform: translateY(0);

                    @starting-style {
                        transform: translateY(-6px);
                    }
                }
            }

            .site-menu::backdrop {
                background-color: rgba(7, 7, 6, 0);
                transition-property: background-color, display, overlay;
                transition-duration: 180ms;
                transition-timing-function: var(--ease-out);
                transition-behavior: allow-discrete;
            }

            .site-menu:is(:popover-open, .\:popover-open):where(:not([data-closing]))::backdrop {
                background-color: rgba(7, 7, 6, 0.45);

                @starting-style {
                    background-color: rgba(7, 7, 6, 0);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .site-menu,
                .site-menu::backdrop {
                    transition-duration: 90ms;
                }
            }

            .site-menu a {
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-block-size: 2.375rem;
                padding: var(--space-2) var(--space-3);
                border-radius: var(--radius-ctl);
                color: var(--ink-secondary);
                text-decoration: none;
                font-size: 0.8125rem;
                font-weight: 500;
                transition: background-color 140ms var(--ease-out), color 140ms var(--ease-out);
            }

            .site-menu a:hover {
                background-color: var(--bg-raised);
                color: var(--ink-primary);
            }

            .site-menu a[aria-current="page"] {
                color: var(--gold-light);
                background-color: rgba(205, 173, 105, 0.1);
                border: 1px solid var(--hairline);
            }

            .site-menu .nav-cta {
                background-image: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border: 1px solid rgba(244, 225, 133, 0.45);
                box-shadow: inset 0 1px 0 rgba(255, 250, 224, 0.42);
            }

            .site-menu .nav-cta:hover {
                background-image: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%);
                color: #070706;
            }

            .site-header.is-wide .menu-button {
                display: none;
            }

            .site-header.is-wide .site-menu {
                position: static;
                display: flex;
                align-items: center;
                gap: var(--space-1);
                inline-size: auto;
                max-inline-size: none;
                block-size: auto;
                max-block-size: none;
                overflow: visible;
                margin: 0;
                padding: 0;
                border: 0;
                background: transparent;
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
                box-shadow: none;
                opacity: 1;
                transform: none;
                transition: none;
            }

            .site-header.is-wide .site-menu::backdrop {
                display: none;
            }

            .site-header.is-wide .site-menu a {
                padding: var(--space-2) var(--space-3);
            }

            .site-header.is-wide .site-menu a[aria-current="page"] {
                background-color: transparent;
                border-color: transparent;
                box-shadow: inset 0 -2px 0 var(--gold-accent);
                border-radius: var(--radius-ctl) var(--radius-ctl) 0 0;
                color: var(--ink-primary);
            }

            .site-header.is-measuring * {
                transition: none !important;
            }

            @media (max-width: 32rem) {
                .brand-lockup span {
                    font-size: 0.9375rem;
                }
            }

            /* Hero Entrance Stagger (max 5 items, 45ms stagger, prefers-reduced-motion gated) */
            @media (prefers-reduced-motion: no-preference) {
                @keyframes obs-rise-in {
                    from {
                        opacity: 0;
                        translate: 0 10px;
                    }
                    to {
                        opacity: 1;
                        translate: 0 0;
                    }
                }

                [data-enter] {
                    animation: obs-rise-in 420ms var(--ease-out) backwards;
                    animation-delay: calc(var(--enter-order, 0) * 45ms);
                }
            }

            /* Scroll Reveal Choreography (Native CSS View Timeline + JS Observer fallback) */
            @media (prefers-reduced-motion: no-preference) {
                @supports ((animation-timeline: view()) and (animation-range: entry)) {
                    @keyframes obs-scroll-entry {
                        from {
                            opacity: 0;
                            translate: 0 16px;
                        }
                        to {
                            opacity: 1;
                            translate: 0 0;
                        }
                    }

                    [data-reveal] {
                        animation: obs-scroll-entry auto linear both;
                        animation-timeline: view(block);
                        animation-range: entry 0% entry 85%;
                    }
                }

                /* Fallback state applied strictly by JS only when View Timeline is unsupported */
                [data-reveal].is-pending {
                    opacity: 0;
                    translate: 0 16px;
                }

                [data-reveal].is-revealed {
                    opacity: 1;
                    translate: 0 0;
                    transition: opacity 420ms var(--ease-out), translate 420ms var(--ease-out);
                }
            }

            /* Hero Layout */
            .hero-section {
                padding-block: clamp(var(--space-8), 5vw, var(--space-16)) clamp(var(--space-8), 4vw, var(--space-12));
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: var(--space-8);
                align-items: center;
            }

            @media (min-width: 64rem) {
                .hero-section {
                    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
                    gap: var(--space-12);
                }
            }

            .hero-kicker {
                display: inline-flex;
                align-items: center;
                gap: var(--space-2);
                font-size: 0.8125rem;
                font-weight: 500;
                color: var(--gold-accent);
            }

            .hero-title {
                margin: var(--space-3) 0 0;
                font-size: clamp(2.625rem, 1.5rem + 3.8vw, 5rem);
                font-weight: 200;
                line-height: 1.03;
                letter-spacing: -0.03em;
                text-wrap: balance;
                color: var(--ink-primary);
            }

            .hero-title .accent {
                background: linear-gradient(135deg, #FFF7CF 0%, #F4E185 38%, #CDAD69 72%, #FFF0AE 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                font-weight: 300;
            }

            .hero-lead {
                margin: var(--space-4) 0 0;
                max-inline-size: 44ch;
                font-size: 1rem;
                font-weight: 300;
                line-height: 1.6;
                text-wrap: pretty;
                color: var(--ink-secondary);
            }

            .hero-actions {
                margin-block-start: var(--space-6);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: var(--space-3);
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: var(--space-2);
                min-block-size: 2.75rem;
                padding: var(--space-2) var(--space-5);
                border-radius: var(--radius-ctl);
                font: inherit;
                font-size: 0.875rem;
                font-weight: 500;
                text-decoration: none;
                cursor: pointer;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out), color 140ms var(--ease-out), transform 120ms var(--ease-out);
            }

            @media (prefers-reduced-motion: no-preference) {
                .btn:active,
                .pad-key:active,
                .preset-tab:active {
                    transform: scale(0.985);
                }
            }

            .btn-primary {
                background-image: linear-gradient(180deg, #F6E496 0%, #D9B768 48%, #B89143 100%);
                color: #070706;
                font-weight: 600;
                border: 1px solid rgba(244, 225, 133, 0.68);
                box-shadow: inset 0 1px 0 rgba(255, 252, 232, 0.7), 0 10px 26px -10px rgba(205, 173, 105, 0.55);
            }

            .btn-primary:hover {
                background-image: linear-gradient(180deg, #FFF0AE 0%, #E7C97B 48%, #C9A04E 100%);
                box-shadow: inset 0 1px 0 rgba(255, 254, 240, 0.85), 0 12px 30px -10px rgba(244, 225, 133, 0.7);
            }

            .btn-secondary {
                background: rgba(24, 23, 20, 0.68);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                color: var(--ink-primary);
                border: 1px solid var(--hairline-strong);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.14);
            }

            .btn-secondary:hover {
                background: rgba(34, 32, 27, 0.82);
                border-color: var(--gold-light);
            }

            /* Quiet Tabular Studio Metrics Strip */
            .hero-metrics {
                margin-block-start: var(--space-8);
                padding-block-start: var(--space-5);
                border-block-start: 1px solid var(--hairline);
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: var(--space-4);
            }

            .metric-value {
                display: block;
                font-variant-numeric: tabular-nums;
                font-size: clamp(1.25rem, 1.05rem + 0.6vw, 1.625rem);
                font-weight: 600;
                line-height: 1.15;
                color: var(--ink-primary);
            }

            .metric-item:first-child .metric-value {
                color: var(--gold-light);
            }

            .metric-label {
                display: block;
                margin-block-start: var(--space-1);
                font-size: 0.75rem;
                color: var(--ink-muted);
            }

            /* Interactive Coded OBS Studio Deck Preview */
            .console-frame {
                background: linear-gradient(165deg, rgba(26, 24, 20, 0.76) 0%, rgba(12, 11, 9, 0.86) 100%);
                -webkit-backdrop-filter: blur(20px) saturate(145%);
                backdrop-filter: blur(20px) saturate(145%);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-frame);
                padding: var(--space-3);
                display: grid;
                gap: var(--space-3);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.18), 0 24px 52px -24px rgba(0, 0, 0, 0.9);
            }

            .console-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                padding-inline: var(--space-1);
            }

            .console-title {
                font-size: 0.8125rem;
                font-weight: 500;
                color: var(--ink-secondary);
            }

            .console-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.125rem var(--space-2);
                border-radius: var(--radius-tag);
                background-color: var(--bg-raised);
                border: 1px solid var(--hairline);
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                font-weight: 500;
                color: var(--gold-light);
            }

            .monitor-stage {
                position: relative;
                aspect-ratio: 16 / 9;
                border-radius: var(--radius-inner);
                border: 1px solid var(--hairline);
                background-color: #0D0D0B;
                padding: var(--space-4);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
            }

            .monitor-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-2);
            }

            .monitor-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.1875rem var(--space-2);
                border-radius: var(--radius-tag);
                background: rgba(11, 11, 10, 0.82);
                -webkit-backdrop-filter: blur(12px);
                backdrop-filter: blur(12px);
                border: 1px solid var(--hairline-neutral);
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                font-weight: 500;
                color: var(--ink-secondary);
            }

            .monitor-chip.is-pgm {
                border-color: rgba(205, 173, 105, 0.35);
                color: var(--gold-light);
            }

            .monitor-scene-stage {
                display: grid;
                gap: var(--space-1);
                padding-block: var(--space-2);
            }

            .monitor-scene-meta {
                font-size: 0.75rem;
                color: var(--gold-accent);
                font-weight: 500;
            }

            .monitor-scene-heading {
                margin: 0;
                font-size: clamp(1.125rem, 0.95rem + 0.6vw, 1.5rem);
                font-weight: 600;
                line-height: 1.2;
                letter-spacing: -0.015em;
                color: var(--ink-primary);
            }

            /* Live Overlay HUD inside Monitor with Discrete Entry/Exit Transition */
            .monitor-hud {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                padding: var(--space-2) var(--space-3);
                border-radius: var(--radius-ctl);
                background: rgba(18, 18, 16, 0.88);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border: 1px solid var(--hairline-strong);
                opacity: 1;
                transition-property: opacity, transform, display;
                transition-duration: 220ms;
                transition-timing-function: var(--ease-out);
                transition-behavior: allow-discrete;
            }

            @media (prefers-reduced-motion: no-preference) {
                .monitor-hud {
                    transform: translateY(0);
                }

                @starting-style {
                    .monitor-hud {
                        opacity: 0;
                        transform: translateY(6px);
                    }
                }
            }

            @starting-style {
                .monitor-hud {
                    opacity: 0;
                }
            }

            .monitor-hud:where([hidden], [data-closing]) {
                opacity: 0;
            }

            @media (prefers-reduced-motion: no-preference) {
                .monitor-hud:where([hidden], [data-closing]) {
                    transform: translateY(6px);
                }
            }

            .monitor-hud[hidden] {
                display: none;
            }

            .hud-guest-name {
                font-size: 0.8125rem;
                font-weight: 600;
                color: var(--gold-light);
            }

            .hud-guest-role {
                font-size: 0.75rem;
                color: var(--ink-muted);
            }

            .hud-state-tag {
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                font-weight: 500;
                color: var(--status-live-ink);
            }

            /* Switcher Deck Keys inside Hero Preview */
            .deck-pad-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: var(--space-2);
            }

            @media (min-width: 28rem) {
                .deck-pad-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .pad-key {
                min-block-size: 2.5rem;
                padding: var(--space-2) var(--space-3);
                border-radius: var(--radius-ctl);
                border: 1px solid var(--hairline);
                background-color: var(--bg-raised);
                color: var(--ink-secondary);
                font: inherit;
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
                text-align: start;
                display: grid;
                gap: 0.125rem;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out), color 140ms var(--ease-out), transform 120ms var(--ease-out);
            }

            .pad-key:hover {
                border-color: var(--hairline-strong);
                color: var(--ink-primary);
            }

            .pad-key[aria-pressed="true"] {
                background-color: var(--bg-bronze);
                border-color: var(--gold-accent);
                color: var(--gold-light);
            }

            .pad-sub {
                font-size: 0.6875rem;
                color: var(--ink-muted);
            }

            .pad-key[aria-pressed="true"] .pad-sub {
                color: var(--gold-accent);
            }

            /* Section Hairline Divider & Band Framing */
            .band {
                padding-block: clamp(var(--space-12), 6vw, var(--space-20));
                border-block-start: 1px solid var(--hairline);
            }

            .band-header {
                display: grid;
                gap: var(--space-2);
                max-inline-size: 46rem;
                margin-block-end: var(--space-8);
            }

            .band-eyebrow {
                font-size: 0.8125rem;
                font-weight: 500;
                color: var(--gold-accent);
            }

            .band-heading {
                margin: 0;
                font-size: clamp(2rem, 1.3rem + 2.2vw, 3.25rem);
                font-weight: 200;
                line-height: 1.08;
                letter-spacing: -0.025em;
                text-wrap: balance;
                color: var(--ink-primary);
            }

            .band-copy {
                margin: 0;
                color: var(--ink-secondary);
                max-inline-size: 58ch;
            }

            /* Asymmetric Capabilities Split (5/7) — Zero 4-Card Bento Symmetry */
            .architecture-split {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: var(--space-6);
                align-items: start;
            }

            @media (min-width: 60rem) {
                .architecture-split {
                    grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                    gap: var(--space-8);
                }
            }

            .capability-list {
                display: grid;
                gap: var(--space-4);
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .capability-item {
                padding: var(--space-4) var(--space-5);
                border-radius: var(--radius-card);
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                display: grid;
                gap: var(--space-1);
                transition: border-color 140ms var(--ease-out);
            }

            .capability-item:hover {
                border-color: var(--hairline-strong);
            }

            .capability-title {
                margin: 0;
                display: flex;
                align-items: center;
                gap: var(--space-2);
                font-size: 1rem;
                font-weight: 500;
                color: var(--ink-primary);
            }

            .capability-desc {
                margin: 0;
                font-size: 0.875rem;
                color: var(--ink-secondary);
            }

            .pipeline-panel {
                background: linear-gradient(165deg, rgba(24, 22, 18, 0.74) 0%, rgba(12, 11, 9, 0.86) 100%);
                -webkit-backdrop-filter: blur(18px) saturate(145%);
                backdrop-filter: blur(18px) saturate(145%);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-card);
                padding: var(--space-6);
                display: grid;
                gap: var(--space-5);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 22px 48px -24px rgba(0, 0, 0, 0.88);
            }

            .pipeline-head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                padding-block-end: var(--space-4);
                border-block-end: 1px solid var(--hairline);
            }

            .pipeline-title {
                margin: 0;
                font-size: 1rem;
                font-weight: 500;
                color: var(--ink-primary);
            }

            .pipeline-nodes {
                display: grid;
                gap: var(--space-3);
            }

            .pipeline-row {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: var(--space-3);
                padding: var(--space-3) var(--space-4);
                border-radius: var(--radius-inner);
                background-color: var(--bg-raised);
                border: 1px solid var(--hairline);
            }

            .pipeline-step-num {
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--gold-light);
            }

            .pipeline-step-name {
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--ink-primary);
            }

            .pipeline-step-sub {
                display: block;
                font-size: 0.75rem;
                color: var(--ink-muted);
            }

            .pipeline-tag {
                padding: 0.125rem var(--space-2);
                border-radius: var(--radius-tag);
                background-color: var(--bg-card);
                border: 1px solid var(--hairline);
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                color: var(--ink-secondary);
            }

            /* Overlay Preset Explorer (4/8 Asymmetric Split) */
            .presets-split {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: var(--space-6);
                align-items: stretch;
            }

            @media (min-width: 56rem) {
                .presets-split {
                    grid-template-columns: minmax(0, 4fr) minmax(0, 8fr);
                }
            }

            .preset-rail {
                display: grid;
                gap: var(--space-2);
                align-content: start;
            }

            .preset-tab {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                inline-size: 100%;
                min-block-size: 2.75rem;
                padding: var(--space-2) var(--space-4);
                border-radius: var(--radius-ctl);
                border: 1px solid var(--hairline);
                background-color: var(--bg-card);
                color: var(--ink-secondary);
                font: inherit;
                font-size: 0.875rem;
                font-weight: 500;
                text-align: start;
                cursor: pointer;
                transition: background-color 140ms var(--ease-out), border-color 140ms var(--ease-out), color 140ms var(--ease-out), transform 120ms var(--ease-out);
            }

            .preset-tab:hover {
                background-color: var(--bg-raised);
                border-color: var(--hairline-strong);
                color: var(--ink-primary);
            }

            .preset-tab[aria-selected="true"] {
                background-color: var(--bg-raised);
                border-color: var(--gold-accent);
                color: var(--gold-light);
                box-shadow: inset 3px 0 0 var(--gold-accent);
            }

            .preset-tab-type {
                font-size: 0.75rem;
                font-weight: 400;
                color: var(--ink-muted);
            }

            .preset-stage-card {
                background: linear-gradient(165deg, rgba(24, 22, 18, 0.74) 0%, rgba(12, 11, 9, 0.86) 100%);
                -webkit-backdrop-filter: blur(18px) saturate(145%);
                backdrop-filter: blur(18px) saturate(145%);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-frame);
                padding: var(--space-4);
                display: grid;
                gap: var(--space-4);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 22px 48px -24px rgba(0, 0, 0, 0.88);
            }

            .preset-viewport {
                position: relative;
                min-block-size: 17rem;
                border-radius: var(--radius-inner);
                border: 1px solid var(--hairline);
                background:
                    radial-gradient(circle at 50% 0%, rgba(205, 173, 105, 0.1), transparent 55%),
                    #0D0D0B;
                padding: clamp(var(--space-4), 3vw, var(--space-6));
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
            }

            /* Features Showcase Grid & FAQ Accordion */
            .showcase-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 17.5rem), 1fr));
                gap: var(--space-4);
            }

            .showcase-card {
                background: linear-gradient(165deg, rgba(24, 22, 18, 0.72) 0%, rgba(12, 11, 9, 0.84) 100%);
                -webkit-backdrop-filter: blur(18px) saturate(140%);
                backdrop-filter: blur(18px) saturate(140%);
                border: 1px solid var(--hairline);
                border-radius: var(--radius-card);
                padding: var(--space-5);
                display: grid;
                gap: 0.45rem;
                align-content: start;
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.14);
                transition: border-color 160ms var(--ease-out), transform 160ms var(--ease-out);
            }

            .showcase-card:hover {
                border-color: var(--hairline-strong);
                transform: translateY(-2px);
            }

            .showcase-icon {
                display: inline-grid;
                place-items: center;
                inline-size: 2.25rem;
                block-size: 2.25rem;
                border-radius: 0.65rem;
                background: rgba(205, 173, 105, 0.14);
                border: 1px solid rgba(244, 225, 133, 0.34);
                color: var(--gold-light);
                margin-block-end: 0.25rem;
            }

            .faq-list {
                display: grid;
                gap: var(--space-3);
            }

            .faq-item {
                background: rgba(18, 17, 14, 0.74);
                -webkit-backdrop-filter: blur(18px) saturate(140%);
                backdrop-filter: blur(18px) saturate(140%);
                border: 1px solid var(--hairline);
                border-radius: var(--radius-inner);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.12);
                overflow: hidden;
            }

            .faq-item[open] {
                border-color: var(--hairline-strong);
            }

            .faq-item summary {
                list-style: none;
                cursor: pointer;
                padding: 1rem 1.25rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                font-size: 0.95rem;
                font-weight: 500;
                color: var(--ink-primary);
            }

            .faq-item summary::-webkit-details-marker {
                display: none;
            }

            .faq-plus {
                color: var(--gold-light);
                font-size: 1.15rem;
                font-weight: 400;
                transition: transform 180ms var(--ease-out);
                flex-shrink: 0;
            }

            .faq-item[open] .faq-plus {
                transform: rotate(45deg);
            }

            .faq-answer {
                padding: 0 1.25rem 1rem;
                margin: 0;
                font-size: 0.875rem;
                color: var(--ink-secondary);
                max-inline-size: 70ch;
            }

            .preset-viewport-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-2);
            }

            .stage-tag {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.1875rem var(--space-2);
                border-radius: var(--radius-tag);
                background-color: var(--bg-raised);
                border: 1px solid var(--hairline);
                font-size: 0.75rem;
                font-weight: 500;
                color: var(--ink-secondary);
            }

            .stage-tag.is-gold {
                border-color: var(--hairline-strong);
                color: var(--gold-light);
            }

            .preset-copy-block {
                display: grid;
                gap: var(--space-2);
            }

            .preset-headline {
                margin: 0;
                font-size: clamp(1.375rem, 1.05rem + 1.1vw, 2rem);
                font-weight: 600;
                line-height: 1.15;
                letter-spacing: -0.02em;
                color: var(--ink-primary);
            }

            .preset-subheadline {
                margin: 0;
                font-size: 0.9375rem;
                color: var(--ink-secondary);
                max-inline-size: 48ch;
            }

            .preset-lower-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                padding: var(--space-3) var(--space-4);
                border-radius: var(--radius-ctl);
                background: rgba(18, 18, 16, 0.86);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border: 1px solid var(--hairline-strong);
            }

            .preset-meta-footer {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-3);
                padding-inline: var(--space-1);
                font-size: 0.8125rem;
                color: var(--ink-muted);
            }

            /* Closing Action Panel & Slim Footer */
            .closing-panel {
                background: linear-gradient(165deg, rgba(28, 25, 19, 0.78) 0%, rgba(12, 11, 9, 0.88) 100%);
                -webkit-backdrop-filter: blur(18px) saturate(145%);
                backdrop-filter: blur(18px) saturate(145%);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-card);
                padding: clamp(var(--space-6), 4vw, var(--space-12));
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-6);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.18), 0 24px 52px -26px rgba(0, 0, 0, 0.9);
            }

            .closing-title {
                margin: 0;
                font-size: clamp(1.75rem, 1.2rem + 1.8vw, 2.75rem);
                font-weight: 200;
                line-height: 1.12;
                letter-spacing: -0.025em;
                text-wrap: balance;
                color: var(--ink-primary);
            }

            .closing-sub {
                margin: var(--space-2) 0 0;
                color: var(--ink-secondary);
                max-inline-size: 50ch;
            }

            .site-footer {
                padding-block: var(--space-8);
                border-block-start: 1px solid var(--hairline);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-4);
                font-size: 0.8125rem;
                color: var(--ink-muted);
            }

            .footer-links {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: var(--space-4);
            }

            .footer-links a {
                color: var(--ink-secondary);
                text-decoration: none;
                transition: color 140ms var(--ease-out);
            }

            .footer-links a:hover {
                color: var(--gold-light);
            }

            @media (forced-colors: active) {
                .site-header,
                .site-menu,
                .console-frame,
                .monitor-stage,
                .monitor-hud,
                .capability-item,
                .pipeline-panel,
                .pipeline-row,
                .preset-tab,
                .preset-stage-card,
                .preset-viewport,
                .preset-lower-strip,
                .closing-panel,
                .obs-brand-version-pill {
                    background: Canvas;
                    color: CanvasText;
                    border: 1px solid CanvasText;
                }

                .site-menu a[aria-current="page"],
                .btn-primary,
                .site-menu .nav-cta,
                .preset-tab[aria-selected="true"] {
                    background: Highlight;
                    color: HighlightText;
                    border: 1px solid ButtonText;
                }
            }
        </style>
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to content</a>

        <header id="site-header" class="site-header">
            <div class="header-inner">
                <div class="brand-cluster">
                    <a href="{{ route('home') }}" class="brand-lockup" aria-current="page">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CDAD69" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                            <circle cx="12" cy="12" r="2.5" fill="#CDAD69"/>
                        </svg>
                        <span>Obsisstant</span>
                    </a>
                    <a href="{{ route('changelog') }}" class="obs-brand-version-pill" title="View changelog">v{{ $currentVersion }}</a>
                </div>

                <div class="header-end">
                    <nav class="site-nav" aria-label="Primary">
                        <button
                            id="menu-trigger"
                            class="menu-button"
                            type="button"
                            popovertarget="site-menu"
                            popovertargetaction="toggle"
                            aria-controls="site-menu"
                            aria-expanded="false"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="4" y1="7" x2="20" y2="7"/>
                                <line x1="4" y1="12" x2="20" y2="12"/>
                                <line x1="4" y1="17" x2="20" y2="17"/>
                            </svg>
                            <span>Menu</span>
                        </button>

                        <ul id="site-menu" class="site-menu" popover="auto">
                            <li><a href="{{ route('home') }}" aria-current="page">Overview</a></li>
                            <li><a href="#features">Architecture</a></li>
                            <li><a href="#overlays">Overlays</a></li>
                            <li><a href="{{ route('knowledge-base') }}">Knowledge Base</a></li>
                            <li><a href="{{ route('changelog') }}">Changelog</a></li>
                            <li><a href="{{ route('studio.index') }}" class="nav-cta">Control Center</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </header>

        <main id="main-content" class="container">
            {{-- Hero Section --}}
            <section class="hero-section" aria-labelledby="hero-heading">
                <div>
                    <div class="hero-kicker" data-enter style="--enter-order: 0;">
                        <span>OBS Studio · Remote Deck · Live Overlays</span>
                    </div>

                    <h1 id="hero-heading" class="hero-title" data-enter style="--enter-order: 1;">
                        Run OBS Studio <span class="accent">from any screen.</span>
                    </h1>

                    <p class="hero-lead" data-enter style="--enter-order: 2;">
                        Switch scenes from your phone, run countdowns and scoreboards, and update overlays in one browser source.
                    </p>

                    <div class="hero-actions" data-enter style="--enter-order: 3;">
                        <a href="{{ route('studio.index') }}" class="btn btn-primary">Open Studio</a>
                        <a href="{{ route('obs.deck.index') }}" class="btn btn-secondary">Mobile Deck</a>
                    </div>

                    <div class="hero-metrics" data-enter style="--enter-order: 4;">
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['studios_online'] }} / {{ max(1, $metrics['studios_total']) }}</span>
                            <span class="metric-label">Studios online</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['screens_total'] }}</span>
                            <span class="metric-label">Overlays</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['macros_total'] }}</span>
                            <span class="metric-label">Macros</span>
                        </div>
                    </div>
                </div>

                {{-- Coded Interactive OBS Switcher & Overlay Monitor --}}
                {{-- Interactive 3D Cute Broadcast Mascot --}}
                <aside aria-label="Interactive 3D broadcast mascot" data-enter style="--enter-order: 5;">
                    <x-obs-mascot mode="hero-guide" greeting="Hey! Tap me or scroll to explore." />
                </aside>
            </section>

            {{-- Scroll-Guided Flying 3D Mascot --}}
            <div id="mascot-scroll-guide" class="mascot-scroll-guide" data-side="right" aria-hidden="true">
                <div class="mascot-float-speech" data-mascot-float-speech>Hey! Scroll to see how it works.</div>
                <div class="mascot-float-canvas-box">
                    <canvas data-mascot-float-canvas aria-label="Floating 3D mascot guide — tap for animation"></canvas>
                </div>
            </div>

            {{-- Asymmetric Architecture Band --}}
            <section id="features" class="band" aria-labelledby="features-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">How it works</span>
                    <h2 id="features-heading" class="band-heading">Built for live shows.</h2>
                    <p class="band-copy">
                        A small helper runs beside OBS on your computer—no router setup needed.
                    </p>
                </div>

                <div class="architecture-split">
                    <ul class="capability-list" data-reveal>
                        <li class="capability-item">
                            <h3 class="capability-title">Phone &amp; tablet deck</h3>
                            <p class="capability-desc">Tap to cut scenes, start recording, or adjust audio levels.</p>
                        </li>
                        <li class="capability-item">
                            <h3 class="capability-title">One browser source</h3>
                            <p class="capability-desc">Timers, scoreboards, lower thirds, and alerts live in a single OBS source.</p>
                        </li>
                        <li class="capability-item">
                            <h3 class="capability-title">Quick macros</h3>
                            <p class="capability-desc">Chain scene switches and source toggles into one button.</p>
                        </li>
                    </ul>

                    <div class="pipeline-panel" aria-label="Broadcast command signal path" data-reveal>
                        <div class="pipeline-head">
                            <h3 class="pipeline-title">How a tap reaches OBS</h3>
                            <span class="stage-tag is-gold">Instant</span>
                        </div>

                        <div class="pipeline-nodes">
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">01</span>
                                <div>
                                    <span class="pipeline-step-name">Your Phone or Browser</span>
                                    <span class="pipeline-step-sub">Tap a scene, timer, or macro button</span>
                                </div>
                                <span class="pipeline-tag">Deck</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">02</span>
                                <div>
                                    <span class="pipeline-step-name">Live Sync</span>
                                    <span class="pipeline-step-sub">Sent straight to your studio channel</span>
                                </div>
                                <span class="pipeline-tag">WSS</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">03</span>
                                <div>
                                    <span class="pipeline-step-name">Desktop Helper</span>
                                    <span class="pipeline-step-sub">Runs quietly next to OBS Studio</span>
                                </div>
                                <span class="pipeline-tag">Agent</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">04</span>
                                <div>
                                    <span class="pipeline-step-name">OBS Studio</span>
                                    <span class="pipeline-step-sub">Cuts scenes, audio &amp; overlays locally</span>
                                </div>
                                <span class="pipeline-tag">ws://:4455</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Practical Live Broadcast Toolkit --}}
            <section id="overlays" class="band" aria-labelledby="overlays-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">Live Toolkit</span>
                    <h2 id="overlays-heading" class="band-heading">Everything you use live.</h2>
                    <p class="band-copy">
                        Try the tools you actually control mid-stream—countdowns, lower thirds, scoreboards, and deck taps.
                    </p>
                </div>

                <div class="presets-split">
                    <div class="preset-rail" role="tablist" aria-label="Live broadcast tools" data-reveal>
                        <button type="button" class="preset-tab" role="tab" id="tool-tab-timer" aria-controls="tool-panel-timer" aria-selected="true" data-toolkit-tab="timer">
                            <span>Starting Soon &amp; Countdown</span>
                            <span class="preset-tab-type">Timer</span>
                        </button>
                        <button type="button" class="preset-tab" role="tab" id="tool-tab-l3" aria-controls="tool-panel-l3" aria-selected="false" data-toolkit-tab="l3">
                            <span>Lower Thirds &amp; Alerts</span>
                            <span class="preset-tab-type">Banners</span>
                        </button>
                        <button type="button" class="preset-tab" role="tab" id="tool-tab-score" aria-controls="tool-panel-score" aria-selected="false" data-toolkit-tab="score">
                            <span>Scoreboard &amp; Ticker</span>
                            <span class="preset-tab-type">Matches</span>
                        </button>
                        <button type="button" class="preset-tab" role="tab" id="tool-tab-deck" aria-controls="tool-panel-deck" aria-selected="false" data-toolkit-tab="deck">
                            <span>Phone Deck &amp; Macros</span>
                            <span class="preset-tab-type">Remote</span>
                        </button>
                    </div>

                    <div class="preset-stage-card" data-reveal>
                        {{-- Tool 1: Starting Soon & Countdown --}}
                        <div id="tool-panel-timer" role="tabpanel" aria-labelledby="tool-tab-timer" data-toolkit-panel="timer" style="display: grid; gap: 1rem;">
                            <div class="preset-viewport" style="justify-content: center; align-items: center; text-align: center; gap: 0.75rem;">
                                <span class="stage-tag is-gold" id="demo-timer-status">Starting Soon · Auto-switches at 00:00</span>
                                <div id="demo-timer-digits" style="font-size: clamp(2.25rem, 1.5rem + 2.5vw, 3.5rem); font-weight: 200; font-variant-numeric: tabular-nums; color: #F4E185; line-height: 1.05;">05:00</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                                    <button type="button" id="demo-timer-toggle" class="btn btn-primary" style="min-block-size: 2.25rem; padding: 0.25rem 0.9rem;">Start</button>
                                    <button type="button" id="demo-timer-plus" class="btn btn-secondary" style="min-block-size: 2.25rem; padding: 0.25rem 0.9rem;">+1m</button>
                                    <button type="button" id="demo-timer-reset" class="btn btn-secondary" style="min-block-size: 2.25rem; padding: 0.25rem 0.9rem;">Reset</button>
                                </div>
                            </div>
                            <div class="preset-meta-footer">
                                <span>Runs on the server so phones and OBS stay synced to the exact second.</span>
                                <a href="{{ route('studio.index') }}" style="color: var(--gold-light); text-decoration: none; font-weight: 500;">Open Studio &rarr;</a>
                            </div>
                        </div>

                        {{-- Tool 2: Lower Thirds & Alerts --}}
                        <div id="tool-panel-l3" role="tabpanel" aria-labelledby="tool-tab-l3" data-toolkit-panel="l3" hidden style="display: grid; gap: 1rem;">
                            <div class="preset-viewport" style="justify-content: space-between;">
                                <div class="preset-viewport-bar">
                                    <span class="stage-tag is-gold" id="demo-alert-badge" hidden>Alert · Live Update</span>
                                    <span class="stage-tag">Transparent OBS Layer</span>
                                </div>
                                <div id="demo-l3-banner" class="preset-lower-strip">
                                    <div>
                                        <div class="hud-guest-name" id="demo-l3-name">Alex Rivera</div>
                                        <div class="hud-guest-role" id="demo-l3-role">Guest Host · Main Desk</div>
                                    </div>
                                    <span class="stage-tag is-gold" id="demo-l3-pill">On air</span>
                                </div>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <button type="button" id="demo-l3-btn" class="btn btn-primary" style="min-block-size: 2.25rem; padding: 0.25rem 0.9rem;">Hide Lower Third</button>
                                    <button type="button" id="demo-alert-btn" class="btn btn-secondary" style="min-block-size: 2.25rem; padding: 0.25rem 0.9rem;">Pop Alert</button>
                                </div>
                                <span style="font-size: 0.8125rem; color: var(--ink-muted);">Instant fade in/out over your camera</span>
                            </div>
                        </div>

                        {{-- Tool 3: Scoreboard & Ticker --}}
                        <div id="tool-panel-score" role="tabpanel" aria-labelledby="tool-tab-score" data-toolkit-panel="score" hidden style="display: grid; gap: 1rem;">
                            <div class="preset-viewport" style="justify-content: center; gap: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 0.75rem; text-align: center;">
                                    <div class="preset-lower-strip" style="justify-content: center; flex-direction: column; gap: 0.25rem;">
                                        <span style="font-size: 0.75rem; color: var(--ink-muted);">Team A</span>
                                        <strong id="demo-sb-a" style="font-size: 2rem; font-weight: 300; color: var(--gold-light); font-variant-numeric: tabular-nums;">2</strong>
                                        <button type="button" id="demo-sb-a-plus" class="btn btn-secondary" style="min-block-size: 1.9rem; padding: 0.15rem 0.7rem; font-size: 0.75rem;">+1 Point</button>
                                    </div>
                                    <span class="stage-tag is-gold">VS</span>
                                    <div class="preset-lower-strip" style="justify-content: center; flex-direction: column; gap: 0.25rem;">
                                        <span style="font-size: 0.75rem; color: var(--ink-muted);">Team B</span>
                                        <strong id="demo-sb-b" style="font-size: 2rem; font-weight: 300; color: var(--gold-light); font-variant-numeric: tabular-nums;">1</strong>
                                        <button type="button" id="demo-sb-b-plus" class="btn btn-secondary" style="min-block-size: 1.9rem; padding: 0.15rem 0.7rem; font-size: 0.75rem;">+1 Point</button>
                                    </div>
                                </div>
                                <div class="preset-lower-strip" style="padding: 0.45rem 0.75rem; font-size: 0.78rem; color: var(--ink-secondary);">
                                    <span class="stage-tag is-gold">LIVE</span>
                                    <span>Grand Finals · Best of 5 · Map 3 starting shortly</span>
                                </div>
                            </div>
                            <div class="preset-meta-footer">
                                <span>Update scores from your phone without opening OBS.</span>
                                <a href="{{ route('obs.deck.index') }}" style="color: var(--gold-light); text-decoration: none; font-weight: 500;">Open Deck &rarr;</a>
                            </div>
                        </div>

                        {{-- Tool 4: Phone Deck & Macros --}}
                        <div id="tool-panel-deck" role="tabpanel" aria-labelledby="tool-tab-deck" data-toolkit-panel="deck" hidden style="display: grid; gap: 1rem;">
                            <div class="preset-viewport" style="justify-content: center; gap: 0.75rem;">
                                <div class="deck-pad-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <button type="button" class="pad-key" data-demo-pad aria-pressed="true">
                                        <span>Main Camera</span>
                                        <span class="pad-sub">Cut Scene</span>
                                    </button>
                                    <button type="button" class="pad-key" data-demo-pad aria-pressed="false">
                                        <span>Desktop Share</span>
                                        <span class="pad-sub">Fade 450ms</span>
                                    </button>
                                    <button type="button" class="pad-key" data-demo-pad aria-pressed="false">
                                        <span>Mic Mute</span>
                                        <span class="pad-sub">Audio Toggle</span>
                                    </button>
                                    <button type="button" class="pad-key" data-demo-pad aria-pressed="false">
                                        <span>Go Live Macro</span>
                                        <span class="pad-sub">3-Step Cue</span>
                                    </button>
                                </div>
                            </div>
                            <div class="preset-meta-footer">
                                <span>Customizable tile grid for phones and tablets.</span>
                                <a href="{{ route('obs.deck.index') }}" style="color: var(--gold-light); text-decoration: none; font-weight: 500;">Try Mobile Deck &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features Showcase --}}
            <section id="features-showcase" class="band" aria-labelledby="showcase-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">Features</span>
                    <h2 id="showcase-heading" class="band-heading">Made for real broadcasts.</h2>
                    <p class="band-copy">
                        Every control stays in sync between your desktop, your phone, and OBS Studio.
                    </p>
                </div>

                <div class="showcase-grid" data-reveal>
                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 3v18M3 9h18"/></svg>
                        </span>
                        <h3 class="capability-title">Visual Canvas Builder</h3>
                        <p class="capability-desc">Drag camera cutouts, text, color shapes, timers, and scoreboards onto a 16:9 stage with undo/redo.</p>
                    </article>

                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                        </span>
                        <h3 class="capability-title">Custom Mobile Deck</h3>
                        <p class="capability-desc">Arrange big touch tiles on your phone to switch views, adjust audio, or fire macros.</p>
                    </article>

                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5M9 2h6"/></svg>
                        </span>
                        <h3 class="capability-title">Timer End Actions</h3>
                        <p class="capability-desc">Pick what happens at 00:00—switch views, show a lower third, start your stream, or run a macro.</p>
                    </article>

                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 17l6-6-6-6M12 19h8"/></svg>
                        </span>
                        <h3 class="capability-title">One Browser Source</h3>
                        <p class="capability-desc">Push a single URL into OBS Studio and switch between all your views with smooth transitions.</p>
                    </article>

                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v4"/></svg>
                        </span>
                        <h3 class="capability-title">Live Audio Mixer</h3>
                        <p class="capability-desc">Mute mics or adjust source volumes remotely without leaving your show screen.</p>
                    </article>

                    <article class="showcase-card">
                        <span class="showcase-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                        </span>
                        <h3 class="capability-title">Zero Port Forwarding</h3>
                        <p class="capability-desc">The desktop helper connects outward over TLS so your local OBS port stays private.</p>
                    </article>
                </div>
            </section>

            {{-- Frequently Asked Questions --}}
            <section id="faqs" class="band" aria-labelledby="faq-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">FAQs</span>
                    <h2 id="faq-heading" class="band-heading">Common questions.</h2>
                </div>

                <div class="faq-list" data-reveal>
                    <details class="faq-item" open>
                        <summary>
                            <span>Do I need to open ports on my router?</span>
                            <span class="faq-plus" aria-hidden="true">+</span>
                        </summary>
                        <p class="faq-answer">No. The small helper app on your computer connects outward over HTTPS/WebSocket to Obsisstant and talks to OBS locally on port 4455.</p>
                    </details>

                    <details class="faq-item">
                        <summary>
                            <span>How many OBS scenes do I need for overlays?</span>
                            <span class="faq-plus" aria-hidden="true">+</span>
                        </summary>
                        <p class="faq-answer">Just one. Push your Unified Stage URL into a single OBS Browser Source and switch between Starting Soon, Gameplay, BRB, and Dual Cam views cleanly.</p>
                    </details>

                    <details class="faq-item">
                        <summary>
                            <span>Can I control OBS from my phone on another Wi-Fi or cellular network?</span>
                            <span class="faq-plus" aria-hidden="true">+</span>
                        </summary>
                        <p class="faq-answer">Yes. Open the Mobile Deck on any phone or tablet browser, sign in, and every tap reaches your studio in under a second.</p>
                    </details>

                    <details class="faq-item">
                        <summary>
                            <span>What happens when a countdown timer reaches 00:00?</span>
                            <span class="faq-plus" aria-hidden="true">+</span>
                        </summary>
                        <p class="faq-answer">You can set it to switch to another screen view, pop an alert or lower third, hide the timer, loop automatically, or start/stop OBS streaming.</p>
                    </details>

                    <details class="faq-item">
                        <summary>
                            <span>Does it work on both Mac and Windows?</span>
                            <span class="faq-plus" aria-hidden="true">+</span>
                        </summary>
                        <p class="faq-answer">Yes. One-click installers are built into Studio for both macOS and Windows.</p>
                    </details>
                </div>
            </section>

            {{-- Closing Strip --}}
            <section class="band" aria-labelledby="closing-heading">
                <div class="closing-panel" data-reveal>
                    <div>
                        <h2 id="closing-heading" class="closing-title">Ready when you go live.</h2>
                        <p class="closing-sub">
                            Open Studio to design your views, or jump straight to your Mobile Deck.
                        </p>
                    </div>
                    <div class="hero-actions" style="margin-block-start: 0;">
                        <a href="{{ route('studio.index') }}" class="btn btn-primary">Open Studio</a>
                        <a href="{{ url('/admin/obs-control-deck') }}" class="btn btn-secondary">Admin Panel</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="container site-footer">
            <div>Obsisstant v{{ $currentVersion }} · Local OBS Relay &amp; Broadcast Console</div>
            <div class="footer-links">
                <a href="{{ url('/admin') }}">Admin Panel</a>
                <a href="{{ route('obs.deck.index') }}">Mobile Deck</a>
                <a href="{{ route('knowledge-base') }}">Knowledge Base</a>
                <a href="{{ route('changelog') }}">Changelog</a>
            </div>
        </footer>

        <script>
            (() => {
                // Conditional popover polyfill loader per web guidance (only when native Popover API is missing)
                if (!Object.hasOwn(HTMLElement.prototype, 'popover')) {
                    import('https://unpkg.com/@oddbird/popover-polyfill@latest/dist/popover.min.js').catch(() => {});
                }

                const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
                const noReducedMotionQuery = window.matchMedia('(prefers-reduced-motion: no-preference)');

                let cachedDisplaySupport;
                const canTransitionDisplay = () => {
                    if (cachedDisplaySupport !== undefined) {
                        return cachedDisplaySupport;
                    }
                    if (!window.CSS?.supports?.('transition-behavior', 'allow-discrete') || !document.body) {
                        return false;
                    }
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

                const header = document.getElementById('site-header');
                const headerInner = header?.querySelector('.header-inner');
                const menu = document.getElementById('site-menu');
                const menuTrigger = document.getElementById('menu-trigger');

                let lastCollapsedWidth = 0;

                const syncNavLayout = () => {
                    if (!header || !headerInner || !menu) {
                        return;
                    }

                    const wasWide = header.classList.contains('is-wide');
                    const wasOpen = !wasWide && menu.matches(':popover-open, .\\:popover-open');
                    const containerWidth = headerInner.clientWidth;

                    if (!wasWide && containerWidth <= lastCollapsedWidth) {
                        return;
                    }

                    header.classList.add('is-measuring');
                    header.classList.add('is-wide');
                    menu.removeAttribute('popover');

                    const fitsInline = headerInner.scrollWidth <= headerInner.clientWidth + 1;

                    if (fitsInline) {
                        lastCollapsedWidth = 0;
                        menuTrigger?.setAttribute('aria-expanded', 'false');
                    } else {
                        header.classList.remove('is-wide');
                        menu.setAttribute('popover', 'auto');
                        lastCollapsedWidth = containerWidth;
                        if (wasOpen && typeof menu.showPopover === 'function') {
                            try {
                                menu.showPopover();
                            } catch {}
                        }
                    }

                    requestAnimationFrame(() => header.classList.remove('is-measuring'));
                };

                if (header && menu && 'ResizeObserver' in window) {
                    const observer = new ResizeObserver(syncNavLayout);
                    observer.observe(header);
                    observer.observe(menu);
                }
                syncNavLayout();

                if (menu && menuTrigger) {
                    menu.addEventListener('toggle', (event) => {
                        menuTrigger.setAttribute('aria-expanded', event.newState === 'open' ? 'true' : 'false');
                    });

                    // Close narrow popover cleanly when tapping an in-page anchor
                    menu.querySelectorAll('a[href^="#"]').forEach((link) => {
                        link.addEventListener('click', async () => {
                            if (header?.classList.contains('is-wide') || !menu.matches(':popover-open, .\\:popover-open')) {
                                return;
                            }
                            if (!supportsTopLayerExit()) {
                                menu.setAttribute('data-closing', '');
                                const anims = menu.getAnimations({ subtree: true });
                                if (anims.length > 0) {
                                    await Promise.race([
                                        Promise.allSettled(anims.map((a) => a.finished)),
                                        new Promise((resolve) => setTimeout(resolve, 600)),
                                    ]);
                                }
                                menu.removeAttribute('data-closing');
                            }
                            if (typeof menu.hidePopover === 'function') {
                                menu.hidePopover();
                            }
                        });
                    });
                }

                // Fallback IntersectionObserver scroll reveals for browsers without View Timeline
                const revealTargets = Array.from(document.querySelectorAll('[data-reveal]'));
                const hasNativeViewTimeline = Boolean(
                    window.CSS?.supports?.('(animation-timeline: view()) and (animation-range: entry)')
                );

                if (!hasNativeViewTimeline && noReducedMotionQuery.matches && 'IntersectionObserver' in window && revealTargets.length > 0) {
                    const observer = new IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (!entry.isIntersecting) {
                                    return;
                                }
                                entry.target.classList.remove('is-pending');
                                entry.target.classList.add('is-revealed');
                                observer.unobserve(entry.target);
                            });
                        },
                        { threshold: 0.12 }
                    );

                    revealTargets.forEach((el) => {
                        const rect = el.getBoundingClientRect();
                        if (rect.top > window.innerHeight * 0.92) {
                            el.classList.add('is-pending');
                            observer.observe(el);
                        }
                    });
                }

                // Micro-animation helper using Web Animations API (strictly honoring reduced motion)
                const pulseElementChange = (element) => {
                    if (!element || reducedMotionQuery.matches || typeof element.animate !== 'function') {
                        return;
                    }
                    element.animate(
                        [
                            { opacity: 0.45, transform: 'translateY(6px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        { duration: 240, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
                    );
                };

                // Practical Live Toolkit Tabs & Interactive Demos
                const toolkitTabs = document.querySelectorAll('[data-toolkit-tab]');
                const toolkitPanels = document.querySelectorAll('[data-toolkit-panel]');
                toolkitTabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        const target = tab.dataset.toolkitTab;
                        toolkitTabs.forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
                        toolkitPanels.forEach((p) => {
                            const show = p.dataset.toolkitPanel === target;
                            p.hidden = !show;
                            if (show) pulseElementChange(p);
                        });
                    });
                });

                let demoTimerSecs = 300;
                let demoTimerHandle = null;
                const demoTimerDigits = document.getElementById('demo-timer-digits');
                const demoTimerToggle = document.getElementById('demo-timer-toggle');
                const renderDemoTimer = () => {
                    if (!demoTimerDigits) return;
                    const mm = String(Math.floor(demoTimerSecs / 60)).padStart(2, '0');
                    const ss = String(demoTimerSecs % 60).padStart(2, '0');
                    demoTimerDigits.textContent = `${mm}:${ss}`;
                };
                demoTimerToggle?.addEventListener('click', () => {
                    if (demoTimerHandle) {
                        clearInterval(demoTimerHandle);
                        demoTimerHandle = null;
                        demoTimerToggle.textContent = 'Start';
                    } else {
                        demoTimerToggle.textContent = 'Pause';
                        demoTimerHandle = setInterval(() => {
                            demoTimerSecs = Math.max(0, demoTimerSecs - 1);
                            renderDemoTimer();
                            if (demoTimerSecs === 0) {
                                clearInterval(demoTimerHandle);
                                demoTimerHandle = null;
                                demoTimerToggle.textContent = 'Start';
                            }
                        }, 1000);
                    }
                });
                document.getElementById('demo-timer-plus')?.addEventListener('click', () => {
                    demoTimerSecs += 60;
                    renderDemoTimer();
                });
                document.getElementById('demo-timer-reset')?.addEventListener('click', () => {
                    clearInterval(demoTimerHandle);
                    demoTimerHandle = null;
                    demoTimerSecs = 300;
                    if (demoTimerToggle) demoTimerToggle.textContent = 'Start';
                    renderDemoTimer();
                });

                const demoL3Btn = document.getElementById('demo-l3-btn');
                const demoL3Banner = document.getElementById('demo-l3-banner');
                const demoL3Pill = document.getElementById('demo-l3-pill');
                demoL3Btn?.addEventListener('click', () => {
                    if (!demoL3Banner) return;
                    const nextShow = demoL3Banner.hidden;
                    demoL3Banner.hidden = !nextShow;
                    demoL3Btn.textContent = nextShow ? 'Hide Lower Third' : 'Show Lower Third';
                    if (demoL3Pill) demoL3Pill.textContent = nextShow ? 'On air' : 'Hidden';
                });
                const demoAlertBadge = document.getElementById('demo-alert-badge');
                document.getElementById('demo-alert-btn')?.addEventListener('click', () => {
                    if (!demoAlertBadge) return;
                    demoAlertBadge.hidden = !demoAlertBadge.hidden;
                });

                let scoreA = 2;
                let scoreB = 1;
                document.getElementById('demo-sb-a-plus')?.addEventListener('click', () => {
                    scoreA++;
                    const el = document.getElementById('demo-sb-a');
                    if (el) { el.textContent = String(scoreA); pulseElementChange(el); }
                });
                document.getElementById('demo-sb-b-plus')?.addEventListener('click', () => {
                    scoreB++;
                    const el = document.getElementById('demo-sb-b');
                    if (el) { el.textContent = String(scoreB); pulseElementChange(el); }
                });

                document.querySelectorAll('[data-demo-pad]').forEach((pad) => {
                    pad.addEventListener('click', () => {
                        const pressed = pad.getAttribute('aria-pressed') === 'true';
                        pad.setAttribute('aria-pressed', pressed ? 'false' : 'true');
                    });
                });
            })();
        </script>
    </body>
</html>
