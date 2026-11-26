@php
    $isSuperAdmin = filament()->getCurrentPanel()?->getId() === 'superadmin';
@endphp
<div class="obs-simple-status-strip">
    <a href="{{ route('home') }}" class="obs-simple-brand-link" aria-label="Obsisstant home" style="text-decoration: none;">
        <x-brand-logo
            :suffix="$isSuperAdmin ? 'HQ' : null"
            :badge="$isSuperAdmin ? 'SUPER ADMIN' : null"
        />
    </a>
    <x-reverb-status-pill />
</div>
