<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Pricing &amp; Demo — {{ config('app.name', 'Obsisstant') }}</title>
        <x-app-favicon />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:200,300,400,500,600,700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-base: #070706;
                --surface-glass: linear-gradient(155deg, rgba(25, 23, 19, 0.72) 0%, rgba(12, 11, 9, 0.88) 100%);
                --gold-light: #F4E185;
                --gold-mid: #CDAD69;
                --gold-border: rgba(205, 173, 105, 0.34);
                --ink-primary: #FAF7EE;
                --ink-muted: #B6AD9C;
                --positive: #39D98A;
            }

            * { box-sizing: border-box; }

            html {
                scroll-behavior: smooth;
                color-scheme: dark;
                background: var(--bg-base);
            }

            body {
                margin: 0;
                min-block-size: 100dvh;
                font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
                color: var(--ink-primary);
                background:
                    radial-gradient(60rem 36rem at 15% -6%, rgba(205, 173, 105, 0.14), transparent 65%),
                    radial-gradient(50rem 34rem at 88% 12%, rgba(244, 225, 133, 0.08), transparent 68%),
                    var(--bg-base);
                line-height: 1.55;
            }

            ::selection {
                background: linear-gradient(135deg, #F6E496, #C89F4C);
                background-color: #E2C275;
                color: #070706;
            }

            .site-header {
                position: sticky;
                top: 0;
                z-index: 40;
                background: rgba(8, 8, 7, 0.78);
                -webkit-backdrop-filter: blur(18px);
                backdrop-filter: blur(18px);
                border-block-end: 1px solid rgba(205, 173, 105, 0.18);
            }

            .header-inner {
                max-inline-size: 74rem;
                margin-inline: auto;
                padding: 0.85rem 1.25rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .header-nav {
                display: flex;
                align-items: center;
                gap: 1.15rem;
                flex-wrap: wrap;
            }

            .header-nav a {
                color: var(--ink-muted);
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 500;
                padding: 0.35rem 0.55rem;
                border-radius: 0.45rem 0.45rem 0 0;
                transition: color 150ms ease, background-color 150ms ease;
            }

            .header-nav a:hover,
            .header-nav a[aria-current="page"] {
                color: var(--gold-light);
            }

            .header-nav a[aria-current="page"] {
                background: rgba(205, 173, 105, 0.1);
                box-shadow: inset 0 -2px 0 var(--gold-light);
            }

            .nav-cta {
                padding: 0.45rem 0.95rem;
                border-radius: 9999px;
                background: linear-gradient(180deg, #F6E496 0%, #D9B768 55%, #B89143 100%);
                color: #070706 !important;
                font-weight: 600 !important;
            }

            .container {
                max-inline-size: 74rem;
                margin-inline: auto;
                padding: 3rem 1.25rem 5rem;
                display: grid;
                gap: 3.5rem;
            }

            .hero-block {
                max-inline-size: 46rem;
            }

            .eyebrow {
                display: inline-block;
                font-size: 0.74rem;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: var(--gold-light);
                margin-bottom: 0.65rem;
            }

            h1 {
                margin: 0 0 0.85rem;
                font-size: clamp(2.1rem, 4.6vw, 3.4rem);
                font-weight: 200;
                line-height: 1.08;
                letter-spacing: -0.025em;
            }

            .lead {
                margin: 0;
                color: var(--ink-muted);
                font-size: 1rem;
                max-inline-size: 38rem;
            }

            .plans-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(18.5rem, 1fr));
                gap: 1.25rem;
                align-items: stretch;
            }

            .plan-card {
                display: grid;
                grid-template-rows: auto auto auto 1fr auto;
                gap: 1.15rem;
                padding: 1.5rem;
                border-radius: 1.35rem;
                background: var(--surface-glass);
                -webkit-backdrop-filter: blur(20px);
                backdrop-filter: blur(20px);
                border: 1px solid var(--gold-border);
                box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.18), 0 22px 46px -22px rgba(0, 0, 0, 0.9);
            }

            .plan-card--featured {
                border-color: rgba(244, 225, 133, 0.7);
                box-shadow: inset 0 1px 0 rgba(255, 246, 196, 0.28), 0 0 36px -14px rgba(205, 173, 105, 0.35);
            }

            .plan-name {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 500;
                color: var(--gold-light);
            }

            .plan-desc {
                margin: 0.35rem 0 0;
                font-size: 0.85rem;
                color: var(--ink-muted);
            }

            .plan-price {
                font-size: 1.85rem;
                font-weight: 300;
                letter-spacing: -0.02em;
                color: var(--ink-primary);
            }

            .section-label {
                font-size: 0.72rem;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--gold-mid);
                margin-bottom: 0.45rem;
            }

            .quota-list,
            .feature-list {
                list-style: none;
                margin: 0;
                padding: 0;
                display: grid;
                gap: 0.42rem;
                font-size: 0.84rem;
            }

            .quota-list li {
                display: flex;
                justify-content: space-between;
                gap: 0.75rem;
                padding-block-end: 0.3rem;
                border-block-end: 1px solid rgba(255, 255, 255, 0.06);
                color: var(--ink-muted);
            }

            .quota-list strong {
                color: var(--ink-primary);
                font-weight: 500;
            }

            .feature-list li {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .feature-on { color: var(--ink-primary); }
            .feature-off { color: rgba(182, 173, 156, 0.48); text-decoration: line-through; }

            .btn-gold {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-block-size: 2.7rem;
                padding: 0.55rem 1.25rem;
                border-radius: 9999px;
                border: 1px solid rgba(244, 225, 133, 0.65);
                background: linear-gradient(180deg, #F6E496 0%, #D9B768 48%, #B89143 100%);
                color: #070706;
                font-family: inherit;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .demo-panel {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
                gap: 2rem;
                padding: 2rem;
                border-radius: 1.5rem;
                background: var(--surface-glass);
                -webkit-backdrop-filter: blur(22px);
                backdrop-filter: blur(22px);
                border: 1px solid var(--gold-border);
            }

            @media (max-width: 52rem) {
                .demo-panel { grid-template-columns: 1fr; padding: 1.35rem; }
            }

            .demo-form {
                display: grid;
                gap: 0.9rem;
            }

            .form-row-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.85rem;
            }

            @media (max-width: 38rem) {
                .form-row-2 { grid-template-columns: 1fr; }
            }

            label {
                display: grid;
                gap: 0.35rem;
                font-size: 0.8rem;
                color: var(--ink-muted);
            }

            input, select, textarea {
                inline-size: 100%;
                padding: 0.62rem 0.85rem;
                border-radius: 0.7rem;
                border: 1px solid rgba(205, 173, 105, 0.3);
                background: rgba(9, 9, 8, 0.9);
                color: var(--ink-primary);
                font-family: inherit;
                font-size: 0.875rem;
            }

            .alert-success {
                padding: 0.85rem 1rem;
                border-radius: 0.85rem;
                border: 1px solid rgba(57, 217, 138, 0.45);
                background: rgba(57, 217, 138, 0.1);
                color: #B7F7D4;
                font-size: 0.875rem;
            }

            .alert-error {
                padding: 0.85rem 1rem;
                border-radius: 0.85rem;
                border: 1px solid rgba(255, 107, 107, 0.45);
                background: rgba(255, 107, 107, 0.1);
                color: #FFD1D1;
                font-size: 0.85rem;
            }
        </style>
    </head>
    <body>
        <header class="site-header">
            <div class="header-inner">
                <a href="{{ route('home') }}" style="text-decoration: none;">
                    <x-brand-logo />
                </a>
                <nav class="header-nav" aria-label="Pricing navigation">
                    <a href="{{ route('home') }}">Overview</a>
                    <a href="{{ route('pricing') }}" aria-current="page">Pricing</a>
                    <a href="{{ route('knowledge-base') }}">Knowledge Base</a>
                    <a href="{{ route('changelog') }}">Changelog</a>
                    <a href="#apply-demo" class="nav-cta">Apply for Demo</a>
                </nav>
            </div>
        </header>

        <main class="container">
            <section class="hero-block">
                <span class="eyebrow">Packages &amp; Onboarding</span>
                <h1>Transparent plans built for live shows.</h1>
                <p class="lead">
                    Every account is provisioned personally after a quick demo so your OBS studio, stage overlays, and quotas are tailored from day one.
                </p>
            </section>

            <section class="plans-grid" aria-label="Available packages">
                @forelse ($packages as $index => $package)
                    <article class="plan-card {{ $index === 1 ? 'plan-card--featured' : '' }}">
                        <div>
                            <h2 class="plan-name">{{ $package->name }}</h2>
                            @if ($package->description)
                                <p class="plan-desc">{{ $package->description }}</p>
                            @endif
                        </div>

                        <div class="plan-price">{{ $package->formattedPrice() }}</div>

                        <div>
                            <div class="section-label">Quotas</div>
                            <ul class="quota-list">
                                @foreach ($limitCatalog as $key => $label)
                                    @php
                                        $val = $package->limits[$key] ?? null;
                                        $display = ($val === null || $val === '' || (int) $val < 0)
                                            ? 'Unlimited'
                                            : ($key === 'max_storage_mb' ? number_format((int) $val).' MB' : number_format((int) $val));
                                    @endphp
                                    <li>
                                        <span>{{ $label }}</span>
                                        <strong>{{ $display }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div>
                            <div class="section-label">Included Modules</div>
                            <ul class="feature-list">
                                @foreach ($featureCatalog as $key => $label)
                                    @php $enabled = (bool) ($package->features[$key] ?? false); @endphp
                                    <li class="{{ $enabled ? 'feature-on' : 'feature-off' }}">
                                        <span aria-hidden="true">{{ $enabled ? '✓' : '—' }}</span>
                                        <span>{{ $label }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <a
                            href="#apply-demo"
                            class="btn-gold"
                            onclick="document.getElementById('demo-package-select').value='{{ $package->id }}';"
                        >
                            Apply for {{ $package->name }}
                        </a>
                    </article>
                @empty
                    <p style="color: var(--ink-muted);">Packages are being updated—apply for a demo below and we will set up a custom plan.</p>
                @endforelse
            </section>

            <section id="apply-demo" class="demo-panel" aria-labelledby="demo-heading">
                <div>
                    <span class="eyebrow">Request Access</span>
                    <h2 id="demo-heading" style="margin: 0 0 0.75rem; font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 200; line-height: 1.15;">
                        Apply for a live demo.
                    </h2>
                    <p style="margin: 0; color: var(--ink-muted); font-size: 0.92rem;">
                        Tell us about your stream or broadcast setup. Once approved, we'll activate your studio account with the right package and quotas.
                    </p>
                </div>

                <form method="POST" action="{{ route('demo-requests.store') }}" class="demo-form">
                    @csrf
                    @if (session('demo_submitted'))
                        <div class="alert-success">{{ session('demo_submitted') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-row-2">
                        <label>
                            <span>Your Name *</span>
                            <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Alex Rivera">
                        </label>
                        <label>
                            <span>Email Address *</span>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="180" placeholder="you@studio.com">
                        </label>
                    </div>

                    <div class="form-row-2">
                        <label>
                            <span>Phone / WhatsApp</span>
                            <input type="text" name="phone" value="{{ old('phone') }}" maxlength="40" placeholder="+977 98...">
                        </label>
                        <label>
                            <span>Channel or Organization</span>
                            <input type="text" name="channel_or_org" value="{{ old('channel_or_org') }}" maxlength="150" placeholder="Studio / Esports / Church">
                        </label>
                    </div>

                    <label>
                        <span>Preferred Package</span>
                        <select id="demo-package-select" name="package_id">
                            <option value="">Not sure yet — recommend one</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}" @selected((string) old('package_id') === (string) $package->id)>
                                    {{ $package->name }} ({{ $package->formattedPrice() }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Tell us about your show</span>
                        <textarea name="message" rows="3" maxlength="1200" placeholder="Number of cameras/PCs, overlays needed, or target launch date...">{{ old('message') }}</textarea>
                    </label>

                    <div>
                        <button type="submit" class="btn-gold">Submit Demo Request</button>
                    </div>
                </form>
            </section>
        </main>

        <x-obs-mascot mode="floating-assistant" />
    </body>
</html>
