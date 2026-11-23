<?php

namespace App\Services\Obs;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ObsLocalRelayService
{
    public function isLocalControlAvailable(): bool
    {
        return (bool) config('obs.local_agent_enabled', false)
            && is_executable($this->scriptPath())
            && is_dir(base_path('obs-agent/node_modules'));
    }

    public function ensureAgentEnvConfigured(): void
    {
        $relayToken = trim((string) config('obs.relay_token', ''));
        if ($relayToken === '') {
            return;
        }

        $envPath = base_path('obs-agent/.env');
        $appUrl = rtrim((string) config('app.url', 'https://ob'), '/');

        if (! File::exists($envPath)) {
            $lines = [
                "OBSISSTANT_URL={$appUrl}",
                "OBSISSTANT_RELAY_TOKEN={$relayToken}",
                'OBS_WS_URL=',
                'OBS_WS_PASSWORD=',
            ];

            File::put($envPath, implode(PHP_EOL, $lines).PHP_EOL);

            return;
        }

        $content = File::get($envPath);
        if (! str_contains($content, 'OBSISSTANT_RELAY_TOKEN=') || preg_match('/^OBSISSTANT_RELAY_TOKEN=\s*$/m', $content)) {
            if (preg_match('/^OBSISSTANT_RELAY_TOKEN=.*$/m', $content)) {
                $content = (string) preg_replace('/^OBSISSTANT_RELAY_TOKEN=.*$/m', "OBSISSTANT_RELAY_TOKEN={$relayToken}", $content);
            } else {
                $content = rtrim($content).PHP_EOL."OBSISSTANT_RELAY_TOKEN={$relayToken}".PHP_EOL;
            }
            File::put($envPath, $content);
        }
    }

    /**
     * @return array{running: bool, pid: ?int, output: string}
     */
    public function status(): array
    {
        if (! is_executable($this->scriptPath())) {
            return ['running' => false, 'pid' => null, 'output' => 'script_missing'];
        }

        $result = Process::path(base_path())->run([$this->scriptPath(), 'status']);
        $output = trim($result->output());
        $pidPath = storage_path('framework/obs-agent.pid');
        $pid = File::exists($pidPath) ? (int) trim((string) File::get($pidPath)) : null;

        return [
            'running' => str_starts_with($output, 'running'),
            'pid' => $pid ?: null,
            'output' => $output,
        ];
    }

    public function start(): string
    {
        $this->ensureAgentEnvConfigured();

        $result = Process::path(base_path())->run([$this->scriptPath(), 'start']);

        return trim($result->output() ?: $result->errorOutput());
    }

    public function restart(): string
    {
        $this->ensureAgentEnvConfigured();

        $result = Process::path(base_path())->run([$this->scriptPath(), 'restart']);

        return trim($result->output() ?: $result->errorOutput());
    }

    public function stop(): string
    {
        $result = Process::path(base_path())->run([$this->scriptPath(), 'stop']);

        return trim($result->output() ?: $result->errorOutput());
    }

    public function recentLogs(int $lines = 30): string
    {
        $logPath = storage_path('logs/obs-agent.log');
        if (! File::exists($logPath)) {
            return 'No obs-agent logs yet.';
        }

        $all = explode(PHP_EOL, trim((string) File::get($logPath)));

        return implode(PHP_EOL, array_slice($all, -$lines));
    }

    public function externalCommandExample(): string
    {
        $appUrl = rtrim((string) config('app.url', 'https://ob'), '/');
        $relayToken = trim((string) config('obs.relay_token', '')) ?: 'your_workspace_relay_token';

        return "OBSISSTANT_URL={$appUrl} OBSISSTANT_RELAY_TOKEN={$relayToken} npm start";
    }

    private function scriptPath(): string
    {
        return base_path('scripts/run_obs_agent.sh');
    }
}
