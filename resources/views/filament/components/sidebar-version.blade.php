<div class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
    <a href="{{ route('changelog') }}" target="_blank" class="inline-flex items-center gap-2 font-medium hover:text-amber-500">
        <span>Obsisstant</span>
        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 font-mono text-[11px] font-semibold text-amber-500">
            v{{ \App\Services\ChangelogService::getCurrentVersion() }}
        </span>
    </a>
</div>
