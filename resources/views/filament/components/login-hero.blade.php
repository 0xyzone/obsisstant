@php
    $isSuperAdmin = filament()->getCurrentPanel()?->getId() === 'superadmin';
@endphp
<div class="obs-login-hero">
    <div>
        <span
            style="display: inline-flex; align-items: center; gap: 0.35rem; margin-bottom: 0.55rem; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; {{ $isSuperAdmin ? 'background: linear-gradient(180deg, rgba(244,225,133,0.22), rgba(205,173,105,0.12)); border: 1px solid rgba(244,225,133,0.55); color: #F4E185;' : 'background: rgba(255,255,255,0.05); border: 1px solid rgba(205,173,105,0.28); color: #C8C0AF;' }}"
        >
            {{ $isSuperAdmin ? 'Super Admin HQ' : 'Studio Operator' }}
        </span>
        <h2 class="obs-login-title">{{ $isSuperAdmin ? 'Sign in to Obsisstant HQ' : 'Sign in to Studio' }}</h2>
        <p class="obs-login-sub">
            {{ $isSuperAdmin
                ? 'Platform administration for customers, packages, and subscriptions.'
                : 'Enter your studio operator credentials to access live OBS controls.' }}
        </p>
        @if ($isSuperAdmin)
            <p style="margin-top: 0.45rem; font-size: 0.75rem; color: #C8C0AF;">
                Looking for your OBS workspace?
                <a href="/admin/login" style="color: #F4E185; text-decoration: underline;">Go to Studio Login</a>
            </p>
        @endif
    </div>
</div>
