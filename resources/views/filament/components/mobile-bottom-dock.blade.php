@if (auth()->check())
    @php
        $currentUrl = url()->current();
        $isDashboard = request()->routeIs('filament.admin.pages.dashboard');
        $isDeck = request()->routeIs('filament.admin.pages.obs-control-deck');
        $isStudios = request()->routeIs('filament.admin.resources.obs-studios.*');
        $isScreens = request()->routeIs('filament.admin.resources.obs-screens.*');
        $isMacros = request()->routeIs('filament.admin.resources.obs-macros.*');
    @endphp
    <nav class="obs-mobile-dock" aria-label="Studio Mobile Navigation">
        <a href="{{ route('filament.admin.pages.dashboard') }}" class="obs-dock-link" @if ($isDashboard) aria-current="page" @endif>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="3" width="7" height="9" rx="2"/>
                <rect x="14" y="3" width="7" height="5" rx="2"/>
                <rect x="14" y="12" width="7" height="9" rx="2"/>
                <rect x="3" y="16" width="7" height="5" rx="2"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="{{ route('filament.admin.pages.obs-control-deck') }}" class="obs-dock-link" @if ($isDeck) aria-current="page" @endif>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Deck</span>
        </a>
        <a href="{{ route('filament.admin.resources.obs-studios.index') }}" class="obs-dock-link" @if ($isStudios) aria-current="page" @endif>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="2" y="4" width="20" height="14" rx="3"/>
                <path d="M8 21h8M12 18v3"/>
            </svg>
            <span>Studios</span>
        </a>
        <a href="{{ route('filament.admin.resources.obs-screens.index') }}" class="obs-dock-link" @if ($isScreens) aria-current="page" @endif>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="3"/>
                <path d="M7 15h6M7 11h10"/>
            </svg>
            <span>Screens</span>
        </a>
        <a href="{{ route('filament.admin.resources.obs-macros.index') }}" class="obs-dock-link" @if ($isMacros) aria-current="page" @endif>
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M13 2 4 14h7l-1 8 10-12h-7z"/>
            </svg>
            <span>Macros</span>
        </a>
    </nav>
@endif
