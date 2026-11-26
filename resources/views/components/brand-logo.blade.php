@props([
    'suffix' => null,
    'badge' => null,
    'showVersion' => true,
])

<span {{ $attributes->class(['obs-iconic-brand']) }}>
    <span class="obs-iconic-emblem" aria-hidden="true">
        <svg class="obs-iconic-svg" width="26" height="26" viewBox="0 0 40 40" fill="none">
            <defs>
                <linearGradient id="obsBrandGoldGrad" x1="4" y1="4" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#FFF6C4"/>
                    <stop offset="40%" stop-color="#E7C978"/>
                    <stop offset="75%" stop-color="#B88E3D"/>
                    <stop offset="100%" stop-color="#F6E496"/>
                </linearGradient>
            </defs>
            <circle class="obs-emblem-orbit" cx="20" cy="20" r="16.5" stroke="url(#obsBrandGoldGrad)" stroke-width="1.5" stroke-dasharray="6 4" opacity="0.82"/>
            <circle cx="20" cy="20" r="11.2" stroke="url(#obsBrandGoldGrad)" stroke-width="1.65"/>
            <path d="M20 2.5v5.2M20 32.3v5.2M2.5 20h5.2M32.3 20h5.2" stroke="url(#obsBrandGoldGrad)" stroke-width="1.7" stroke-linecap="round"/>
            <circle class="obs-emblem-core" cx="20" cy="20" r="4.2" fill="url(#obsBrandGoldGrad)"/>
        </svg>
    </span>
    <span class="obs-iconic-wordmark">
        <span class="obs-word-obs">Obs</span><span class="obs-word-isstant">isstant</span>@if ($suffix)<span class="obs-word-suffix"> {{ $suffix }}</span>@endif
    </span>
    @if ($badge)
        <span class="obs-brand-version-pill">{{ $badge }}</span>
    @elseif ($showVersion)
        <span class="obs-brand-version-pill" title="Current version">v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    @endif
</span>

@once
    <style>
        .obs-iconic-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }

        .obs-iconic-emblem {
            display: inline-grid;
            place-items: center;
            inline-size: 2rem;
            block-size: 2rem;
            border-radius: 0.65rem;
            background: radial-gradient(circle at 30% 25%, rgba(244, 225, 133, 0.2), rgba(14, 13, 11, 0.92));
            border: 1px solid rgba(244, 225, 133, 0.4);
            box-shadow: inset 0 1px 0 rgba(255, 245, 196, 0.3), 0 6px 18px -8px rgba(205, 173, 105, 0.55);
            flex-shrink: 0;
        }

        .obs-emblem-orbit {
            transform-origin: 20px 20px;
            animation: obsLogoOrbit 16s linear infinite;
        }

        .obs-emblem-core {
            transform-origin: 20px 20px;
            animation: obsLogoPulse 2.8s ease-in-out infinite;
        }

        .obs-iconic-wordmark {
            display: inline-flex;
            align-items: baseline;
            font-size: 1.05rem;
            letter-spacing: -0.025em;
            white-space: nowrap;
        }

        .obs-word-obs {
            font-weight: 300;
            color: #FAF7EE;
        }

        .obs-word-isstant {
            font-weight: 600;
            background: linear-gradient(110deg, #C7A45B 0%, #FFF2B4 32%, #D5B165 55%, #FFF8D1 78%, #C7A45B 100%);
            background-size: 220% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: obsLogoSheen 5.5s ease-in-out infinite;
        }

        .obs-word-suffix {
            margin-inline-start: 0.25rem;
            font-weight: 500;
            color: #F4E185;
        }

        @keyframes obsLogoOrbit {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes obsLogoPulse {
            0%, 100% { transform: scale(1); opacity: 0.92; }
            50% { transform: scale(1.18); opacity: 1; }
        }

        @keyframes obsLogoSheen {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .obs-emblem-orbit,
            .obs-emblem-core,
            .obs-word-isstant {
                animation: none;
            }
        }
    </style>
@endonce
