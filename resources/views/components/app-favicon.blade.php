@php
    $faviconFile = app()->isProduction() ? 'favicon-prod.svg' : 'favicon-local.svg';
    $faviconVersion = @filemtime(public_path($faviconFile)) ?: '1';
    $chromeCssVersion = @filemtime(public_path('css/obs-chrome.css')) ?: '1';
@endphp
<link rel="icon" type="image/svg+xml" href="{{ asset($faviconFile) }}?v={{ $faviconVersion }}">
<link rel="stylesheet" href="{{ asset('css/obs-chrome.css') }}?v={{ $chromeCssVersion }}">
