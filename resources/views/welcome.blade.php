<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ config('app.name', 'Obsisstant') }} · OBS Broadcast Relay &amp; Overlay Studio</title>
        <meta name="description" content="Real-time wireless OBS Studio deck, hardware relay agent, and live Browser Source overlays synced via Laravel Reverb.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600&display=swap" rel="stylesheet">
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
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                font-size: 0.9375rem;
                font-weight: 400;
                line-height: 1.6;
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

            :is(a, button, input, select, textarea, [tabindex]):focus-visible {
                outline: 2px solid var(--gold-light);
                outline-offset: 2px;
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
                background: rgba(11, 11, 10, 0.86);
                -webkit-backdrop-filter: blur(16px);
                backdrop-filter: blur(16px);
                border-block-end: 1px solid var(--hairline);
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
                font-size: clamp(2.125rem, 1.45rem + 2.3vw, 3.625rem);
                font-weight: 600;
                line-height: 1.08;
                letter-spacing: -0.025em;
                text-wrap: balance;
                color: var(--ink-primary);
            }

            .hero-title .accent {
                color: var(--gold-light);
            }

            .hero-lead {
                margin: var(--space-4) 0 0;
                max-inline-size: 52ch;
                font-size: 1rem;
                line-height: 1.6;
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
                background-image: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border: 1px solid rgba(244, 225, 133, 0.45);
                box-shadow: inset 0 1px 0 rgba(255, 250, 224, 0.45);
            }

            .btn-primary:hover {
                background-image: linear-gradient(180deg, #E8CE8D 0%, #D6B773 100%);
            }

            .btn-secondary {
                background-color: var(--bg-raised);
                color: var(--ink-primary);
                border: 1px solid var(--hairline-strong);
            }

            .btn-secondary:hover {
                background-color: #23221E;
                border-color: var(--gold-accent);
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
                background-color: var(--bg-card);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-frame);
                padding: var(--space-3);
                display: grid;
                gap: var(--space-3);
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
                font-size: clamp(1.5rem, 1.15rem + 1.25vw, 2.25rem);
                font-weight: 500;
                line-height: 1.16;
                letter-spacing: -0.02em;
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
                background-color: var(--bg-card);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-card);
                padding: var(--space-6);
                display: grid;
                gap: var(--space-5);
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
                background-color: var(--bg-card);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-frame);
                padding: var(--space-4);
                display: grid;
                gap: var(--space-4);
            }

            .preset-viewport {
                position: relative;
                aspect-ratio: 16 / 9;
                border-radius: var(--radius-inner);
                border: 1px solid var(--hairline);
                background-color: #0D0D0B;
                padding: clamp(var(--space-4), 3vw, var(--space-6));
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
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
                background-color: var(--bg-card);
                border: 1px solid var(--hairline-strong);
                border-radius: var(--radius-card);
                padding: clamp(var(--space-6), 4vw, var(--space-12));
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: var(--space-6);
            }

            .closing-title {
                margin: 0;
                font-size: clamp(1.375rem, 1.1rem + 0.9vw, 1.875rem);
                font-weight: 500;
                line-height: 1.2;
                letter-spacing: -0.02em;
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
                        <span>Laravel Reverb · OBS WebSocket v5</span>
                    </div>

                    <h1 id="hero-heading" class="hero-title" data-enter style="--enter-order: 1;">
                        Control every OBS frame <span class="accent">from any device</span>
                    </h1>

                    <p class="hero-lead" data-enter style="--enter-order: 2;">
                        Switch scenes, fire multi-step broadcast macros, and update Browser Source overlays in real time through an outbound local relay—zero router port-forwarding required.
                    </p>

                    <div class="hero-actions" data-enter style="--enter-order: 3;">
                        <a href="{{ route('studio.index') }}" class="btn btn-primary">Launch Studio Center</a>
                        <a href="{{ route('obs.deck.index') }}" class="btn btn-secondary">Mobile wireless deck</a>
                    </div>

                    <div class="hero-metrics" data-enter style="--enter-order: 4;">
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['studios_online'] }} / {{ max(1, $metrics['studios_total']) }}</span>
                            <span class="metric-label">Studios online</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['screens_total'] }}</span>
                            <span class="metric-label">Live overlays</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $metrics['macros_total'] }}</span>
                            <span class="metric-label">Saved macros</span>
                        </div>
                    </div>
                </div>

                {{-- Coded Interactive OBS Switcher & Overlay Monitor --}}
                <aside class="console-frame" aria-label="Interactive broadcast switcher preview" data-enter style="--enter-order: 5;">
                    <div class="console-bar">
                        <span class="console-title">Live program monitor</span>
                        <span class="console-badge" id="preview-status-chip">{{ $metrics['studios_online'] > 0 ? 'PGM · Relay connected' : 'PGM · Relay ready' }}</span>
                    </div>

                    <div id="hero-monitor" class="monitor-stage">
                        <div class="monitor-top">
                            <span class="monitor-chip is-pgm" id="preview-scene-pill">Scene · Starting Soon</span>
                            <span class="monitor-chip" id="preview-stream-chip">1080p60 · Ready</span>
                        </div>

                        <div class="monitor-scene-stage">
                            <span class="monitor-scene-meta" id="preview-scene-meta">Program output</span>
                            <h2 class="monitor-scene-heading" id="preview-scene-heading">Starting Soon</h2>
                        </div>

                        <div class="monitor-hud" id="preview-lower-third">
                            <div>
                                <div class="hud-guest-name" id="preview-lt-name">Obsisstant Studio</div>
                                <div class="hud-guest-role" id="preview-lt-role">Browser Source Overlay · Lower Thirds</div>
                            </div>
                            <span class="hud-state-tag" id="preview-lt-state">On air</span>
                        </div>
                    </div>

                    <div class="deck-pad-grid" role="group" aria-label="Test scene switcher buttons">
                        <button type="button" class="pad-key" data-demo-scene="Starting Soon" data-demo-meta="Pre-show slate" aria-pressed="true">
                            <span>Starting Soon</span>
                            <span class="pad-sub">Scene 01</span>
                        </button>
                        <button type="button" class="pad-key" data-demo-scene="Main Camera" data-demo-meta="Studio A feed" aria-pressed="false">
                            <span>Main Camera</span>
                            <span class="pad-sub">Scene 02</span>
                        </button>
                        <button type="button" class="pad-key" data-demo-scene="Screen Share" data-demo-meta="Workstation capture" aria-pressed="false">
                            <span>Screen Share</span>
                            <span class="pad-sub">Scene 03</span>
                        </button>
                        <button type="button" class="pad-key" id="toggle-lt-pad" aria-pressed="true">
                            <span>Lower Third</span>
                            <span class="pad-sub" id="toggle-lt-sub">Overlay visible</span>
                        </button>
                    </div>
                </aside>
            </section>

            {{-- Asymmetric Architecture Band --}}
            <section id="features" class="band" aria-labelledby="features-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">Architecture</span>
                    <h2 id="features-heading" class="band-heading">Outbound WebSocket control with zero firewall changes</h2>
                    <p class="band-copy">
                        Your local relay agent dials out to Laravel Reverb over TLS and bridges every button tap straight to local OBS Studio on port 4455.
                    </p>
                </div>

                <div class="architecture-split">
                    <ul class="capability-list" data-reveal>
                        <li class="capability-item">
                            <h3 class="capability-title">Wireless mobile &amp; desktop decks</h3>
                            <p class="capability-desc">Switch scenes, trigger transitions, toggle streams, and balance audio faders from phone or panel.</p>
                        </li>
                        <li class="capability-item">
                            <h3 class="capability-title">Multi-step macro sequences</h3>
                            <p class="capability-desc">Chain source visibility, scene cuts, and timed delays into reliable single-tap broadcast cues.</p>
                        </li>
                        <li class="capability-item">
                            <h3 class="capability-title">Token-scoped Browser Sources</h3>
                            <p class="capability-desc">Push instant headline updates, guest lower-thirds, and alerts to transparent OBS overlay URLs.</p>
                        </li>
                    </ul>

                    <div class="pipeline-panel" aria-label="Broadcast command signal path" data-reveal>
                        <div class="pipeline-head">
                            <h3 class="pipeline-title">Command signal path</h3>
                            <span class="stage-tag is-gold">Sub-second relay</span>
                        </div>

                        <div class="pipeline-nodes">
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">01</span>
                                <div>
                                    <span class="pipeline-step-name">Operator Deck or Panel</span>
                                    <span class="pipeline-step-sub">Mobile wireless deck or Filament control surface</span>
                                </div>
                                <span class="pipeline-tag">HTTPS</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">02</span>
                                <div>
                                    <span class="pipeline-step-name">Laravel Reverb Broker</span>
                                    <span class="pipeline-step-sub">Authorized studio channel fan-out</span>
                                </div>
                                <span class="pipeline-tag">WSS</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">03</span>
                                <div>
                                    <span class="pipeline-step-name">Local Relay Daemon</span>
                                    <span class="pipeline-step-sub">Authenticated agent beside OBS Studio</span>
                                </div>
                                <span class="pipeline-tag">Outbound</span>
                            </div>
                            <div class="pipeline-row">
                                <span class="pipeline-step-num">04</span>
                                <div>
                                    <span class="pipeline-step-name">OBS Studio v5 Engine</span>
                                    <span class="pipeline-step-sub">Scene cuts, audio mixers &amp; replay buffers</span>
                                </div>
                                <span class="pipeline-tag">ws://:4455</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Interactive Overlay Presets Explorer --}}
            @php
                $firstPreset = $presets[0] ?? [
                    'key' => 'starting_soon',
                    'label' => 'Starting Soon',
                    'theme' => ['transparent_background' => false],
                    'state' => [
                        'badge_text' => 'LIVE SOON',
                        'headline' => 'Stream Starting Soon',
                        'subheadline' => 'Grab a drink — broadcast begins shortly.',
                        'show_countdown' => true,
                    ],
                ];
            @endphp

            <section id="overlays" class="band" aria-labelledby="overlays-heading">
                <div class="band-header" data-reveal>
                    <span class="band-eyebrow">Browser Source Studio</span>
                    <h2 id="overlays-heading" class="band-heading">Broadcast canvases ready for any scene</h2>
                    <p class="band-copy">
                        Select any built-in preset to inspect how Obsisstant renders full-frame slates and transparent lower-thirds inside OBS Studio.
                    </p>
                </div>

                <div class="presets-split">
                    <div class="preset-rail" role="tablist" aria-label="Overlay canvas presets" data-reveal>
                        @foreach ($presets as $index => $preset)
                            <button
                                type="button"
                                class="preset-tab"
                                role="tab"
                                id="preset-tab-{{ $preset['key'] }}"
                                aria-controls="preset-tabpanel"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-preset-key="{{ $preset['key'] }}"
                                data-preset='@json($preset)'
                            >
                                <span>{{ $preset['label'] }}</span>
                                <span class="preset-tab-type">{{ ! empty($preset['theme']['transparent_background']) ? 'Overlay' : 'Slate' }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div id="preset-tabpanel" class="preset-stage-card" role="tabpanel" aria-labelledby="preset-tab-{{ $firstPreset['key'] }}" data-reveal>
                        <div class="preset-viewport" id="preset-stage-viewport">
                            <div class="preset-viewport-bar">
                                <span class="stage-tag is-gold" id="preset-badge">{{ $firstPreset['state']['badge_text'] ?? 'Live' }}</span>
                                <span class="stage-tag" id="preset-bg-tag">{{ ! empty($firstPreset['theme']['transparent_background']) ? 'Transparent layer' : 'Full canvas' }}</span>
                            </div>

                            <div class="preset-copy-block" id="preset-copy-region">
                                <h3 class="preset-headline" id="preset-headline">{{ $firstPreset['state']['headline'] ?? $firstPreset['label'] }}</h3>
                                <p class="preset-subheadline" id="preset-subheadline">{{ $firstPreset['state']['subheadline'] ?? 'Broadcast overlay synced through Reverb.' }}</p>
                            </div>

                            <div class="preset-lower-strip">
                                <div>
                                    <div class="hud-guest-name" id="preset-guest">{{ $firstPreset['state']['guest_name'] ?? 'Obsisstant Studio' }}</div>
                                    <div class="hud-guest-role" id="preset-role">{{ $firstPreset['state']['guest_role'] ?? 'Real-Time Overlay' }}</div>
                                </div>
                                <span class="stage-tag is-gold" id="preset-extra">{{ ! empty($firstPreset['state']['show_countdown']) ? 'Timer active' : (! empty($firstPreset['state']['show_webcam_frame']) ? 'Cam frame' : 'Reverb sync') }}</span>
                            </div>
                        </div>

                        <div class="preset-meta-footer">
                            <span id="preset-side-type">Preset key: {{ $firstPreset['key'] }}</span>
                            <span id="preset-side-title">{{ $firstPreset['label'] }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Closing Strip --}}
            <section class="band" aria-labelledby="closing-heading">
                <div class="closing-panel" data-reveal>
                    <div>
                        <h2 id="closing-heading" class="closing-title">Open your broadcast console</h2>
                        <p class="closing-sub">
                            Configure studio relay profiles, customize live Browser Sources, or launch the mobile wireless control deck.
                        </p>
                    </div>
                    <div class="hero-actions" style="margin-block-start: 0;">
                        <a href="{{ route('studio.index') }}" class="btn btn-primary">Launch Control Center</a>
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

                // Interactive Demo Scene Switcher
                const sceneButtons = document.querySelectorAll('[data-demo-scene]');
                const scenePill = document.getElementById('preview-scene-pill');
                const sceneHeading = document.getElementById('preview-scene-heading');
                const sceneMeta = document.getElementById('preview-scene-meta');

                sceneButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const sceneName = button.getAttribute('data-demo-scene') || '';
                        const metaText = button.getAttribute('data-demo-meta') || 'Program output';

                        sceneButtons.forEach((btn) => btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false'));
                        if (scenePill) {
                            scenePill.textContent = `Scene · ${sceneName}`;
                        }
                        if (sceneMeta) {
                            sceneMeta.textContent = metaText;
                        }
                        if (sceneHeading) {
                            sceneHeading.textContent = sceneName;
                            pulseElementChange(sceneHeading);
                        }
                    });
                });

                // Interactive Lower-Third HUD Toggle with Fallback for Discrete Display Exits
                const ltToggleBtn = document.getElementById('toggle-lt-pad');
                const ltToggleSub = document.getElementById('toggle-lt-sub');
                const ltBanner = document.getElementById('preview-lower-third');
                const ltStateTag = document.getElementById('preview-lt-state');

                const hideHud = async (el) => {
                    if (canTransitionDisplay()) {
                        el.hidden = true;
                    } else {
                        el.setAttribute('data-closing', '');
                    }

                    const animations = el.getAnimations();
                    if (animations.length > 0) {
                        await Promise.race([
                            Promise.allSettled(animations.map((a) => a.finished)),
                            new Promise((resolve) => setTimeout(resolve, 1000)),
                        ]);
                    }

                    if (el.hasAttribute('data-closing')) {
                        el.removeAttribute('data-closing');
                        el.hidden = true;
                    }
                };

                const showHud = (el) => {
                    el.removeAttribute('data-closing');
                    el.hidden = false;
                };

                ltToggleBtn?.addEventListener('click', async () => {
                    if (!ltBanner) {
                        return;
                    }

                    const nextVisible = ltBanner.hidden || ltBanner.hasAttribute('data-closing');
                    ltToggleBtn.setAttribute('aria-pressed', nextVisible ? 'true' : 'false');

                    if (ltToggleSub) {
                        ltToggleSub.textContent = nextVisible ? 'Overlay visible' : 'Overlay hidden';
                    }
                    if (ltStateTag) {
                        ltStateTag.textContent = nextVisible ? 'On air' : 'Muted';
                    }

                    if (nextVisible) {
                        showHud(ltBanner);
                    } else {
                        await hideHud(ltBanner);
                    }
                });

                // Overlay Preset Explorer
                const presetTabs = document.querySelectorAll('[data-preset-key]');
                const presetTabpanel = document.getElementById('preset-tabpanel');
                const presetCopyRegion = document.getElementById('preset-copy-region');
                const pBadge = document.getElementById('preset-badge');
                const pBgTag = document.getElementById('preset-bg-tag');
                const pHeadline = document.getElementById('preset-headline');
                const pSubheadline = document.getElementById('preset-subheadline');
                const pGuest = document.getElementById('preset-guest');
                const pRole = document.getElementById('preset-role');
                const pExtra = document.getElementById('preset-extra');
                const pSideType = document.getElementById('preset-side-type');
                const pSideTitle = document.getElementById('preset-side-title');

                presetTabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        const raw = tab.getAttribute('data-preset');
                        if (!raw) {
                            return;
                        }

                        const data = JSON.parse(raw);
                        const state = data.state || {};
                        const theme = data.theme || {};

                        presetTabs.forEach((btn) => btn.setAttribute('aria-selected', btn === tab ? 'true' : 'false'));
                        if (presetTabpanel && tab.id) {
                            presetTabpanel.setAttribute('aria-labelledby', tab.id);
                        }

                        if (pBadge) {
                            pBadge.textContent = state.badge_text || 'Live';
                        }
                        if (pBgTag) {
                            pBgTag.textContent = theme.transparent_background ? 'Transparent layer' : 'Full canvas';
                        }
                        if (pHeadline) {
                            pHeadline.textContent = state.headline || data.label || '';
                        }
                        if (pSubheadline) {
                            pSubheadline.textContent = state.subheadline || '';
                        }
                        if (pGuest) {
                            pGuest.textContent = state.guest_name || 'Obsisstant Studio';
                        }
                        if (pRole) {
                            pRole.textContent = state.guest_role || 'Real-Time Overlay';
                        }
                        if (pExtra) {
                            pExtra.textContent = state.show_countdown
                                ? 'Timer active'
                                : (state.show_webcam_frame ? 'Cam frame' : 'Reverb sync');
                        }
                        if (pSideType) {
                            pSideType.textContent = `Preset key: ${data.key || 'custom'}`;
                        }
                        if (pSideTitle) {
                            pSideTitle.textContent = data.label || 'Custom Overlay';
                        }

                        pulseElementChange(presetCopyRegion);
                    });
                });
            })();
        </script>
    </body>
</html>
