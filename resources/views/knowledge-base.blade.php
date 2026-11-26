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
                background:
                    radial-gradient(circle at 12% 0%, rgba(205, 173, 105, 0.14), transparent 42%),
                    radial-gradient(circle at 88% 12%, rgba(244, 225, 133, 0.07), transparent 38%),
                    var(--bg-canvas);
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
                background: rgba(12, 11, 9, 0.74);
                -webkit-backdrop-filter: blur(20px) saturate(145%);
                backdrop-filter: blur(20px) saturate(145%);
                border-block-end: 1px solid rgba(205, 173, 105, 0.24);
                box-shadow: inset 0 -1px 0 rgba(244, 225, 133, 0.08), 0 12px 32px -18px rgba(0, 0, 0, 0.85);
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
                background-image: linear-gradient(180deg, #F6E496 0%, #D9B768 48%, #B89143 100%);
                color: #070706;
                font-weight: 600;
                border: 1px solid rgba(244, 225, 133, 0.65);
                box-shadow: inset 0 1px 0 rgba(255, 252, 232, 0.65), 0 6px 18px -8px rgba(205, 173, 105, 0.45);
            }
        </style>
        <x-knowledge-base-styles />
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <div class="brand-cluster">
                    <a href="{{ route('home') }}" class="brand-lockup">
                        <x-brand-logo :showVersion="false" />
                    </a>
                    <a href="{{ route('changelog') }}" class="obs-brand-version-pill" title="View release changelog">v{{ $currentVersion }}</a>
                </div>

                <div class="header-end">
                    <nav class="header-nav" aria-label="Knowledge Base navigation">
                        <a href="{{ route('home') }}">Overview</a>
                        <a href="{{ route('pricing') }}">Pricing</a>
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
        <x-obs-mascot mode="floating-assistant" />
    </body>
</html>
