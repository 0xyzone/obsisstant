<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Request;
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
     * @var WeakMap<Request, array{active: bool, label: string, host: string, port: int}>|null
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

    /**
     * Determine whether the Laravel Reverb WebSocket server is accepting connections.
     *
     * @return array{active: bool, label: string, host: string, port: int}
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

        $active = self::$probeResolver instanceof Closure
            ? (bool) (self::$probeResolver)($target['host'], $target['port'])
            : $this->probeTcpSocket($target['host'], $target['port']);

        $result = [
            'active' => $active,
            'label' => $active ? 'Reverb active' : 'Reverb offline',
            'host' => $target['host'],
            'port' => $target['port'],
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
