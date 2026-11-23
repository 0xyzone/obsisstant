<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Request;
use Pusher\Pusher;
use Throwable;
use WeakMap;

class ReverbStatusService
{
    /**
     * Optional resolver override for deterministic testing.
     */
    protected static ?Closure $probeResolver = null;

    /**
     * Request-scoped status cache so multiple views in one HTTP request probe at most once.
     *
     * @var WeakMap<Request, array{active: bool, label: string, host: string, port: int, reason: string}>|null
     */
    protected ?WeakMap $requestCache = null;

    public function __construct(
        public readonly float $timeoutSeconds = 0.25,
    ) {}

    public static function fakeUsing(?Closure $resolver): void
    {
        self::$probeResolver = $resolver;
    }

    public static function resetFake(): void
    {
        self::$probeResolver = null;
    }

    public static function isConfigured(): bool
    {
        return config('broadcasting.default') === 'reverb'
            && filled(config('reverb.apps.apps.0.app_id'))
            && filled(config('reverb.apps.apps.0.key'))
            && filled(config('reverb.apps.apps.0.secret'));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function clientEchoConfig(): ?array
    {
        if (config('broadcasting.default') !== 'reverb' || ! filled(config('reverb.apps.apps.0.key'))) {
            return null;
        }

        $echo = config('filament.broadcasting.echo');

        return is_array($echo) ? $echo : null;
    }

    /**
     * Determine whether the Laravel Reverb WebSocket server is configured, reachable, and authenticated.
     *
     * @return array{active: bool, label: string, host: string, port: int, reason: string}
     */
    public function status(?Request $request = null): array
    {
        $useCache = ! (self::$probeResolver instanceof Closure);
        $currentRequest = $useCache && app()->bound('request') ? ($request ?? app('request')) : null;

        if ($useCache && $currentRequest instanceof Request) {
            $this->requestCache ??= new WeakMap;

            if (isset($this->requestCache[$currentRequest])) {
                return $this->requestCache[$currentRequest];
            }
        }

        $target = $this->resolveTarget();

        if (self::$probeResolver instanceof Closure) {
            $active = (bool) (self::$probeResolver)($target['host'], $target['port']);
            $reason = $active
                ? 'Laravel Reverb WebSocket server is listening'
                : 'Laravel Reverb WebSocket server is not reachable';
        } else {
            [$active, $reason] = $this->evaluateLiveStatus($target);
        }

        $result = [
            'active' => $active,
            'label' => $active ? 'Reverb active' : 'Reverb offline',
            'host' => $target['host'],
            'port' => $target['port'],
            'reason' => $reason,
        ];

        if ($useCache && $currentRequest instanceof Request) {
            $this->requestCache[$currentRequest] = $result;
        }

        return $result;
    }

    /**
     * Resolve the configured Reverb server host and port for local socket inspection.
     *
     * @return array{host: string, port: int}
     */
    public function resolveTarget(): array
    {
        $serverHost = config('reverb.servers.reverb.host');
        $serverPort = config('reverb.servers.reverb.port');

        $host = (string) ($serverHost ?: config('broadcasting.connections.reverb.options.host') ?: '127.0.0.1');

        if ($host === '' || $host === '0.0.0.0' || $host === '::') {
            $host = '127.0.0.1';
        }

        $port = (int) ($serverPort ?: config('broadcasting.connections.reverb.options.port') ?: 8080);

        if ($port <= 0) {
            $port = 8080;
        }

        return [
            'host' => $host,
            'port' => $port,
        ];
    }

    /**
     * Evaluate broadcasting config, app keys, TCP availability, and genuine Reverb API authentication.
     *
     * @param  array{host: string, port: int}  $target
     * @return array{0: bool, 1: string}
     */
    protected function evaluateLiveStatus(array $target): array
    {
        $driver = (string) (config('broadcasting.default') ?? 'null');
        if ($driver !== 'reverb') {
            return [false, "BROADCAST_CONNECTION is \"{$driver}\" (must be \"reverb\")"];
        }

        if (! self::isConfigured()) {
            return [false, 'REVERB_APP_ID, REVERB_APP_KEY, or REVERB_APP_SECRET is empty'];
        }

        if (! $this->probeTcpSocket($target['host'], $target['port'])) {
            return [false, "Laravel Reverb WebSocket server is not reachable ({$target['host']}:{$target['port']})"];
        }

        if (! $this->probeReverbServer($target['host'], $target['port'])) {
            return [false, "Port {$target['host']}:{$target['port']} is open, but no authenticated Laravel Reverb server responded"];
        }

        return [true, "Laravel Reverb WebSocket server is listening and authenticated ({$target['host']}:{$target['port']})"];
    }

    public function probeReverbServer(string $host, int $port): bool
    {
        if (! self::isConfigured() || ! $this->probeTcpSocket($host, $port)) {
            return false;
        }

        try {
            $scheme = (string) (config('broadcasting.connections.reverb.options.scheme') ?: 'http');
            $internalHost = (string) (config('broadcasting.connections.reverb.options.host') ?: $host);
            $internalPort = (int) (config('broadcasting.connections.reverb.options.port') ?: $port);

            if ($internalHost === '' || $internalHost === '0.0.0.0' || $internalHost === '::') {
                $internalHost = '127.0.0.1';
            }

            $pusher = new Pusher(
                auth_key: (string) config('reverb.apps.apps.0.key'),
                secret: (string) config('reverb.apps.apps.0.secret'),
                app_id: (string) config('reverb.apps.apps.0.app_id'),
                options: [
                    'host' => $internalHost,
                    'port' => $internalPort > 0 ? $internalPort : $port,
                    'scheme' => $scheme,
                    'useTLS' => $scheme === 'https',
                    'timeout' => max(0.5, $this->timeoutSeconds),
                ],
            );

            $response = $pusher->getChannels();

            return is_object($response) && property_exists($response, 'channels');
        } catch (Throwable) {
            return false;
        }
    }

    public function probeTcpSocket(string $host, int $port): bool
    {
        $errorCode = 0;
        $errorMessage = '';

        set_error_handler(static fn (): bool => true);

        try {
            $socket = stream_socket_client(
                "tcp://{$host}:{$port}",
                $errorCode,
                $errorMessage,
                $this->timeoutSeconds,
                STREAM_CLIENT_CONNECT,
            );
        } finally {
            restore_error_handler();
        }

        if (! is_resource($socket)) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
