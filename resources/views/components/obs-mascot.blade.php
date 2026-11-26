@props([
    'mode' => 'badge',
    'greeting' => 'Hey! Tap or drag me!',
])

@php
    $isHero = $mode === 'hero-guide';
    $jsVersion = @filemtime(public_path('js/obs-mascot.js')) ?: '1';
@endphp

<div
    class="obs-mascot-host {{ $isHero ? 'obs-mascot-hero' : 'obs-mascot-badge' }}"
    data-obs-mascot
    data-mascot-mode="{{ $mode }}"
>
    @if ($isHero)
        <div class="obs-mascot-hero-stage">
            <div class="obs-mascot-hero-anchor" data-mascot-anchor>
                <div class="obs-mascot-fallback" data-mascot-fallback hidden aria-hidden="true">
                    <svg width="112" height="112" viewBox="0 0 88 88" fill="none">
                        <circle cx="44" cy="44" r="34" fill="#141311" stroke="#CDAD69" stroke-width="2.5"/>
                        <ellipse cx="44" cy="46" rx="22" ry="15" fill="#FAF5E8"/>
                        <circle cx="36" cy="45" r="3.6" fill="#050505"/>
                        <circle cx="52" cy="45" r="3.6" fill="#050505"/>
                    </svg>
                </div>
            </div>

            <div class="obs-mascot-actions" data-mascot-controls role="group" aria-label="Mascot animations">
                <button type="button" class="obs-mascot-chip" data-mascot-react="wave">Wave</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="spin">Spin</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="cheer">Cheer</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="dance">Dance</button>
            </div>
        </div>

        {{-- Single unified boxless 3D mascot that starts in hero and flies smoothly on scroll or drag --}}
        <div class="obs-mascot-flyer" data-mascot-flyer data-side="hero">
            <div class="obs-mascot-bubble" data-mascot-speech>{{ $greeting }}</div>
            <div class="obs-mascot-flyer-canvas">
                <canvas data-mascot-canvas aria-label="Interactive 3D broadcast mascot — tap to animate or drag to move"></canvas>
            </div>
        </div>
    @else
        <div class="obs-mascot-badge-free" title="Tap Lumi!">
            <canvas data-mascot-canvas aria-label="Interactive 3D mascot — click to animate"></canvas>
            <div class="obs-mascot-fallback" data-mascot-fallback hidden aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 88 88" fill="none">
                    <circle cx="44" cy="44" r="34" fill="#141311" stroke="#CDAD69" stroke-width="3"/>
                    <ellipse cx="44" cy="46" rx="22" ry="15" fill="#FAF5E8"/>
                    <circle cx="36" cy="45" r="4" fill="#050505"/>
                    <circle cx="52" cy="45" r="4" fill="#050505"/>
                </svg>
            </div>
            <span class="obs-mascot-badge-bubble" data-mascot-speech hidden></span>
        </div>
    @endif
</div>

@once
    <style>
        .obs-mascot-hero-stage {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 0.75rem;
            background: transparent;
            border: 0;
        }

        .obs-mascot-hero-anchor {
            position: relative;
            inline-size: min(76vw, 20rem);
            block-size: min(76vw, 20rem);
            background: transparent;
            border: 0;
            pointer-events: none;
        }

        .obs-mascot-flyer {
            position: fixed;
            inset-inline-start: 0;
            inset-block-start: 0;
            inline-size: 20rem;
            block-size: 20rem;
            transform-origin: top left;
            will-change: transform;
            z-index: 35;
            pointer-events: none;
        }

        .obs-mascot-flyer-canvas {
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            border: 0;
        }

        .obs-mascot-flyer-canvas canvas,
        .obs-mascot-badge-free canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            cursor: grab;
            touch-action: none;
            pointer-events: auto;
        }

        .obs-mascot-bubble {
            position: absolute;
            inset-block-start: -0.6rem;
            inset-inline-start: 50%;
            transform: translateX(-50%);
            inline-size: max-content;
            max-inline-size: 15rem;
            font-size: 0.78rem;
            line-height: 1.4;
            color: #FAF7EE;
            background: rgba(16, 15, 12, 0.78);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(205, 173, 105, 0.36);
            border-radius: 9999px;
            padding: 0.35rem 0.9rem;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 12px 28px -16px rgba(0, 0, 0, 0.85);
            pointer-events: auto;
            white-space: nowrap;
        }

        .obs-mascot-flyer[data-side="left"] .obs-mascot-bubble {
            inset-inline-start: calc(100% - 1rem);
            inset-block-start: 28%;
            transform: none;
            white-space: normal;
            border-radius: 1rem;
        }

        .obs-mascot-flyer[data-side="right"] .obs-mascot-bubble {
            inset-inline-start: auto;
            inset-inline-end: calc(100% - 1rem);
            inset-block-start: 28%;
            transform: none;
            white-space: normal;
            border-radius: 1rem;
        }

        .obs-mascot-fallback {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
        }

        .obs-mascot-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
            transition: opacity 220ms ease;
        }

        .obs-mascot-chip {
            min-block-size: 2rem;
            padding: 0.25rem 0.85rem;
            border-radius: 9999px;
            border: 1px solid rgba(205, 173, 105, 0.32);
            background: rgba(24, 22, 18, 0.68);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            color: #FAF7EE;
            font: inherit;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: border-color 140ms ease, background-color 140ms ease, transform 120ms ease;
        }

        .obs-mascot-chip:hover {
            border-color: #F4E185;
            background: rgba(205, 173, 105, 0.2);
            color: #F4E185;
        }

        .obs-mascot-chip:active {
            transform: scale(0.96);
        }

        .obs-mascot-badge-free {
            position: relative;
            inline-size: 6rem;
            block-size: 6rem;
            background: transparent;
            border: 0;
            flex-shrink: 0;
        }

        .obs-mascot-badge-free canvas {
            cursor: pointer;
            touch-action: manipulation;
        }

        .obs-mascot-badge-bubble {
            position: absolute;
            inset-inline-end: calc(100% + 0.5rem);
            inset-block-start: 50%;
            transform: translateY(-50%);
            inline-size: max-content;
            max-inline-size: 13rem;
            padding: 0.38rem 0.7rem;
            border-radius: 0.75rem;
            background: rgba(16, 15, 12, 0.88);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(205, 173, 105, 0.36);
            color: #FAF7EE;
            font-size: 0.72rem;
            line-height: 1.4;
            pointer-events: none;
            z-index: 20;
        }

        @media (max-width: 48rem) {
            .obs-mascot-badge-bubble {
                inset-inline-end: 0;
                inset-block-start: calc(100% + 0.45rem);
                transform: none;
            }
        }
    </style>
    <script type="module" src="{{ asset('js/obs-mascot.js') }}?v={{ $jsVersion }}"></script>
@endonce
