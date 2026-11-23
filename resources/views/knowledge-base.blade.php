<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Knowledge Base · Obsisstant v{{ $currentVersion }}</title>
        <meta name="description" content="Setup instructions, Broadcast Macro automation, Overlay Studio reference, and searchable OBS WebSocket v5 commands.">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600&display=swap" rel="stylesheet">
        <x-app-favicon />
        <x-dev-hot-reload />
        <style>
            :root {
                color-scheme: dark;
                --bg-canvas: #070706;
                --bg-raised: #1B1A17;
                --gold-accent: #CDAD69;
                --gold-light: #F4E185;
                --hairline: rgba(205, 173, 105, 0.14);
                --hairline-strong: rgba(205, 173, 105, 0.34);
                --ink-primary: #FAF7EE;
                --ink-secondary: #C8C0AF;
                --ink-muted: #8E8777;
                --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
            }

            *, *::before, *::after {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
                background-color: var(--bg-canvas);
                color: var(--ink-primary);
                font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, sans-serif;
                -webkit-font-smoothing: antialiased;
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
                line-height: 1.6;
                overflow-x: clip;
            }

            .container {
                inline-size: min(100%, 76rem);
                margin-inline: auto;
                padding-inline: max(1rem, env(safe-area-inset-left)) max(1rem, env(safe-area-inset-right));
                padding-block: 1.5rem 3rem;
            }

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
                padding-inline: max(1rem, env(safe-area-inset-left)) max(1rem, env(safe-area-inset-right));
                padding-block: 0.5rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .brand-cluster {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .brand-lockup {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--ink-primary);
                text-decoration: none;
                font-size: 1rem;
                font-weight: 600;
                letter-spacing: -0.01em;
            }

            .brand-lockup:hover {
                color: var(--gold-light);
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

            .header-end {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.625rem;
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
                border: 1px solid rgba(255, 255, 255, 0.08);
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
                background-color: #4CC38A;
            }

            .obs-reverb-pill.is-offline {
                border-color: rgba(229, 72, 77, 0.32);
                background-color: rgba(229, 72, 77, 0.08);
                color: #F4A3A6;
            }

            .obs-reverb-pill.is-offline .obs-reverb-dot {
                background-color: #E5484D;
            }

            .header-nav {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.25rem;
            }

            .header-nav a {
                display: inline-flex;
                align-items: center;
                min-block-size: 2.25rem;
                padding: 0.375rem 0.75rem;
                border-radius: 0.5rem;
                color: var(--ink-secondary);
                text-decoration: none;
                font-size: 0.8125rem;
                font-weight: 500;
            }

            .header-nav a:hover {
                background-color: var(--bg-raised);
                color: var(--ink-primary);
            }

            .header-nav a[aria-current="page"] {
                color: var(--ink-primary);
                box-shadow: inset 0 -2px 0 var(--gold-accent);
                border-radius: 0.5rem 0.5rem 0 0;
            }

            .header-nav .nav-cta {
                background-image: linear-gradient(180deg, #DFC27D 0%, #CDAD69 100%);
                color: #070706;
                font-weight: 600;
                border: 1px solid rgba(244, 225, 133, 0.45);
            }
        </style>
        <x-knowledge-base-styles />
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <div class="brand-cluster">
                    <a href="{{ route('home') }}" class="brand-lockup">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CDAD69" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                            <circle cx="12" cy="12" r="2.5" fill="#CDAD69"/>
                        </svg>
                        <span>Obsisstant</span>
                    </a>
                    <a href="{{ route('changelog') }}" class="obs-brand-version-pill" title="View release changelog">v{{ $currentVersion }}</a>
                </div>

                <div class="header-end">
                    <x-reverb-status-pill />

                    <nav class="header-nav" aria-label="Knowledge Base navigation">
                        <a href="{{ route('home') }}">Overview</a>
                        <a href="{{ route('knowledge-base') }}" aria-current="page">Knowledge Base</a>
                        <a href="{{ route('changelog') }}">Changelog</a>
                        <a href="{{ url('/admin/obs-control-deck') }}" class="nav-cta">Control Deck</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="container">
            <x-knowledge-base-workspace
                :articles="$articles"
                :commandReference="$commandReference"
                :activeSlug="$activeSlug"
                :macroCreateUrl="url('/admin/obs-macros/create')"
            />
        </main>
    </body>
</html>
