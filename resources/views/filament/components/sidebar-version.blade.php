<div class="px-4 py-3 text-xs text-stone-400">
    <a href="{{ route('changelog') }}" target="_blank" class="inline-flex items-center gap-2 font-medium hover:text-[#F4E185]">
        <span>Obsisstant</span>
        <span class="rounded-md border border-[#CDAD69]/25 bg-[#1B1A17] px-2 py-0.5 text-xs font-medium text-[#CDAD69]" style="font-variant-numeric: tabular-nums;">
            v{{ \App\Services\ChangelogService::getCurrentVersion() }}
        </span>
    </a>
</div>
