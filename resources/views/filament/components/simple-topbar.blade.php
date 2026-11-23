<div class="obs-simple-status-strip">
    <a href="{{ route('home') }}" class="obs-brand-lockup obs-simple-brand-link" aria-label="Obsisstant home">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CDAD69" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
            <circle cx="12" cy="12" r="2.5" fill="#CDAD69"/>
        </svg>
        <span class="obs-brand-wordmark">{{ config('app.name', 'Obsisstant') }}</span>
        <span class="obs-brand-version-pill">v{{ \App\Services\ChangelogService::getCurrentVersion() }}</span>
    </a>
    <x-reverb-status-pill />
</div>
