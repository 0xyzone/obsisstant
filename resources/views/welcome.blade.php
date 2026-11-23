<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ config('app.name', 'Obsisstant') }} · Obsidian Broadcast Control</title>
        <meta name="description" content="Control multi-studio OBS Studio rigs, push real-time Reverb screen overlays, and trigger broadcast macros from any desktop or mobile deck.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=syne:700,800|plus-jakarta-sans:400,500,600,700|jetbrains-mono:500,600&display=swap" rel="stylesheet">
        <style>
            :root {
                color-scheme: dark;
                --bg-void: #050506;
                --bg-obsidian: #0a0907;
                --surface-glass: rgba(17, 15, 11, 0.72);
                --surface-glass-strong: rgba(23, 20, 14, 0.88);
                --surface-pill: rgba(255, 255, 255, 0.045);
                --gold-bright: #ffd700;
                --gold-300: #ffe680;
                --gold-400: #f5c451;
                --gold-500: #d4af37;
                --border-gold: rgba(255, 215, 0, 0.28);
                --border-gold-strong: rgba(255, 215, 0, 0.56);
                --border-subtle: rgba(255, 255, 255, 0.1);
                --text-primary: #faf6ec;
                --text-secondary: #d4ccb8;
                --text-muted: #9e9582;
                --font-display: 'Syne', ui-sans-serif, system-ui, sans-serif;
                --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
                --font-mono: 'JetBrains Mono', ui-monospace, monospace;
                --radius-card: 1.5rem;
                --radius-pill: 9999px;
                --shadow-glass: 0 24px 60px -20px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(255, 230, 128, 0.16);
                --shadow-gold-glow: 0 16px 40px -14px rgba(255, 215, 0, 0.45);
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                background-color: var(--bg-void);
                color: var(--text-primary);
                font-family: var(--font-sans);
                -webkit-font-smoothing: antialiased;
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                min-block-size: 100dvb;
                background-color: var(--bg-void);
                color: var(--text-primary);
                line-height: 1.55;
                position: relative;
                overflow-x: clip;
                text-align: start;
            }

            .ambient-canvas {
                position: fixed;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background:
                    radial-gradient(circle at 12% 10%, rgba(255, 215, 0, 0.22), transparent 38%),
                    radial-gradient(circle at 86% 16%, rgba(212, 175, 55, 0.16), transparent 44%),
                    radial-gradient(circle at 50% 85%, rgba(245, 196, 81, 0.1), transparent 52%),
                    linear-gradient(180deg, #090806 0%, #050506 100%);
            }

            .ambient-grid {
                position: fixed;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                opacity: 0.24;
                background-image:
                    linear-gradient(to right, rgba(255, 215, 0, 0.08) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255, 215, 0, 0.08) 1px, transparent 1px);
                background-size: 48px 48px;
                mask-image: radial-gradient(ellipse at 50% 24%, black 12%, transparent 78%);
            }

            .page-shell {
                position: relative;
                z-index: 1;
                inline-size: min(100%, 78rem);
                margin-inline: auto;
                padding-inline: max(1rem, env(safe-area-inset-left)) max(1rem, env(safe-area-inset-right));
                padding-block-start: max(0.85rem, env(safe-area-inset-top));
                padding-block-end: max(4rem, env(safe-area-inset-bottom));
                display: grid;
                gap: clamp(2.5rem, 1.5rem + 3vw, 4.75rem);
            }

            /* Navigation Bar following responsive popover guidance */
            .site-header {
                position: sticky;
                inset-block-start: max(0.75rem, env(safe-area-inset-top));
                z-index: 40;
                border-radius: 1.35rem;
                background: rgba(12, 11, 8, 0.78);
                -webkit-backdrop-filter: blur(22px) saturate(150%);
                backdrop-filter: blur(22px) saturate(150%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
                padding: 0.65rem 1rem;
            }

            .header-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .header-inner > * {
                flex-shrink: 0;
                white-space: nowrap;
            }

            .site-header.is-measuring * {
                transition: none !important;
            }

            .brand-lockup {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                text-decoration: none;
                color: var(--text-primary);
            }

            .brand-mark {
                display: grid;
                place-items: center;
                inline-size: 2.5rem;
                block-size: 2.5rem;
                border-radius: 0.9rem;
                background: linear-gradient(135deg, rgba(255, 215, 0, 0.28), rgba(158, 115, 20, 0.22));
                border: 1px solid var(--border-gold-strong);
                color: var(--gold-bright);
                box-shadow: inset 0 1px 0 rgba(255, 240, 175, 0.4);
            }

            .brand-name {
                font-family: var(--font-display);
                font-size: 1.15rem;
                font-weight: 800;
                letter-spacing: -0.02em;
            }

            .version-tag {
                font-family: var(--font-mono);
                font-size: 0.7rem;
                font-weight: 600;
                color: var(--gold-bright);
                background: rgba(255, 215, 0, 0.12);
                border: 1px solid var(--border-gold);
                border-radius: var(--radius-pill);
                padding: 0.2rem 0.55rem;
            }

            .site-nav {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .menu-button {
                anchor-name: --menu-button;
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                min-block-size: 2.6rem;
                padding: 0.45rem 0.95rem;
                border-radius: var(--radius-pill);
                border: 1px solid var(--border-gold);
                background: rgba(255, 215, 0, 0.1);
                color: var(--gold-300);
                font: inherit;
                font-size: 0.85rem;
                font-weight: 600;
                cursor: pointer;
            }

            .site-menu {
                position: fixed;
                inset: auto;
                inset-block-start: 4.85rem;
                inset-block-start: anchor(--menu-button bottom);
                inset-inline-end: 1rem;
                inline-size: min(84dvw, 22rem);
                max-inline-size: calc(100dvw - 2rem);
                max-block-size: calc(100dvb - 6rem);
                margin: 0.5rem 0 0;
                padding: 0.75rem;
                list-style: none;
                overflow: auto;
                border-radius: 1.25rem;
                background: rgba(14, 12, 9, 0.94);
                -webkit-backdrop-filter: blur(24px) saturate(160%);
                backdrop-filter: blur(24px) saturate(160%);
                border: 1px solid var(--border-gold-strong);
                box-shadow: var(--shadow-glass);
                color: var(--text-primary);
            }

            .site-menu::backdrop {
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(4px);
            }

            .site-menu[popover]:is(:popover-open, .\:popover-open) {
                display: grid;
                gap: 0.35rem;
            }

            @media (prefers-reduced-motion: no-preference) {
                .site-menu[popover] {
                    opacity: 0;
                    translate: 0 -0.45rem;
                    transition:
                        opacity 0.2s ease,
                        translate 0.2s ease,
                        display 0.2s ease,
                        overlay 0.2s ease;
                    transition-behavior: allow-discrete;
                }

                .site-menu[popover]:is(:popover-open, .\:popover-open) {
                    opacity: 1;
                    translate: 0 0;
                }

                @starting-style {
                    .site-menu[popover]:is(:popover-open, .\:popover-open) {
                        opacity: 0;
                        translate: 0 -0.45rem;
                    }
                }
            }

            .nav-link {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                min-block-size: 2.5rem;
                padding: 0.45rem 0.9rem;
                border-radius: var(--radius-pill);
                text-decoration: none;
                font-size: 0.86rem;
                font-weight: 600;
                color: var(--text-secondary);
                border: 1px solid transparent;
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            }

            .nav-link:hover {
                color: var(--gold-300);
                background: rgba(255, 215, 0, 0.08);
            }

            .nav-link[aria-current="page"] {
                color: var(--gold-bright);
                background: rgba(255, 215, 0, 0.14);
                border-color: var(--border-gold);
            }

            .nav-cta {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                min-block-size: 2.55rem;
                padding: 0.5rem 1.15rem;
                border-radius: var(--radius-pill);
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 700;
                color: #080705;
                background: linear-gradient(135deg, var(--gold-bright), var(--gold-400));
                box-shadow: var(--shadow-gold-glow);
                transition: transform 0.18s ease, box-shadow 0.2s ease;
            }

            .nav-cta:hover {
                transform: translateY(-1px);
            }

            .site-header.is-wide .menu-button {
                display: none;
            }

            .site-header.is-wide .site-menu {
                position: static;
                display: flex;
                align-items: center;
                gap: 0.35rem;
                inline-size: auto;
                max-inline-size: none;
                max-block-size: none;
                overflow: visible;
                margin: 0;
                padding: 0;
                border: 0;
                border-radius: 0;
                background: transparent;
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
                box-shadow: none;
            }

            .site-header.is-wide .site-menu::backdrop {
                display: none;
            }

            :is(a, button, [tabindex]):focus-visible {
                outline: 2px solid var(--gold-bright);
                outline-offset: 3px;
            }

            /* Hero Layout & Kinetic Display Typography */
            .hero-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: clamp(1.75rem, 1rem + 2.5vw, 3rem);
                align-items: center;
            }

            @media (min-width: 62rem) {
                .hero-grid {
                    grid-template-columns: minmax(0, 1.18fr) minmax(0, 0.82fr);
                }
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.55rem;
                min-block-size: 2rem;
                padding: 0.3rem 0.85rem;
                border-radius: var(--radius-pill);
                background: rgba(255, 215, 0, 0.1);
                border: 1px solid var(--border-gold);
                font-family: var(--font-mono);
                font-size: 0.72rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--gold-300);
            }

            .eyebrow-dot {
                inline-size: 0.5rem;
                block-size: 0.5rem;
                border-radius: 50%;
                background: var(--gold-bright);
                box-shadow: 0 0 12px var(--gold-bright);
            }

            .hero-display {
                margin: 1rem 0 0;
                font-family: var(--font-display);
                font-size: clamp(2.85rem, 1.45rem + 5.6vw, 6.65rem);
                font-weight: 800;
                line-height: 0.92;
                letter-spacing: -0.04em;
                text-wrap: balance;
                color: var(--text-primary);
            }

            .hero-display .gold-accent {
                display: block;
                background: linear-gradient(125deg, #fff5c0 0%, #ffd700 45%, #d4af37 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .hero-lead {
                margin: 1.35rem 0 0;
                max-inline-size: 54ch;
                font-size: clamp(1rem, 0.94rem + 0.35vw, 1.2rem);
                line-height: 1.65;
                color: var(--text-secondary);
                text-wrap: pretty;
            }

            .hero-actions {
                margin-block-start: 1.75rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .btn-gold {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                min-block-size: 3.15rem;
                padding: 0.75rem 1.45rem;
                border-radius: var(--radius-pill);
                background: linear-gradient(135deg, var(--gold-bright), var(--gold-400));
                color: #080705;
                text-decoration: none;
                font-family: var(--font-sans);
                font-size: 0.95rem;
                font-weight: 700;
                box-shadow: var(--shadow-gold-glow);
                transition: transform 0.18s ease, filter 0.18s ease;
            }

            .btn-gold:hover {
                transform: translateY(-2px);
                filter: brightness(1.06);
            }

            .btn-glass {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                min-block-size: 3.15rem;
                padding: 0.75rem 1.35rem;
                border-radius: var(--radius-pill);
                background: rgba(255, 255, 255, 0.05);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border: 1px solid var(--border-gold);
                color: var(--text-primary);
                text-decoration: none;
                font-family: var(--font-sans);
                font-size: 0.92rem;
                font-weight: 600;
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }

            .btn-glass:hover {
                background: rgba(255, 215, 0, 0.12);
                border-color: var(--border-gold-strong);
                color: var(--gold-300);
            }

            .hero-kpi-strip {
                margin-block-start: 2.25rem;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.85rem;
            }

            .kpi-cell {
                padding: 0.9rem 1rem;
                border-radius: 1.25rem;
                background: var(--surface-glass);
                -webkit-backdrop-filter: blur(16px);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 215, 0, 0.22);
            }

            .kpi-value {
                font-family: var(--font-display);
                font-size: clamp(1.75rem, 1.4rem + 1.4vw, 2.5rem);
                font-weight: 800;
                line-height: 1.05;
                color: var(--gold-bright);
            }

            .kpi-label {
                margin-block-start: 0.25rem;
                font-family: var(--font-mono);
                font-size: 0.68rem;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: var(--text-muted);
            }

            /* Interactive Mobile Device Glass Card */
            .phone-shell {
                border-radius: 2.2rem;
                padding: 1.1rem;
                background: linear-gradient(165deg, rgba(28, 24, 15, 0.9), rgba(10, 9, 7, 0.92));
                -webkit-backdrop-filter: blur(26px) saturate(160%);
                backdrop-filter: blur(26px) saturate(160%);
                border: 1px solid var(--border-gold-strong);
                box-shadow:
                    0 34px 80px -24px rgba(0, 0, 0, 0.95),
                    0 0 60px -24px rgba(255, 215, 0, 0.28),
                    inset 0 1px 0 rgba(255, 238, 160, 0.28);
                display: grid;
                gap: 0.95rem;
            }

            .phone-notch-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-inline: 0.35rem;
            }

            .phone-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.25rem 0.65rem;
                border-radius: var(--radius-pill);
                font-family: var(--font-mono);
                font-size: 0.68rem;
                font-weight: 600;
                background: rgba(255, 215, 0, 0.14);
                border: 1px solid var(--border-gold);
                color: var(--gold-300);
            }

            .monitor-stage {
                position: relative;
                aspect-ratio: 16 / 9;
                border-radius: 1.25rem;
                overflow: hidden;
                background:
                    radial-gradient(circle at 20% 20%, rgba(255, 215, 0, 0.24), transparent 55%),
                    linear-gradient(145deg, #15120c, #070708);
                border: 1px solid rgba(255, 215, 0, 0.3);
                padding: 0.9rem;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .monitor-badge-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .monitor-live-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.2rem 0.6rem;
                border-radius: var(--radius-pill);
                background: rgba(255, 215, 0, 0.2);
                border: 1px solid var(--border-gold-strong);
                font-family: var(--font-mono);
                font-size: 0.65rem;
                font-weight: 700;
                color: var(--gold-bright);
            }

            .monitor-scene-title {
                margin: 0.35rem 0 0;
                font-family: var(--font-display);
                font-size: clamp(1.35rem, 1rem + 1vw, 1.85rem);
                font-weight: 800;
                line-height: 1.05;
                color: var(--text-primary);
            }

            .monitor-lower-third {
                border-radius: 0.9rem;
                padding: 0.55rem 0.8rem;
                background: rgba(10, 9, 7, 0.8);
                border: 1px solid var(--border-gold);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            .monitor-lower-third.is-hidden {
                opacity: 0.28;
            }

            .pad-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.55rem;
            }

            .deck-pad {
                min-block-size: 3.35rem;
                padding: 0.65rem 0.8rem;
                border-radius: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.12);
                background: rgba(255, 255, 255, 0.045);
                color: var(--text-primary);
                font: inherit;
                font-size: 0.82rem;
                font-weight: 600;
                text-align: start;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 0.25rem;
                transition: border-color 0.18s ease, background-color 0.18s ease, transform 0.15s ease;
            }

            .deck-pad:hover {
                border-color: var(--border-gold);
                background: rgba(255, 215, 0, 0.1);
            }

            .deck-pad.is-active {
                background: linear-gradient(135deg, rgba(255, 215, 0, 0.28), rgba(212, 175, 55, 0.16));
                border-color: var(--gold-bright);
                color: var(--gold-300);
            }

            .deck-pad-sub {
                font-family: var(--font-mono);
                font-size: 0.64rem;
                color: var(--text-muted);
            }

            /* Section Headers — Big Creative Sans Typography */
            .section-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 1rem;
                margin-block-end: 1.5rem;
            }

            .section-index {
                font-family: var(--font-mono);
                font-size: 0.72rem;
                font-weight: 600;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: var(--gold-bright);
            }

            .section-title {
                margin: 0.35rem 0 0;
                font-family: var(--font-display);
                font-size: clamp(2.1rem, 1.3rem + 3vw, 4.1rem);
                font-weight: 800;
                line-height: 0.98;
                letter-spacing: -0.03em;
                text-wrap: balance;
            }

            .section-aside {
                max-inline-size: 40ch;
                font-size: 0.92rem;
                color: var(--text-muted);
                text-wrap: pretty;
                margin: 0;
            }

            /* Bento Capabilities Grid */
            .bento-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                container-type: inline-size;
            }

            @media (min-width: 48rem) {
                .bento-grid {
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                }
                .span-7 { grid-column: span 7; }
                .span-5 { grid-column: span 5; }
                .span-6 { grid-column: span 6; }
            }

            .glass-card {
                border-radius: var(--radius-card);
                padding: clamp(1.25rem, 1rem + 1vw, 1.85rem);
                background: var(--surface-glass);
                -webkit-backdrop-filter: blur(22px) saturate(145%);
                backdrop-filter: blur(22px) saturate(145%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 1.1rem;
            }

            .card-kicker {
                font-family: var(--font-mono);
                font-size: 0.7rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--gold-400);
            }

            .card-heading {
                margin: 0.45rem 0 0;
                font-family: var(--font-display);
                font-size: clamp(1.45rem, 1.15rem + 1.1vw, 2.15rem);
                font-weight: 700;
                line-height: 1.08;
                letter-spacing: -0.02em;
                text-wrap: balance;
            }

            .card-copy {
                margin: 0.6rem 0 0;
                max-inline-size: 56ch;
                font-size: 0.94rem;
                color: var(--text-secondary);
                text-wrap: pretty;
            }

            .chip-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
            }

            .gold-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem 0.7rem;
                border-radius: var(--radius-pill);
                font-family: var(--font-mono);
                font-size: 0.7rem;
                color: var(--gold-300);
                background: rgba(255, 215, 0, 0.09);
                border: 1px solid rgba(255, 215, 0, 0.25);
            }

            /* 7 Overlay Presets Interactive Explorer */
            .presets-panel {
                border-radius: var(--radius-card);
                padding: clamp(1.15rem, 0.9rem + 1.1vw, 1.9rem);
                background: var(--surface-glass-strong);
                -webkit-backdrop-filter: blur(24px) saturate(150%);
                backdrop-filter: blur(24px) saturate(150%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
                display: grid;
                gap: 1.25rem;
            }

            .preset-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .preset-tab {
                min-block-size: 2.55rem;
                padding: 0.45rem 0.95rem;
                border-radius: var(--radius-pill);
                border: 1px solid rgba(255, 255, 255, 0.14);
                background: rgba(255, 255, 255, 0.04);
                color: var(--text-secondary);
                font: inherit;
                font-size: 0.82rem;
                font-weight: 600;
                cursor: pointer;
                transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease;
            }

            .preset-tab[aria-selected="true"] {
                background: linear-gradient(135deg, var(--gold-bright), var(--gold-400));
                border-color: var(--gold-bright);
                color: #080705;
                font-weight: 700;
            }

            .preset-stage {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1.25rem;
                align-items: center;
            }

            @media (min-width: 56rem) {
                .preset-stage {
                    grid-template-columns: minmax(0, 1.35fr) minmax(0, 0.65fr);
                }
            }

            .preset-canvas {
                position: relative;
                aspect-ratio: 16 / 9;
                border-radius: 1.25rem;
                overflow: hidden;
                border: 1px solid var(--border-gold-strong);
                background:
                    radial-gradient(circle at 18% 22%, rgba(255, 215, 0, 0.25), transparent 50%),
                    radial-gradient(circle at 82% 80%, rgba(212, 175, 55, 0.18), transparent 55%),
                    #070708;
                padding: clamp(1rem, 2.5vw, 1.75rem);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .preset-headline {
                margin: 0.4rem 0 0;
                font-family: var(--font-display);
                font-size: clamp(1.5rem, 1.15rem + 1.8vw, 2.75rem);
                font-weight: 800;
                line-height: 1.02;
                letter-spacing: -0.03em;
                color: var(--text-primary);
            }

            .preset-subheadline {
                margin: 0.4rem 0 0;
                font-size: 0.92rem;
                color: var(--text-secondary);
            }

            /* Closing Statement Banner */
            .cta-banner {
                border-radius: 2rem;
                padding: clamp(1.75rem, 1.2rem + 2.5vw, 3.25rem);
                background:
                    radial-gradient(circle at 85% 20%, rgba(255, 215, 0, 0.25), transparent 45%),
                    linear-gradient(145deg, rgba(24, 20, 12, 0.92), rgba(9, 8, 7, 0.94));
                -webkit-backdrop-filter: blur(24px);
                backdrop-filter: blur(24px);
                border: 1px solid var(--border-gold-strong);
                box-shadow: var(--shadow-glass);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 1.5rem;
            }

            .cta-title {
                margin: 0;
                font-family: var(--font-display);
                font-size: clamp(2rem, 1.3rem + 2.8vw, 3.85rem);
                font-weight: 800;
                line-height: 0.98;
                letter-spacing: -0.03em;
                text-wrap: balance;
            }

            .footer-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.85rem;
                padding-block-start: 1.25rem;
                border-block-start: 1px solid rgba(255, 215, 0, 0.16);
                font-size: 0.82rem;
                color: var(--text-muted);
            }

            @media (forced-colors: active) {
                .site-header,
                .glass-card,
                .presets-panel,
                .phone-shell,
                .cta-banner,
                .kpi-cell {
                    border: 1px solid CanvasText;
                    background: Canvas;
                    color: CanvasText;
                }

                .btn-gold,
                .nav-cta,
                .preset-tab[aria-selected="true"] {
                    background: Highlight;
                    color: HighlightText;
                    border: 1px solid ButtonText;
                }
            }
        </style>
    </head>
    <body>
        <div class="ambient-canvas" aria-hidden="true"></div>
        <div class="ambient-grid" aria-hidden="true"></div>

        <div class="page-shell">
            <header class="site-header" id="site-header">
                <div class="header-inner">
                    <a class="brand-lockup" href="{{ route('home') }}">
                        <span class="brand-mark" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                                <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="brand-name">OBSISSTANT</span>
                        <span class="version-tag">v{{ $currentVersion }}</span>
                    </a>

                    <nav class="site-nav" aria-label="Primary">
                        <button
                            class="menu-button"
                            type="button"
                            popovertarget="site-menu"
                            popovertargetaction="toggle"
                            aria-controls="site-menu"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16"/>
                            </svg>
                            <span>Menu</span>
                        </button>

                        <ul id="site-menu" class="site-menu" popover="auto">
                            <li><a class="nav-link" href="{{ route('home') }}" aria-current="page">Overview</a></li>
                            <li><a class="nav-link" href="{{ url('/admin/obs-control-deck') }}">Control Deck</a></li>
                            <li><a class="nav-link" href="{{ route('obs.deck.index') }}">Mobile Deck</a></li>
                            <li><a class="nav-link" href="{{ route('changelog') }}">Changelog</a></li>
                            <li>
                                <a class="nav-cta" href="{{ url('/admin') }}">
                                    <span>@auth Enter Console @else Studio Login @endauth</span>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </header>

            <main>
                {{-- Hero Section --}}
                <section class="hero-grid" aria-labelledby="hero-heading">
                    <div>
                        <span class="eyebrow">
                            <span class="eyebrow-dot" aria-hidden="true"></span>
                            <span>Laravel Reverb + OBS WebSocket v5</span>
                        </span>

                        <h1 class="hero-display" id="hero-heading">
                            COMMAND
                            <span class="gold-accent">EVERY OBS</span>
                            FRAME.
                        </h1>

                        <p class="hero-lead">
                            Design live glassmorphic broadcast overlays, orchestrate multi-studio OBS rigs behind NAT via outbound Node.js relays, and trigger scenes, macros, and audio from any mobile deck in real time.
                        </p>

                        <div class="hero-actions">
                            <a class="btn-gold" href="{{ url('/admin/obs-control-deck') }}">
                                <span>Launch Control Deck</span>
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14M13 6l6 6-6 6"/>
                                </svg>
                            </a>
                            <a class="btn-glass" href="{{ route('obs.deck.index') }}">
                                <span>Open Mobile Deck</span>
                            </a>
                            <a class="btn-glass" href="{{ route('changelog') }}">
                                <span>v{{ $currentVersion }} Notes</span>
                            </a>
                        </div>

                        <div class="hero-kpi-strip" aria-label="Studio readiness telemetry">
                            <div class="kpi-cell">
                                <div class="kpi-value">{{ $metrics['studios_total'] }}</div>
                                <div class="kpi-label">Active Studios</div>
                            </div>
                            <div class="kpi-cell">
                                <div class="kpi-value">{{ count($presets) }}</div>
                                <div class="kpi-label">Overlay Canvases</div>
                            </div>
                            <div class="kpi-cell">
                                <div class="kpi-value">{{ $metrics['macros_total'] }}</div>
                                <div class="kpi-label">Broadcast Macros</div>
                            </div>
                        </div>
                    </div>

                    {{-- Interactive Glass Mobile Deck Preview --}}
                    <aside class="phone-shell" aria-label="Interactive mobile deck preview">
                        <div class="phone-notch-row">
                            <span class="phone-pill">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8"/>
                                </svg>
                                <span>REVERB LIVE SYNC</span>
                            </span>
                            <span class="version-tag">{{ $metrics['studios_online'] > 0 ? $metrics['studios_online'].' ONLINE' : 'RELAY READY' }}</span>
                        </div>

                        <div class="monitor-stage" id="hero-monitor">
                            <div class="monitor-badge-row">
                                <span class="monitor-live-badge" id="preview-badge">MAIN CAM</span>
                                <span class="version-tag" id="preview-status-chip">PGM · 1080P60</span>
                            </div>

                            <div>
                                <div class="kpi-label" style="margin:0;">Active OBS Program</div>
                                <h2 class="monitor-scene-title" id="preview-scene-name">Main Camera Feed</h2>
                            </div>

                            <div class="monitor-lower-third" id="preview-lower-third">
                                <div>
                                    <div style="font-weight:700;font-size:0.86rem;color:var(--gold-300);" id="preview-guest">Super Admin</div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);" id="preview-role">Host · Obsisstant Studio</div>
                                </div>
                                <span class="version-tag" id="preview-lt-state">VISIBLE</span>
                            </div>
                        </div>

                        <div class="pad-grid" role="group" aria-label="Simulated OBS scene switcher">
                            <button type="button" class="deck-pad is-active" data-scene="Main Camera Feed" data-badge="MAIN CAM">
                                <span>Main Camera</span>
                                <span class="deck-pad-sub">Switch Scene</span>
                            </button>
                            <button type="button" class="deck-pad" data-scene="Starting Soon" data-badge="COUNTDOWN">
                                <span>Starting Soon</span>
                                <span class="deck-pad-sub">Switch Scene</span>
                            </button>
                            <button type="button" class="deck-pad" data-scene="Picture-in-Picture" data-badge="DUAL CAM">
                                <span>Screen + Cam</span>
                                <span class="deck-pad-sub">Switch Scene</span>
                            </button>
                            <button type="button" class="deck-pad" id="toggle-lt-pad">
                                <span>Toggle Lower Third</span>
                                <span class="deck-pad-sub" id="toggle-lt-sub">Overlay: On</span>
                            </button>
                        </div>
                    </aside>
                </section>

                {{-- Capabilities Bento --}}
                <section aria-labelledby="architecture-heading" style="margin-block-start: clamp(3rem, 2rem + 3vw, 5rem);">
                    <div class="section-head">
                        <div>
                            <div class="section-index">01 / STUDIO ARCHITECTURE</div>
                            <h2 class="section-title" id="architecture-heading">ONE GLASS DECK.<br>ZERO FIREWALL HEADACHES.</h2>
                        </div>
                        <p class="section-aside">
                            Engineered to run effortlessly over HTTPS locally on MAMP or in production on cPanel with WebSocket tunneling.
                        </p>
                    </div>

                    <div class="bento-grid">
                        <article class="glass-card span-7">
                            <div>
                                <span class="card-kicker">Outbound Relay (obs-agent)</span>
                                <h3 class="card-heading">Connect Any Local OBS Studio Without Opening Router Ports.</h3>
                                <p class="card-copy">
                                    Each studio connects outbound over HTTPS and <code>wss://</code> to Laravel Reverb using its scoped bearer token while talking locally to OBS WebSocket v5 (<code>127.0.0.1:4455</code>). Credentials never touch client browsers.
                                </p>
                            </div>
                            <div class="chip-row">
                                <span class="gold-chip">obs-websocket-js v5</span>
                                <span class="gold-chip">Multi-Studio Scoped</span>
                                <span class="gold-chip">Signed Channel Auth</span>
                            </div>
                        </article>

                        <article class="glass-card span-5">
                            <div>
                                <span class="card-kicker">Instant Push Overlays</span>
                                <h3 class="card-heading">Browser Sources That Update Mid-Sentence.</h3>
                                <p class="card-copy">
                                    Edit headlines, countdown clocks, tickers, lower thirds, or breaking alert toasts and broadcast straight into OBS via synchronous Reverb events.
                                </p>
                            </div>
                            <div class="chip-row">
                                <span class="gold-chip">/screens/{token}</span>
                                <span class="gold-chip">1-Click Push to OBS</span>
                            </div>
                        </article>

                        <article class="glass-card span-6">
                            <div>
                                <span class="card-kicker">Touch-First Wireless Control</span>
                                <h3 class="card-heading">Your Phone Becomes a Tactile Stream Deck.</h3>
                                <p class="card-copy">
                                    Open the mobile-native deck at <code>/deck</code> on iOS or Android to fire multi-step macros, mute mics, toggle scene items, and transition Studio Mode cleanly.
                                </p>
                            </div>
                            <div class="chip-row">
                                <span class="gold-chip">Safe-Area Mobile UI</span>
                                <span class="gold-chip">Audio Mixer &amp; Macros</span>
                            </div>
                        </article>

                        <article class="glass-card span-6">
                            <div>
                                <span class="card-kicker">Filament v5 Obsidian Cockpit</span>
                                <h3 class="card-heading">All Studios, Screens, Macros &amp; Audit Logs Unified.</h3>
                                <p class="card-copy">
                                    Full desktop and mobile command panel styled in pure obsidian glass and bright gold with real-time telemetry, FPS/CPU stats, and instant alert triggers.
                                </p>
                            </div>
                            <div class="chip-row">
                                <span class="gold-chip">Command Telemetry</span>
                                <span class="gold-chip">Zero-Build Ready</span>
                            </div>
                        </article>
                    </div>
                </section>

                {{-- 7 Screen Presets Interactive Explorer --}}
                <section aria-labelledby="presets-heading" style="margin-block-start: clamp(3rem, 2rem + 3vw, 5rem);">
                    <div class="section-head">
                        <div>
                            <div class="section-index">02 / LIVE SCREEN DESIGNER</div>
                            <h2 class="section-title" id="presets-heading">SEVEN BROADCAST CANVASES.</h2>
                        </div>
                        <p class="section-aside">
                            Select any preset below to inspect its glassmorphism layer, lower-third block, and live overlay state.
                        </p>
                    </div>

                    <div class="presets-panel">
                        <div class="preset-tabs" role="tablist" aria-label="OBS screen overlay presets">
                            @foreach ($presets as $index => $preset)
                                <button
                                    type="button"
                                    class="preset-tab"
                                    role="tab"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                    data-preset='@json($preset)'
                                >
                                    {{ $preset['label'] }}
                                </button>
                            @endforeach
                        </div>

                        @php $firstPreset = $presets[0] ?? ['label' => 'Starting Soon', 'state' => [], 'theme' => []]; @endphp
                        <div class="preset-stage">
                            <div class="preset-canvas">
                                <div class="monitor-badge-row">
                                    <span class="monitor-live-badge" id="preset-badge">{{ $firstPreset['state']['badge_text'] ?? 'UP NEXT' }}</span>
                                    <span class="version-tag" id="preset-bg-tag">{{ ! empty($firstPreset['theme']['transparent_background']) ? 'TRANSPARENT LAYER' : 'FULL CANVAS' }}</span>
                                </div>

                                <div>
                                    <h3 class="preset-headline" id="preset-headline">{{ $firstPreset['state']['headline'] ?? 'Stream Starting Soon' }}</h3>
                                    <p class="preset-subheadline" id="preset-subheadline">{{ $firstPreset['state']['subheadline'] ?? 'Audio checks complete.' }}</p>
                                </div>

                                <div class="monitor-lower-third">
                                    <div>
                                        <div style="font-weight:700;font-size:0.88rem;color:var(--gold-300);" id="preset-guest">{{ $firstPreset['state']['guest_name'] ?? 'Obsisstant Live' }}</div>
                                        <div style="font-size:0.74rem;color:var(--text-muted);" id="preset-role">{{ $firstPreset['state']['guest_role'] ?? 'Studio Production' }}</div>
                                    </div>
                                    <span class="version-tag" id="preset-extra">{{ ! empty($firstPreset['state']['show_countdown']) ? 'TIMER ACTIVE' : (! empty($firstPreset['state']['show_webcam_frame']) ? 'CAM FRAME' : 'REVERB SYNC') }}</span>
                                </div>
                            </div>

                            <div class="glass-card" style="height:auto;">
                                <div>
                                    <span class="card-kicker" id="preset-side-type">{{ $firstPreset['key'] ?? 'starting_soon' }}</span>
                                    <h3 class="card-heading" id="preset-side-title">{{ $firstPreset['label'] }}</h3>
                                    <p class="card-copy" id="preset-side-desc">
                                        Customize colors, backdrop blur, border radius, countdown duration, ticker messages, and push directly into OBS as an active Browser Source.
                                    </p>
                                </div>
                                <div class="hero-actions" style="margin-block-start:0.5rem;">
                                    <a class="btn-gold" href="{{ url('/admin/obs-screens') }}">Open Screen Designer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Closing CTA --}}
                <section style="margin-block-start: clamp(3rem, 2rem + 3vw, 5rem);">
                    <div class="cta-banner">
                        <div>
                            <div class="section-index">03 / READY FOR AIR</div>
                            <h2 class="cta-title">TAKE THE CONTROLS.</h2>
                        </div>
                        <div class="hero-actions" style="margin:0;">
                            <a class="btn-gold" href="{{ url('/admin') }}">Enter Studio Console</a>
                            <a class="btn-glass" href="{{ route('changelog') }}">Release History</a>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="footer-bar">
                <span>Obsisstant Broadcast Control Suite · Version {{ $currentVersion }}</span>
                <span>Obsidian Black &amp; Bright Gold Glass System</span>
            </footer>
        </div>

        <script>
            (() => {
                // Adaptive responsive navbar measurement (ResizeObserver + Popover fallback)
                const header = document.getElementById('site-header');
                const inner = header?.querySelector('.header-inner');
                const menu = document.getElementById('site-menu');
                const nav = header?.querySelector('.site-nav');

                if (header && inner && menu) {
                    let lastCollapsedWidth = 0;

                    const syncNavbar = () => {
                        const wasOpen = menu.matches(':popover-open') || menu.classList.contains(':popover-open');
                        header.classList.add('is-measuring', 'is-wide');
                        menu.removeAttribute('popover');

                        const fitsInline = inner.scrollWidth <= inner.clientWidth + 1;
                        if (fitsInline) {
                            header.classList.add('is-wide');
                        } else {
                            header.classList.remove('is-wide');
                            menu.setAttribute('popover', 'auto');
                            lastCollapsedWidth = header.clientWidth;
                            if (wasOpen && typeof menu.showPopover === 'function') {
                                try { menu.showPopover(); } catch (_) {}
                            }
                        }

                        requestAnimationFrame(() => header.classList.remove('is-measuring'));
                    };

                    syncNavbar();

                    if ('ResizeObserver' in window) {
                        const ro = new ResizeObserver(() => {
                            if (header.classList.contains('is-wide') || header.clientWidth > lastCollapsedWidth + 8) {
                                syncNavbar();
                            }
                        });
                        ro.observe(header);
                        if (nav) {
                            ro.observe(nav);
                        }
                    }
                }

                // Interactive hero deck mockup
                const scenePads = document.querySelectorAll('.deck-pad[data-scene]');
                const sceneTitle = document.getElementById('preview-scene-name');
                const previewBadge = document.getElementById('preview-badge');
                const ltToggleBtn = document.getElementById('toggle-lt-pad');
                const ltBox = document.getElementById('preview-lower-third');
                const ltState = document.getElementById('preview-lt-state');
                const ltSub = document.getElementById('toggle-lt-sub');
                let ltVisible = true;

                scenePads.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        scenePads.forEach((b) => b.classList.remove('is-active'));
                        btn.classList.add('is-active');
                        if (sceneTitle) { sceneTitle.textContent = btn.dataset.scene || 'Main Camera Feed'; }
                        if (previewBadge) { previewBadge.textContent = btn.dataset.badge || 'LIVE'; }
                    });
                });

                ltToggleBtn?.addEventListener('click', () => {
                    ltVisible = !ltVisible;
                    ltBox?.classList.toggle('is-hidden', !ltVisible);
                    if (ltState) { ltState.textContent = ltVisible ? 'VISIBLE' : 'HIDDEN'; }
                    if (ltSub) { ltSub.textContent = ltVisible ? 'Overlay: On' : 'Overlay: Off'; }
                    ltToggleBtn.classList.toggle('is-active', ltVisible);
                });

                // Interactive 7-preset showcase switcher
                const presetTabs = document.querySelectorAll('.preset-tab[data-preset]');
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
                        const data = JSON.parse(tab.dataset.preset || '{}');
                        presetTabs.forEach((t) => t.setAttribute('aria-selected', 'false'));
                        tab.setAttribute('aria-selected', 'true');

                        const st = data.state || {};
                        const th = data.theme || {};

                        if (pBadge) { pBadge.textContent = st.badge_text || 'LIVE'; }
                        if (pBgTag) { pBgTag.textContent = th.transparent_background ? 'TRANSPARENT LAYER' : 'FULL CANVAS'; }
                        if (pHeadline) { pHeadline.textContent = st.headline || data.label || ''; }
                        if (pSubheadline) { pSubheadline.textContent = st.subheadline || ''; }
                        if (pGuest) { pGuest.textContent = st.guest_name || 'Obsisstant Studio'; }
                        if (pRole) { pRole.textContent = st.guest_role || 'Real-Time Overlay'; }
                        if (pExtra) {
                            pExtra.textContent = st.show_countdown ? 'TIMER ACTIVE' : (st.show_webcam_frame ? 'CAM FRAME' : 'REVERB SYNC');
                        }
                        if (pSideType) { pSideType.textContent = data.key || 'custom'; }
                        if (pSideTitle) { pSideTitle.textContent = data.label || 'Custom Overlay'; }
                    });
                });
            })();
        </script>
    </body>
</html>
