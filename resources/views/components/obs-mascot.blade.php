@props([
    'mode' => 'floating-assistant',
    'greeting' => 'Hey! Tap or drag me!',
    'studioSlug' => '',
])

@php
    $isHero = $mode === 'hero-guide';
    $isSignedIn = auth()->check();
    $jsVersion = @filemtime(public_path('js/obs-mascot.js')) ?: '1';
@endphp

<div
    class="obs-mascot-host {{ $isHero ? 'obs-mascot-hero' : 'obs-mascot-assistant' }}"
    data-obs-mascot
    data-mascot-mode="{{ $mode }}"
    data-oxy-url="{{ route('oxy.ask', [], false) }}"
    data-studio-slug="{{ $studioSlug }}"
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

            <div class="obs-mascot-actions" data-mascot-controls role="group" aria-label="Mascot animations and assistant">
                <button type="button" class="obs-mascot-chip" data-mascot-react="wave">Wave</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="spin">Spin</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="cheer">Cheer</button>
                <button type="button" class="obs-mascot-chip" data-mascot-react="dance">Dance</button>
                <button type="button" class="obs-mascot-chip obs-mascot-chip--gold" data-lumi-toggle aria-expanded="false">Ask Oxy</button>
            </div>
        </div>
    @endif

    {{-- Single unified boxless 3D mascot --}}
    <div class="obs-mascot-flyer" data-mascot-flyer data-side="{{ $isHero ? 'hero' : 'right' }}">
        <div class="obs-mascot-bubble" data-mascot-speech>{{ $isHero ? $greeting : '' }}</div>
        <div class="obs-mascot-flyer-canvas">
            <canvas data-mascot-canvas aria-label="Oxy 3D Assistant — tap to animate or ask, drag to move"></canvas>
        </div>
        @if (! $isHero)
            <button type="button" class="obs-mascot-ask-pill" data-lumi-toggle aria-expanded="false">Ask Oxy</button>
        @endif
    </div>

    {{-- Built-in Smart AI Assistant Drawer --}}
    <section class="lumi-drawer" data-lumi-drawer hidden aria-label="Oxy Assistant">
        <header class="lumi-drawer-head">
            <div>
                <strong class="lumi-drawer-title">Oxy</strong>
                <span class="lumi-drawer-sub">{{ $isSignedIn ? 'Your Studio Sidekick' : 'OBS & Overlay Buddy' }}</span>
            </div>
            <button type="button" class="lumi-close-btn" data-lumi-close aria-label="Close Oxy Assistant">&times;</button>
        </header>

        <div class="lumi-log" data-lumi-log>
            <div class="lumi-msg lumi-msg--bot">
                {{ $isSignedIn
                    ? 'Hey! I\'m Oxy. Ask me anything about OBS, brainstorm overlay text, or tell me to run a live action like "start a 5m timer" or "show lower third".'
                    : 'Hey there! I\'m Oxy. Ask me how Obsisstant connects to OBS, or ask me for lower-third and ticker text ideas.' }}
            </div>
        </div>

        <div class="lumi-quick-row">
            @if ($isSignedIn)
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="Check studio status">Studio status</button>
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="Start 5m timer">Start 5m timer</button>
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="Suggest lower-third copy">Overlay copy</button>
            @else
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="How do I pair OBS?">Pair OBS</button>
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="Suggest lower-third copy">Overlay copy</button>
                <button type="button" class="lumi-quick-btn" data-lumi-prompt="What happens at 00:00?">Timer rules</button>
            @endif
        </div>

        <form class="lumi-form" data-lumi-form>
            <input
                type="text"
                class="lumi-input"
                data-lumi-input
                maxlength="360"
                placeholder="{{ $isSignedIn ? 'Chat with Oxy or run an action...' : 'Chat with Oxy...' }}"
                aria-label="Message Oxy"
            >
            <button type="submit" class="lumi-send-btn">Send</button>
        </form>
    </section>
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
            z-index: 45;
            pointer-events: none;
        }

        .obs-mascot-flyer-canvas {
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            border: 0;
        }

        .obs-mascot-flyer-canvas canvas {
            display: block;
            inline-size: 100%;
            block-size: 100%;
            background: transparent;
            cursor: grab;
            touch-action: none;
            pointer-events: auto;
        }

        .obs-mascot-ask-pill {
            position: absolute;
            inset-inline-start: 50%;
            inset-block-end: 0.4rem;
            transform: translateX(-50%) scale(var(--mascot-inv-scale, 1));
            transform-origin: bottom center;
            pointer-events: auto;
            padding: 0.32rem 0.85rem;
            border-radius: 9999px;
            border: 1px solid rgba(244, 225, 133, 0.55);
            background: rgba(16, 15, 12, 0.88);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            color: #F4E185;
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            box-shadow: 0 8px 20px -10px rgba(0, 0, 0, 0.9);
        }

        /* Brief, highly readable speech bubble (fades out automatically) */
        .obs-mascot-bubble {
            position: absolute;
            inset-block-start: -1.1rem;
            inset-inline-start: 50%;
            transform: translateX(-50%) translateY(6px) scale(var(--mascot-inv-scale, 1));
            transform-origin: bottom center;
            inline-size: max-content;
            max-inline-size: min(68vw, 17.5rem);
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.45;
            color: #FAF7EE;
            background: rgba(14, 13, 11, 0.92);
            -webkit-backdrop-filter: blur(16px);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(244, 225, 133, 0.42);
            border-radius: 1rem;
            padding: 0.55rem 0.95rem;
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.18), 0 16px 34px -14px rgba(0, 0, 0, 0.92);
            pointer-events: none;
            opacity: 0;
            transition: opacity 260ms ease, transform 260ms ease;
        }

        .obs-mascot-bubble.is-visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(var(--mascot-inv-scale, 1));
        }

        .obs-mascot-flyer[data-side="left"] .obs-mascot-bubble {
            inset-inline-start: calc(100% - 0.5rem);
            inset-block-start: 28%;
            transform: translateY(6px) scale(var(--mascot-inv-scale, 1));
            transform-origin: center left;
            min-inline-size: 12.5rem;
            max-inline-size: min(62vw, 17rem);
            font-size: 0.92rem;
        }

        .obs-mascot-flyer[data-side="left"] .obs-mascot-bubble.is-visible {
            transform: translateY(0) scale(var(--mascot-inv-scale, 1));
        }

        .obs-mascot-flyer[data-side="right"] .obs-mascot-bubble {
            inset-inline-start: auto;
            inset-inline-end: calc(100% - 0.5rem);
            inset-block-start: 28%;
            transform: translateY(6px) scale(var(--mascot-inv-scale, 1));
            transform-origin: center right;
            min-inline-size: 12.5rem;
            max-inline-size: min(62vw, 17rem);
            font-size: 0.92rem;
        }

        .obs-mascot-flyer[data-side="right"] .obs-mascot-bubble.is-visible {
            transform: translateY(0) scale(var(--mascot-inv-scale, 1));
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
            min-block-size: 2.1rem;
            padding: 0.25rem 0.9rem;
            border-radius: 9999px;
            border: 1px solid rgba(205, 173, 105, 0.34);
            background: rgba(24, 22, 18, 0.72);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            color: #FAF7EE;
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: border-color 140ms ease, background-color 140ms ease, transform 120ms ease;
        }

        .obs-mascot-chip:hover {
            border-color: #F4E185;
            background: rgba(205, 173, 105, 0.2);
            color: #F4E185;
        }

        .obs-mascot-chip--gold {
            background: linear-gradient(180deg, #F6E496 0%, #D9B768 48%, #B89143 100%);
            color: #070706;
            font-weight: 600;
            border-color: rgba(244, 225, 133, 0.65);
        }

        .obs-mascot-chip--gold:hover {
            color: #070706;
            background: linear-gradient(180deg, #FFF0AE 0%, #E7C97B 48%, #C9A04E 100%);
        }

        /* Lumi AI Assistant Drawer */
        .lumi-drawer {
            position: fixed;
            inset-inline-end: max(1rem, env(safe-area-inset-right));
            inset-block-end: max(5.5rem, env(safe-area-inset-bottom));
            z-index: 60;
            inline-size: min(92vw, 22.5rem);
            max-block-size: 70dvh;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto auto;
            gap: 0.65rem;
            padding: 0.9rem;
            border-radius: 1.15rem;
            background: linear-gradient(165deg, rgba(22, 20, 16, 0.94) 0%, rgba(11, 10, 9, 0.96) 100%);
            -webkit-backdrop-filter: blur(22px) saturate(145%);
            backdrop-filter: blur(22px) saturate(145%);
            border: 1px solid rgba(205, 173, 105, 0.38);
            box-shadow: inset 0 1px 0 rgba(244, 225, 133, 0.2), 0 26px 56px -18px rgba(0, 0, 0, 0.95);
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            color: #FAF7EE;
        }

        .lumi-drawer[hidden] {
            display: none !important;
        }

        .lumi-drawer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding-block-end: 0.5rem;
            border-block-end: 1px solid rgba(205, 173, 105, 0.18);
        }

        .lumi-drawer-title {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #F4E185;
        }

        .lumi-drawer-sub {
            display: block;
            font-size: 0.72rem;
            color: #C8C0AF;
        }

        .lumi-close-btn {
            inline-size: 2rem;
            block-size: 2rem;
            border-radius: 9999px;
            border: 1px solid rgba(205, 173, 105, 0.28);
            background: rgba(255, 255, 255, 0.05);
            color: #FAF7EE;
            font-size: 1.15rem;
            line-height: 1;
            cursor: pointer;
        }

        .lumi-log {
            overflow-y: auto;
            display: grid;
            gap: 0.5rem;
            align-content: start;
            min-block-size: 8rem;
            max-block-size: 16rem;
            padding-inline-end: 0.2rem;
        }

        .lumi-msg {
            padding: 0.55rem 0.75rem;
            border-radius: 0.85rem;
            font-size: 0.8125rem;
            line-height: 1.48;
            white-space: pre-line;
        }

        .lumi-msg--bot {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(205, 173, 105, 0.2);
            color: #FAF7EE;
            justify-self: start;
        }

        .lumi-msg--user {
            background: rgba(205, 173, 105, 0.18);
            border: 1px solid rgba(244, 225, 133, 0.36);
            color: #FFF7D6;
            justify-self: end;
        }

        .lumi-msg-links {
            margin-block-start: 0.35rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .lumi-msg-links a {
            color: #F4E185;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: underline;
        }

        .lumi-quick-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .lumi-quick-btn {
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            border: 1px solid rgba(205, 173, 105, 0.28);
            background: rgba(255, 255, 255, 0.04);
            color: #E8DEC8;
            font: inherit;
            font-size: 0.72rem;
            cursor: pointer;
        }

        .lumi-quick-btn:hover {
            border-color: #F4E185;
            color: #F4E185;
        }

        .lumi-form {
            display: flex;
            gap: 0.4rem;
        }

        .lumi-input {
            flex: 1;
            min-inline-size: 0;
            min-block-size: 2.25rem;
            padding: 0.35rem 0.7rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(205, 173, 105, 0.32);
            background: #0B0B0A;
            color: #FAF7EE;
            font: inherit;
            font-size: 0.8125rem;
        }

        .lumi-send-btn {
            min-block-size: 2.25rem;
            padding: 0.25rem 0.85rem;
            border-radius: 0.6rem;
            border: 1px solid rgba(244, 225, 133, 0.6);
            background: linear-gradient(180deg, #F6E496 0%, #D9B768 48%, #B89143 100%);
            color: #070706;
            font: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
        }

        @media (max-width: 63.99rem) {
            .obs-mascot-flyer[data-side="left"] .obs-mascot-bubble,
            .obs-mascot-flyer[data-side="right"] .obs-mascot-bubble {
                font-size: 1.55rem;
                min-inline-size: 18rem;
                max-inline-size: 24rem;
                padding: 0.8rem 1.2rem;
            }
            .obs-mascot-ask-pill {
                font-size: 1.55rem;
                padding: 0.5rem 1.35rem;
            }
        }
    </style>
    <script type="module" src="{{ asset('js/obs-mascot.js') }}?v={{ $jsVersion }}"></script>
@endonce
