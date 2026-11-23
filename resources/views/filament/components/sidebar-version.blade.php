<div class="obs-sidebar-footer">
    <a href="{{ route('changelog') }}" target="_blank" class="obs-sidebar-version-link">
        <span>Obsisstant</span>
        <span class="obs-version-meta">
            v{{ \App\Services\ChangelogService::getCurrentVersion() }}
        </span>
    </a>
</div>
