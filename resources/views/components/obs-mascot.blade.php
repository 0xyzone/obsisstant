@props([
    'mode' => 'badge',
    'greeting' => 'Hi! Tap me!',
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
        <div class="obs-mascot-hero-free">
            <div class="obs-mascot-bubble" data-mascot-speech>{{ $greeting }}</div>

            <div class="obs-mascot-canvas-free">
                <canvas data-mascot-canvas aria-label="Interactive 3D broadcast mascot — click or tap to play animations"></canvas>
                <div class="obs-mascot-fallback" data-mascot-fallback hidden aria-hidden="true">
                    <svg width="96" height="96" viewBox="0 0 88 88" fill="none">
                        <circle cx="44" cy="44" r="34" fill="#141311" stroke="#CDAD69" stroke-width="2.5"/>
                        <ellipse cx="44" cy="46" rx="22" ry="15" fill="#FAF5E8"/>
                        <circle cx="36" cy="45" r="3.6" fill="#050505"/>
                        <circle cx="52" cy="45" r="3.6" fill="#050505"/>
                    </svg>
                </div>
            </div>

            <div class="obs-mascot-actions" role="group" aria-label="Mascot animations">
                <button type="button" class="obs-mascot-chip" data-mascot-react="wave">Wave</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="spin">Spin</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="cheer">Cheer</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="dance">Dance</button>
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
        .obs-mascot-hero-free {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 0.6rem;
            background: transparent;
            border: 0;
        }

        .obs-mascot-bubble {
            font-size: 0.78rem;
            color: #FAF7EE;
            background: rgba(16, 15, 12, 0.72);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(205, 173, 105, 0.34);
            border-radius: 9999px;
            padding: 0.35rem 0.9rem;
            max-inline-size: 100%;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16);
        }

        .obs-mascot-canvas-free {
            position: relative;
            inline-size: min(100%, 24rem);
            aspect-ratio: 1 / 1;
            min-block-size: 16rem;
            background: transparent;
            border: 0;
            overflow: visible;
        }

        .obs-mascot-canvas-free canvas,
        .obs-mascot-badge-free canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            cursor: pointer;
            touch-action: manipulation;
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
            inline-size: 5.5rem;
            block-size: 5.5rem;
            background: transparent;
            border: 0;
            flex-shrink: 0;
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

        /* Scroll-guided boxless flying 3D mascot on Homepage */
        .mascot-scroll-guide {
            position: fixed;
            z-index: 35;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            pointer-events: none;
            opacity: 0;
            transform: scale(0.88);
            transition: opacity 320ms cubic-bezier(0.22, 1, 0.36, 1), transform 520ms cubic-bezier(0.22, 1, 0.36, 1), left 720ms cubic-bezier(0.22, 1, 0.36, 1), top 720ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .mascot-scroll-guide.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        .mascot-scroll-guide[data-side="left"] {
            flex-direction: row-reverse;
        }

        .mascot-float-speech {
            pointer-events: auto;
            max-inline-size: 13rem;
            padding: 0.5rem 0.8rem;
            border-radius: 1rem;
            background: rgba(16, 15, 12, 0.82);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            backdrop-filter: blur(16px) saturate(140%);
            border: 1px solid rgba(205, 173, 105, 0.38);
            color: #FAF7EE;
            font-size: 0.76rem;
            line-height: 1.4;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 14px 28px -16px rgba(0, 0, 0, 0.85);
        }

        .mascot-float-canvas-box {
            pointer-events: auto;
            inline-size: 10.5rem;
            block-size: 10.5rem;
            background: transparent;
            border: 0;
            flex-shrink: 0;
        }

        .mascot-float-canvas-box canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            cursor: pointer;
            touch-action: manipulation;
        }

        @media (max-width: 63.99rem) {
            .mascot-scroll-guide {
                gap: 0.3rem;
            }
            .mascot-float-speech {
                max-inline-size: 10rem;
                font-size: 0.68rem;
                padding: 0.4rem 0.65rem;
            }
            .mascot-float-canvas-box {
                inline-size: 6rem;
                block-size: 6rem;
            }
        }
    </style>
    <script type="module" src="{{ asset('js/obs-mascot.js') }}?v={{ $jsVersion }}"></script>
@endonce
