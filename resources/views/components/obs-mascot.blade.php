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
        <div class="obs-mascot-hero-card">
            <div class="obs-mascot-stage-top">
                <span class="obs-mascot-pill">Lumi · 3D Buddy</span>
                <div class="obs-mascot-bubble" data-mascot-speech>{{ $greeting }}</div>
            </div>

            <div class="obs-mascot-canvas-wrap">
                <canvas data-mascot-canvas aria-label="Interactive 3D broadcast mascot — click or tap to play animations"></canvas>
                <div class="obs-mascot-fallback" data-mascot-fallback hidden aria-hidden="true">
                    <svg width="88" height="88" viewBox="0 0 88 88" fill="none">
                        <circle cx="44" cy="44" r="34" fill="#181714" stroke="#CDAD69" stroke-width="2.5"/>
                        <circle cx="34" cy="42" r="4.5" fill="#F4E185"/>
                        <circle cx="54" cy="42" r="4.5" fill="#F4E185"/>
                        <path d="M35 55c3.5 3.5 14.5 3.5 18 0" stroke="#F4E185" stroke-width="2.5" stroke-linecap="round"/>
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
        <div class="obs-mascot-badge-wrap" title="Tap Lumi!">
            <canvas data-mascot-canvas aria-label="Interactive 3D mascot — click to animate"></canvas>
            <div class="obs-mascot-fallback" data-mascot-fallback hidden aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 88 88" fill="none">
                    <circle cx="44" cy="44" r="34" fill="#181714" stroke="#CDAD69" stroke-width="3"/>
                    <circle cx="34" cy="42" r="5" fill="#F4E185"/>
                    <circle cx="54" cy="42" r="5" fill="#F4E185"/>
                </svg>
            </div>
            <span class="obs-mascot-badge-bubble" data-mascot-speech hidden></span>
        </div>
    @endif
</div>

@once
    <style>
        .obs-mascot-hero-card {
            position: relative;
            background: linear-gradient(165deg, rgba(28, 25, 19, 0.72) 0%, rgba(12, 11, 9, 0.84) 100%);
            -webkit-backdrop-filter: blur(20px) saturate(145%);
            backdrop-filter: blur(20px) saturate(145%);
            border: 1px solid rgba(205, 173, 105, 0.34);
            border-radius: 1.5rem;
            padding: 1rem;
            display: grid;
            gap: 0.75rem;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.2), 0 28px 60px -24px rgba(0, 0, 0, 0.9);
        }

        .obs-mascot-stage-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .obs-mascot-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 9999px;
            background: rgba(205, 173, 105, 0.14);
            border: 1px solid rgba(244, 225, 133, 0.36);
            font-size: 0.72rem;
            font-weight: 500;
            color: #F4E185;
        }

        .obs-mascot-bubble {
            font-size: 0.78rem;
            color: #FAF7EE;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(205, 173, 105, 0.24);
            border-radius: 0.75rem;
            padding: 0.35rem 0.75rem;
            max-inline-size: 100%;
        }

        .obs-mascot-canvas-wrap {
            position: relative;
            inline-size: 100%;
            aspect-ratio: 4 / 3;
            min-block-size: 15.5rem;
            border-radius: 1.1rem;
            background:
                radial-gradient(circle at 50% 45%, rgba(244, 225, 133, 0.16), transparent 62%),
                rgba(8, 8, 7, 0.62);
            border: 1px solid rgba(205, 173, 105, 0.18);
            overflow: hidden;
        }

        .obs-mascot-canvas-wrap canvas,
        .obs-mascot-badge-wrap canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
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
            background: rgba(28, 26, 21, 0.78);
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

        .obs-mascot-badge-wrap {
            position: relative;
            inline-size: 5.25rem;
            block-size: 5.25rem;
            border-radius: 1.15rem;
            background: radial-gradient(circle at 50% 40%, rgba(244, 225, 133, 0.18), rgba(14, 13, 11, 0.84));
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(205, 173, 105, 0.34);
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.18), 0 14px 30px -16px rgba(0, 0, 0, 0.85);
            flex-shrink: 0;
        }

        .obs-mascot-badge-bubble {
            position: absolute;
            inset-inline-end: calc(100% + 0.65rem);
            inset-block-start: 50%;
            transform: translateY(-50%);
            inline-size: max-content;
            max-inline-size: 14rem;
            padding: 0.4rem 0.7rem;
            border-radius: 0.75rem;
            background: rgba(16, 15, 12, 0.92);
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

        /* Scroll-guided floating 3D mascot on Homepage */
        .mascot-scroll-guide {
            position: fixed;
            inset-block-end: max(1rem, env(safe-area-inset-bottom));
            inset-inline-end: max(1rem, env(safe-area-inset-right));
            z-index: 35;
            display: flex;
            align-items: flex-end;
            gap: 0.6rem;
            pointer-events: none;
            opacity: 0;
            transform: translateY(18px) scale(0.9);
            transition: opacity 320ms cubic-bezier(0.22, 1, 0.36, 1), transform 420ms cubic-bezier(0.22, 1, 0.36, 1), inset-inline 420ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .mascot-scroll-guide.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .mascot-scroll-guide[data-side="left"] {
            inset-inline-end: auto;
            inset-inline-start: max(1.25rem, env(safe-area-inset-left));
            flex-direction: row-reverse;
        }

        .mascot-float-speech {
            pointer-events: auto;
            max-inline-size: 13.5rem;
            padding: 0.55rem 0.85rem;
            border-radius: 1rem;
            background: rgba(16, 15, 12, 0.84);
            -webkit-backdrop-filter: blur(18px) saturate(145%);
            backdrop-filter: blur(18px) saturate(145%);
            border: 1px solid rgba(205, 173, 105, 0.38);
            color: #FAF7EE;
            font-size: 0.78rem;
            line-height: 1.45;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.16), 0 18px 36px -16px rgba(0, 0, 0, 0.9);
        }

        .mascot-float-canvas-box {
            pointer-events: auto;
            inline-size: 7.5rem;
            block-size: 7.5rem;
            border-radius: 50%;
            background: radial-gradient(circle at 50% 45%, rgba(244, 225, 133, 0.18), rgba(12, 11, 9, 0.72) 72%);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(205, 173, 105, 0.34);
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.2), 0 18px 38px -14px rgba(0, 0, 0, 0.9);
            overflow: hidden;
            flex-shrink: 0;
        }

        .mascot-float-canvas-box canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
            cursor: pointer;
            touch-action: manipulation;
        }

        @media (max-width: 48rem) {
            .mascot-scroll-guide {
                inset-block-end: max(0.65rem, env(safe-area-inset-bottom));
                inset-inline-end: max(0.65rem, env(safe-area-inset-right));
                gap: 0.4rem;
            }
            .mascot-float-speech {
                max-inline-size: 10.5rem;
                font-size: 0.7rem;
                padding: 0.45rem 0.65rem;
            }
            .mascot-float-canvas-box {
                inline-size: 5rem;
                block-size: 5rem;
            }
        }
    </style>
    <script type="module" src="{{ asset('js/obs-mascot.js') }}?v={{ $jsVersion }}"></script>
@endonce
