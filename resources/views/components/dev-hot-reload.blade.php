@php
    $hotPath = public_path('hot');
    $shouldLoadVite = false;

    if (app()->environment('local') && is_file($hotPath)) {
        $hotUrl = trim((string) file_get_contents($hotPath));
        $parsed = parse_url($hotUrl);
        $port = (int) ($parsed['port'] ?? 5173);

        set_error_handler(static fn (): bool => true);
        try {
            $socket = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 0.05, STREAM_CLIENT_CONNECT);
            if (is_resource($socket)) {
                fclose($socket);
                $shouldLoadVite = true;
            }
        } finally {
            restore_error_handler();
        }
    }
@endphp

@if ($shouldLoadVite)
    @vite(['resources/js/app.js'])
@endif
