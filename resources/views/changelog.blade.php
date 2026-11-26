<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Changelog · Obsisstant v{{ $currentVersion }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;
                --bg-obsidian: #070706;
                --bg-charcoal: #292C31;
                --surface-glass: linear-gradient(155deg, rgba(41, 44, 49, 0.54) 0%, rgba(7, 7, 6, 0.80) 52%, rgba(45, 31, 17, 0.56) 100%);
                --surface-glass-strong: linear-gradient(155deg, rgba(41, 44, 49, 0.68) 0%, rgba(7, 7, 6, 0.88) 54%, rgba(45, 31, 17, 0.66) 100%);
                --surface-chip: rgba(41, 44, 49, 0.42);
                --border-gold: rgba(205, 173, 105, 0.34);
                --border-gold-strong: rgba(244, 225, 133, 0.6);
                --border-subtle: rgba(172, 139, 86, 0.22);
                --gold-100: #F4E185;
                --gold-300: #F4E185;
                --gold-400: #CDAD69;
                --gold-500: #89612B;
                --text-primary: #FAF7EE;
                --text-secondary: #D8CEBA;
                --text-muted: #A99F8D;
                --radius-card: 1.5rem;
                --radius-pill: 9999px;
                --shadow-glass: 0 24px 55px -18px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(244, 225, 133, 0.22);
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                background-color: var(--bg-obsidian);
                color: var(--text-primary);
                font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            body {
                margin: 0;
                min-block-size: 100dvb;
                background-color: var(--bg-obsidian);
                color: var(--text-primary);
                line-height: 1.6;
                position: relative;
                overflow-x: clip;
            }

            /* Ambient backdrop for glassmorphism refraction */
            .ambient-canvas {
                position: fixed;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background:
                    radial-gradient(circle at 14% 10%, rgba(212, 175, 55, 0.2), transparent 40%),
                    radial-gradient(circle at 86% 18%, rgba(161, 98, 7, 0.18), transparent 44%),
                    radial-gradient(circle at 50% 88%, rgba(226, 184, 75, 0.11), transparent 52%),
                    linear-gradient(180deg, #090807 0%, #050506 100%);
            }

            .ambient-grid {
                position: fixed;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                opacity: 0.28;
                background-image:
                    linear-gradient(to right, rgba(212, 175, 55, 0.08) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(212, 175, 55, 0.08) 1px, transparent 1px);
                background-size: 44px 44px;
                mask-image: radial-gradient(ellipse at 50% 25%, black 15%, transparent 78%);
            }

            .sr-only {
                position: absolute;
                inline-size: 1px;
                block-size: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .page-shell {
                position: relative;
                z-index: 1;
                inline-size: min(100%, 72rem);
                margin-inline: auto;
                padding-inline: max(1rem, env(safe-area-inset-left)) max(1rem, env(safe-area-inset-right));
                padding-block-start: max(0.85rem, env(safe-area-inset-top));
                padding-block-end: max(3rem, env(safe-area-inset-bottom));
                display: grid;
                gap: 1.25rem;
            }

            /* Sticky mobile-style frosted glass app bar */
            .app-bar {
                position: sticky;
                top: max(0.75rem, env(safe-area-inset-top));
                z-index: 30;
                display: flex;
                flex-wrap: wrap;
                align-items: safe center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                border-radius: 1.25rem;
                background: rgba(12, 11, 9, 0.76);
                -webkit-backdrop-filter: blur(20px) saturate(145%);
                backdrop-filter: blur(20px) saturate(145%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
            }

            .brand-cluster {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-inline-size: 0;
            }

            .brand-mark {
                display: grid;
                place-items: center;
                inline-size: 2.5rem;
                block-size: 2.5rem;
                flex-shrink: 0;
                border-radius: 0.875rem;
                background: linear-gradient(135deg, rgba(243, 211, 122, 0.25), rgba(161, 98, 7, 0.16));
                border: 1px solid var(--border-gold-strong);
                color: var(--gold-300);
            }

            .brand-text {
                display: grid;
                min-inline-size: 0;
            }

            .brand-title {
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
                font-weight: 700;
                font-size: 1rem;
                letter-spacing: 0.03em;
                color: var(--text-primary);
            }

            .brand-subtitle {
                font-size: 0.78rem;
                color: var(--text-muted);
            }

            .nav-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .version-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.1875rem 0.5625rem;
                border-radius: var(--radius-pill);
                background: rgba(205, 173, 105, 0.12);
                border: 1px solid rgba(205, 173, 105, 0.34);
                color: var(--gold-100);
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
                font-variant-numeric: tabular-nums;
                font-size: 0.75rem;
                font-weight: 500;
                line-height: 1.3;
                text-decoration: none;
            }

            .brand-link {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                color: inherit;
                text-decoration: none;
            }

            .brand-link:hover .brand-title {
                color: var(--gold-100);
            }

            .nav-link {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                min-block-size: 2.75rem;
                padding: 0.5rem 0.95rem;
                border-radius: var(--radius-pill);
                border: 1px solid var(--border-subtle);
                background: var(--surface-chip);
                color: var(--text-primary);
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 500;
                transition: border-color 180ms ease, background-color 180ms ease, color 180ms ease;
            }

            .nav-link:hover {
                border-color: var(--border-gold-strong);
                background: rgba(212, 175, 55, 0.14);
                color: var(--gold-100);
            }

            .nav-link:focus-visible,
            .filter-pill:focus-visible,
            .search-input:focus-visible,
            summary:focus-visible {
                outline: 2px solid var(--gold-300);
                outline-offset: 2px;
            }

            /* Mobile-style Hero Overview Card */
            .hero-card {
                border-radius: var(--radius-card);
                padding: clamp(1.25rem, 3vw, 2rem);
                background: linear-gradient(145deg, rgba(28, 24, 17, 0.82), rgba(10, 10, 11, 0.82));
                -webkit-backdrop-filter: blur(22px) saturate(145%);
                backdrop-filter: blur(22px) saturate(145%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
                display: grid;
                gap: 1.35rem;
            }

            .hero-top {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                font-size: 0.76rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--gold-300);
                margin-block-end: 0.4rem;
            }

            .hero-heading {
                margin: 0;
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
                font-size: clamp(2.15rem, 1.35rem + 2.6vw, 3.75rem);
                font-weight: 700;
                line-height: 1.04;
                letter-spacing: -0.03em;
                text-wrap: balance;
                color: var(--text-primary);
            }

            .hero-description {
                margin: 0.55rem 0 0;
                max-inline-size: 60ch;
                color: var(--text-secondary);
                font-size: 0.95rem;
            }

            .semver-legend {
                display: flex;
                flex-wrap: wrap;
                flex-wrap: balance;
                gap: 0.5rem;
            }

            .legend-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem 0.7rem;
                border-radius: var(--radius-pill);
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid var(--border-subtle);
                font-size: 0.78rem;
                color: var(--text-secondary);
            }

            .legend-chip code {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                color: var(--gold-300);
                font-weight: 600;
            }

            /* Mobile UI stat tiles */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }

            @media (min-width: 640px) {
                .stats-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .stat-tile {
                min-inline-size: 0;
                padding: 0.9rem 1rem;
                border-radius: 1.125rem;
                background: rgba(10, 9, 8, 0.62);
                border: 1px solid rgba(212, 175, 55, 0.18);
                display: grid;
                gap: 0.25rem;
            }

            .stat-label {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.76rem;
                font-weight: 600;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .stat-value {
                font-size: 1.45rem;
                font-weight: 700;
                color: var(--gold-100);
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }

            /* Filter & Search Mobile Toolbar */
            .controls-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: safe center;
                justify-content: space-between;
                gap: 0.85rem;
                padding: 0.9rem 1rem;
                border-radius: 1.25rem;
                background: var(--surface-glass);
                -webkit-backdrop-filter: blur(16px);
                backdrop-filter: blur(16px);
                border: 1px solid var(--border-subtle);
            }

            .filter-group {
                display: flex;
                flex-wrap: wrap;
                flex-wrap: balance;
                gap: 0.45rem;
            }

            .filter-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                min-block-size: 2.75rem;
                padding: 0.45rem 0.95rem;
                border-radius: var(--radius-pill);
                border: 1px solid var(--border-subtle);
                background: rgba(255, 255, 255, 0.04);
                color: var(--text-secondary);
                font: inherit;
                font-size: 0.84rem;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 180ms ease, border-color 180ms ease, color 180ms ease;
            }

            .filter-pill:hover {
                border-color: var(--border-gold);
                color: var(--text-primary);
            }

            .filter-pill[aria-pressed="true"] {
                background: linear-gradient(135deg, rgba(226, 184, 75, 0.24), rgba(161, 98, 7, 0.2));
                border-color: var(--border-gold-strong);
                color: var(--gold-100);
            }

            .search-wrap {
                position: relative;
                flex: 1 1 14rem;
                max-inline-size: 22rem;
                min-inline-size: 0;
            }

            .search-icon {
                position: absolute;
                inset-inline-start: 0.85rem;
                inset-block-start: 50%;
                transform: translateY(-50%);
                color: var(--gold-300);
                pointer-events: none;
            }

            .search-input {
                inline-size: 100%;
                min-block-size: 2.75rem;
                padding-inline: 2.5rem 0.95rem;
                border-radius: var(--radius-pill);
                border: 1px solid var(--border-subtle);
                background: rgba(7, 7, 8, 0.78);
                color: var(--text-primary);
                font: inherit;
                font-size: 0.88rem;
            }

            .search-input::placeholder {
                color: var(--text-muted);
            }

            /* Responsive Release Cards Grid */
            .releases-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
            }

            @media (min-width: 820px) {
                .releases-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .release-card {
                container-type: inline-size;
                position: relative;
                border-radius: var(--radius-card);
                padding: 1.25rem;
                background: var(--surface-glass-strong);
                -webkit-backdrop-filter: blur(18px) saturate(140%);
                backdrop-filter: blur(18px) saturate(140%);
                border: 1px solid var(--border-gold);
                box-shadow: var(--shadow-glass);
                display: grid;
                gap: 1rem;
                align-content: start;
                transition: transform 200ms ease, border-color 200ms ease;
            }

            .release-card:hover {
                border-color: var(--border-gold-strong);
            }

            .release-card--featured {
                grid-column: 1 / -1;
                background: linear-gradient(150deg, rgba(31, 25, 16, 0.88), rgba(11, 10, 9, 0.84));
                border-color: var(--border-gold-strong);
            }

            .release-header {
                display: grid;
                gap: 0.75rem;
            }

            @container (inline-size > 520px) {
                .release-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                }
            }

            .release-meta-left {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.55rem;
                min-inline-size: 0;
            }

            .release-version {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.8rem;
                border-radius: var(--radius-pill);
                background: linear-gradient(135deg, #f3d37a, #c9972c);
                color: #141008;
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-size: 0.86rem;
                font-weight: 700;
            }

            .type-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem 0.7rem;
                border-radius: var(--radius-pill);
                font-size: 0.76rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                border: 1px solid transparent;
            }

            .type-badge--major {
                background: rgba(243, 211, 122, 0.14);
                border-color: rgba(243, 211, 122, 0.42);
                color: var(--gold-100);
            }

            .type-badge--minor {
                background: rgba(226, 184, 75, 0.12);
                border-color: rgba(226, 184, 75, 0.35);
                color: var(--gold-300);
            }

            .type-badge--macro {
                background: rgba(255, 255, 255, 0.06);
                border-color: rgba(255, 255, 255, 0.22);
                color: var(--text-secondary);
            }

            .latest-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.28rem 0.65rem;
                border-radius: var(--radius-pill);
                background: rgba(243, 211, 122, 0.12);
                border: 1px dashed var(--border-gold-strong);
                color: var(--gold-300);
                font-size: 0.74rem;
                font-weight: 600;
            }

            .release-date {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                color: var(--text-muted);
                font-size: 0.84rem;
                font-family: 'JetBrains Mono', ui-monospace, monospace;
            }

            .release-summary {
                margin: 0;
                padding: 0.8rem 1rem;
                border-radius: 1rem;
                background: rgba(255, 255, 255, 0.035);
                border-inline-start: 3px solid var(--gold-400);
                color: var(--text-primary);
                font-size: 0.96rem;
                font-weight: 500;
            }

            .sections-stack {
                display: grid;
                gap: 0.85rem;
            }

            .section-card {
                border-radius: 1.125rem;
                background: rgba(8, 8, 9, 0.56);
                border: 1px solid rgba(255, 255, 255, 0.08);
                padding: 0.9rem 1rem;
            }

            .section-summary {
                list-style: none;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.88rem;
                color: var(--gold-300);
            }

            .section-summary::-webkit-details-marker {
                display: none;
            }

            .section-title {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .section-count {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-size: 0.75rem;
                padding: 0.15rem 0.55rem;
                border-radius: var(--radius-pill);
                background: rgba(212, 175, 55, 0.14);
                color: var(--gold-100);
            }

            .change-list {
                margin: 0.8rem 0 0;
                padding: 0;
                list-style: none;
                display: grid;
                gap: 0.65rem;
            }

            .change-item {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                align-items: start;
                gap: 0.65rem;
                font-size: 0.9rem;
                color: var(--text-secondary);
            }

            .change-bullet {
                margin-block-start: 0.42rem;
                inline-size: 0.45rem;
                block-size: 0.45rem;
                border-radius: 9999px;
                background: linear-gradient(135deg, #f3d37a, #c9972c);
                box-shadow: 0 0 8px rgba(243, 211, 122, 0.45);
            }

            .change-title {
                color: var(--text-primary);
                font-weight: 600;
            }

            .inline-code {
                font-family: 'JetBrains Mono', ui-monospace, monospace;
                font-size: 0.83em;
                padding: 0.12rem 0.42rem;
                border-radius: 0.4rem;
                background: rgba(212, 175, 55, 0.14);
                border: 1px solid rgba(212, 175, 55, 0.24);
                color: var(--gold-100);
            }

            .empty-state {
                grid-column: 1 / -1;
                padding: 2.25rem 1.25rem;
                text-align: center;
                border-radius: var(--radius-card);
                background: var(--surface-glass);
                border: 1px dashed var(--border-gold);
                color: var(--text-secondary);
            }

            .empty-state[hidden],
            .release-card[hidden] {
                display: none;
            }

            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    transition-duration: 0.01ms !important;
                    animation-duration: 0.01ms !important;
                }
            }

            @media (forced-colors: active) {
                .app-bar,
                .hero-card,
                .controls-bar,
                .release-card,
                .stat-tile,
                .section-card {
                    background: Canvas;
                    color: CanvasText;
                    border: 1px solid CanvasText;
                }

                .filter-pill[aria-pressed="true"],
                .release-version {
                    background: Highlight;
                    color: HighlightText;
                }
            }
        </style>
    </head>
    <body>
        <div class="ambient-canvas" aria-hidden="true"></div>
        <div class="ambient-grid" aria-hidden="true"></div>

        <div class="page-shell">
            <header class="app-bar">
                <div class="brand-cluster">
                    <a href="{{ route('home') }}" class="brand-link">
                        <div class="brand-mark" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                                <circle cx="12" cy="12" r="2.5" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="brand-text">
                            <span class="brand-title">Obsisstant</span>
                            <span class="brand-subtitle">Release Ledger</span>
                        </div>
                    </a>
                    <span class="version-pill" title="Current active release">v{{ $currentVersion }}</span>
                </div>

                <nav class="nav-actions" aria-label="Changelog navigation">
                    <a class="nav-link" href="{{ url('/') }}">Home</a>
                    <a class="nav-link" href="{{ route('knowledge-base') }}">Knowledge Base</a>
                    <a class="nav-link" href="{{ url('/admin') }}">Admin Panel</a>
                </nav>
            </header>

            <main style="display: grid; gap: 1.25rem;">
                <section class="hero-card" aria-labelledby="changelog-heading">
                    <div class="hero-top">
                        <div>
                            <div class="eyebrow">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 7v5l3 2"/>
                                </svg>
                                <span>Live from changelog.md</span>
                            </div>
                            <h1 id="changelog-heading" class="hero-heading">Product Releases &amp; Updates</h1>
                            <p class="hero-description">
                                Every architectural milestone, feature rollout, and incremental patch tracked cleanly under Semantic Versioning.
                            </p>
                        </div>

                        <div class="semver-legend" aria-label="Semantic versioning legend">
                            <span class="legend-chip"><code>X.0.0</code> Major</span>
                            <span class="legend-chip"><code>X.Y.0</code> Minor</span>
                            <span class="legend-chip"><code>X.Y.Z</code> Macro</span>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-tile">
                            <span class="stat-label">Releases</span>
                            <span class="stat-value">{{ $stats['total'] }}</span>
                        </div>
                        <div class="stat-tile">
                            <span class="stat-label">Major</span>
                            <span class="stat-value">{{ $stats['major'] }}</span>
                        </div>
                        <div class="stat-tile">
                            <span class="stat-label">Minor</span>
                            <span class="stat-value">{{ $stats['minor'] }}</span>
                        </div>
                        <div class="stat-tile">
                            <span class="stat-label">Macro</span>
                            <span class="stat-value">{{ $stats['macro'] }}</span>
                        </div>
                    </div>
                </section>

                <section class="controls-bar" aria-label="Filter releases">
                    <div class="filter-group" role="group" aria-label="Filter by release type">
                        <button type="button" class="filter-pill" data-filter="all" aria-pressed="true">All</button>
                        <button type="button" class="filter-pill" data-filter="major" aria-pressed="false">Major</button>
                        <button type="button" class="filter-pill" data-filter="minor" aria-pressed="false">Minor</button>
                        <button type="button" class="filter-pill" data-filter="macro" aria-pressed="false">Macro</button>
                    </div>

                    <div class="search-wrap">
                        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m20 20-3.5-3.5"/>
                        </svg>
                        <label class="sr-only" for="changelog-search">Search releases</label>
                        <input
                            id="changelog-search"
                            type="search"
                            class="search-input"
                            placeholder="Search version, feature, or change..."
                            autocomplete="off"
                        >
                    </div>
                </section>

                <p id="results-status" class="sr-only" aria-live="polite">Showing {{ count($releases) }} releases.</p>

                <section class="releases-grid" aria-label="Release timeline">
                    @forelse ($releases as $index => $release)
                        @php
                            $searchParts = [$release['tag'], $release['type'], $release['date'], $release['summary']];
                            foreach ($release['sections'] as $section) {
                                $searchParts[] = $section['category'];
                                foreach ($section['items'] as $item) {
                                    $searchParts[] = ($item['title'] ?? '').' '.$item['body'];
                                }
                            }
                            $searchIndex = mb_strtolower(implode(' ', $searchParts));
                        @endphp
                        <article
                            class="release-card {{ $index === 0 ? 'release-card--featured' : '' }}"
                            data-type="{{ $release['type'] }}"
                            data-search="{{ $searchIndex }}"
                        >
                            <header class="release-header">
                                <div class="release-meta-left">
                                    <span class="release-version">{{ $release['tag'] }}</span>
                                    <span class="type-badge type-badge--{{ $release['type'] }}">
                                        @if ($release['type'] === 'major')
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                <path d="M12 2l3 7h7l-5.5 4.2 2.1 6.8L12 16l-6.6 4 2.1-6.8L2 9h7l3-7z"/>
                                            </svg>
                                        @elseif ($release['type'] === 'minor')
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                <path d="M12 5v14M5 12h14"/>
                                            </svg>
                                        @else
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-5.6 5.6a1.5 1.5 0 0 0 2.1 2.1l5.6-5.6a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.1-2.1 2.2-2.2z"/>
                                            </svg>
                                        @endif
                                        <span>{{ ucfirst($release['type']) }}</span>
                                    </span>

                                    @if ($index === 0)
                                        <span class="latest-badge">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                <path d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Latest
                                        </span>
                                    @endif
                                </div>

                                <time class="release-date" datetime="{{ $release['date'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <path d="M16 2v4M8 2v4M3 10h18"/>
                                    </svg>
                                    {{ $release['date'] }}
                                </time>
                            </header>

                            @if ($release['summary'] !== '')
                                <p class="release-summary">{{ $release['summary'] }}</p>
                            @endif

                            <div class="sections-stack">
                                @foreach ($release['sections'] as $section)
                                    <details class="section-card" open>
                                        <summary class="section-summary">
                                            <span class="section-title">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path d="M9 11l3 3L22 4"/>
                                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                                </svg>
                                                {{ $section['category'] }}
                                            </span>
                                            <span class="section-count">{{ count($section['items']) }}</span>
                                        </summary>

                                        <ul class="change-list">
                                            @foreach ($section['items'] as $item)
                                                <li class="change-item">
                                                    <span class="change-bullet" aria-hidden="true"></span>
                                                    <span>
                                                        @if ($item['title'])
                                                            <strong class="change-title">{{ $item['title'] }}:</strong>
                                                        @endif
                                                        {!! $item['body_html'] !!}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <p>No releases have been recorded in <code>changelog.md</code> yet.</p>
                        </div>
                    @endforelse

                    <div id="filter-empty-state" class="empty-state" role="status" hidden>
                        <p>No changelog entries match the selected filter or search query.</p>
                    </div>
                </section>
            </main>
        </div>

        <script>
            (() => {
                const buttons = Array.from(document.querySelectorAll('.filter-pill'));
                const cards = Array.from(document.querySelectorAll('.release-card'));
                const searchInput = document.getElementById('changelog-search');
                const emptyState = document.getElementById('filter-empty-state');
                const statusEl = document.getElementById('results-status');
                let activeFilter = 'all';

                const applyFilters = () => {
                    const query = (searchInput?.value ?? '').trim().toLowerCase();
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const type = card.dataset.type ?? '';
                        const text = card.dataset.search ?? '';
                        const matchesType = activeFilter === 'all' || type === activeFilter;
                        const matchesSearch = query === '' || text.includes(query);
                        const show = matchesType && matchesSearch;

                        card.hidden = !show;
                        if (show) {
                            visibleCount += 1;
                        }
                    });

                    if (emptyState) {
                        emptyState.hidden = visibleCount > 0 || cards.length === 0;
                    }

                    if (statusEl) {
                        statusEl.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'release' : 'releases'}.`;
                    }
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        activeFilter = button.dataset.filter ?? 'all';
                        buttons.forEach((btn) => {
                            btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false');
                        });
                        applyFilters();
                    });
                });

                searchInput?.addEventListener('input', applyFilters);
            })();
        </script>
    </body>
</html>
