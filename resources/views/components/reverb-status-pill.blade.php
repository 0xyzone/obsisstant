@php
    $reverbStatus = app(\App\Services\ReverbStatusService::class)->status();
    $isActive = $reverbStatus['active'];
@endphp

<span
    {{ $attributes->class([
        'obs-reverb-pill',
        'is-active' => $isActive,
        'is-offline' => ! $isActive,
    ]) }}
    data-reverb-status="{{ $isActive ? 'active' : 'offline' }}"
    title="{{ $reverbStatus['reason'] ?? ($isActive ? 'Laravel Reverb WebSocket server is listening' : 'Laravel Reverb WebSocket server is not reachable') }} ({{ $reverbStatus['host'] }}:{{ $reverbStatus['port'] }})"
    aria-label="Reverb server status: {{ $isActive ? 'active' : 'offline' }}"
>
    <span class="obs-reverb-dot" aria-hidden="true"></span>
    <span class="obs-reverb-label">{{ $reverbStatus['label'] }}</span>
</span>
