<?php

namespace App\Services\Obs;

use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ObsAgentInstallerService
{
    public const string PLATFORM_MAC = 'mac';

    public const string PLATFORM_WINDOWS = 'windows';

    private const int SETUP_TOKEN_TTL_MINUTES = 20;

    /**
     * Issue a short-lived bootstrap token (and ready-to-paste Mac/Windows commands)
     * scoped to the authenticated user and optional Studio profile.
     *
     * @return array{
     *     token: string,
     *     pairing_code: string,
     *     expires_in_minutes: int,
     *     mac_command: string,
     *     windows_command: string,
     *     mac_download_url: string,
     *     windows_download_url: string
     * }
     */
    public function issueBootstrapPayload(User $user, ?ObsStudio $studio = null): array
    {
        $token = Str::random(48);
        $this->cacheStore()->put($this->cacheKey($token), [
            'user_id' => $user->id,
            'studio_id' => $studio?->id,
        ], now()->addMinutes(self::SETUP_TOKEN_TTL_MINUTES));

        $pairDigits = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->cacheStore()->put("obs_agent_pair_code:{$pairDigits}", [
            'user_id' => $user->id,
            'studio_id' => $studio?->id,
        ], now()->addMinutes(10));

        // Pre-warm personal relay token so the account token exists before bundle download
        if ($studio === null) {
            $user->getOrCreateRelayToken();
        }

        $baseUrl = $this->resolveAppUrl();
        $macScriptUrl = "{$baseUrl}/setup-agent/{$token}/mac.sh";
        $winScriptUrl = "{$baseUrl}/setup-agent/{$token}/win.ps1";

        $studioQuery = $studio !== null ? '?studio='.urlencode($studio->slug) : '';

        return [
            'token' => $token,
            'pairing_code' => substr($pairDigits, 0, 3).'-'.substr($pairDigits, 3, 3),
            'expires_in_minutes' => self::SETUP_TOKEN_TTL_MINUTES,
            'mac_command' => "curl -fsSL \"{$macScriptUrl}\" | bash",
            'windows_command' => "powershell -NoProfile -ExecutionPolicy Bypass -Command \"irm '{$winScriptUrl}' | iex\"",
            'mac_download_url' => route('agent-installer.download', ['platform' => self::PLATFORM_MAC]).$studioQuery,
            'windows_download_url' => route('agent-installer.download', ['platform' => self::PLATFORM_WINDOWS]).$studioQuery,
        ];
    }

    /**
     * Exchange a one-time 6-digit pairing code for the user's relay credentials.
     *
     * @return array<string, mixed>|null
     */
    public function resolvePairingCode(string $code): ?array
    {
        $digits = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($digits) !== 6) {
            return null;
        }

        $payload = $this->cacheStore()->pull("obs_agent_pair_code:{$digits}");
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        $user = User::query()->find((int) $payload['user_id']);
        if (! $user instanceof User) {
            return null;
        }

        $studio = ! empty($payload['studio_id'])
            ? ObsStudio::query()->ownedBy($user)->where('id', (int) $payload['studio_id'])->first()
            : null;

        return [
            'relay_token' => $user->getOrCreateRelayToken(),
            'api_base' => $this->resolveAppUrl(),
            'studio_slug' => $studio?->slug,
            'studio_name' => $studio?->name,
            'obs_host' => $studio?->obs_host ?? '127.0.0.1',
            'obs_port' => (int) ($studio?->obs_port ?? 4455),
            'obs_password' => $studio?->obs_password ?? '',
        ];
    }

    /**
     * Resolve user & optional studio from an active bootstrap setup token.
     *
     * @return array{user: User, studio: ?ObsStudio}|null
     */
    public function resolveBootstrapContext(string $token): ?array
    {
        if (! preg_match('/^[A-Za-z0-9]{32,80}$/', $token)) {
            return null;
        }

        $payload = $this->cacheStore()->get($this->cacheKey($token));
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        $user = User::query()->find((int) $payload['user_id']);
        if (! $user instanceof User) {
            return null;
        }

        $studio = null;
        if (! empty($payload['studio_id'])) {
            $studio = ObsStudio::query()
                ->ownedBy($user)
                ->where('id', (int) $payload['studio_id'])
                ->first();
        }

        return [
            'user' => $user,
            'studio' => $studio,
        ];
    }

    /**
     * Generate a pre-configured, self-installing ZIP bundle for macOS or Windows.
     */
    public function buildBundleZip(User $user, string $platform, ?ObsStudio $studio = null): string
    {
        $normalizedPlatform = $this->normalizePlatform($platform);
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to build agent installer packages.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'obs_agent_pkg_');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to allocate temporary zip archive path.');
        }

        $zipPath = $tempPath.'.zip';
        @unlink($tempPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create Obsisstant OBS Agent installer ZIP archive.');
        }

        $root = 'Obsisstant-OBS-Agent';
        $agentPrefix = "{$root}/agent";

        $sourceFiles = [
            "{$agentPrefix}/package.json" => base_path('obs-agent/package.json'),
            "{$agentPrefix}/package-lock.json" => base_path('obs-agent/package-lock.json'),
            "{$agentPrefix}/src/index.js" => base_path('obs-agent/src/index.js'),
        ];

        foreach ($sourceFiles as $zipEntry => $diskPath) {
            if (! is_file($diskPath)) {
                $zip->close();
                @unlink($zipPath);
                throw new RuntimeException("Required obs-agent file missing: {$diskPath}");
            }
            $zip->addFile($diskPath, $zipEntry);
        }

        $zip->addFromString("{$agentPrefix}/.env", $this->buildAgentEnvContent($user, $studio));
        $zip->addFromString("{$root}/README.txt", $this->buildReadmeText($normalizedPlatform, $studio));

        if ($normalizedPlatform === self::PLATFORM_MAC) {
            $installName = "{$root}/Install & Run Obsisstant Agent.command";
            $stopName = "{$root}/Stop Obsisstant Agent.command";
            $statusName = "{$root}/Open Agent Status & Pairing.command";
            $uninstallName = "{$root}/Uninstall Obsisstant Agent.command";

            $zip->addFromString($installName, $this->buildMacInstallCommand());
            $zip->setExternalAttributesName($installName, ZipArchive::OPSYS_UNIX, 0100755 << 16);

            $zip->addFromString($stopName, $this->buildMacStopCommand());
            $zip->setExternalAttributesName($stopName, ZipArchive::OPSYS_UNIX, 0100755 << 16);

            $zip->addFromString($statusName, $this->buildMacStatusCommand());
            $zip->setExternalAttributesName($statusName, ZipArchive::OPSYS_UNIX, 0100755 << 16);

            $zip->addFromString($uninstallName, $this->buildMacUninstallCommand());
            $zip->setExternalAttributesName($uninstallName, ZipArchive::OPSYS_UNIX, 0100755 << 16);
        } else {
            $zip->addFromString("{$root}/Install & Run Obsisstant Agent.bat", $this->toCrlf($this->buildWindowsInstallBat()));
            $zip->addFromString("{$root}/install-agent.ps1", $this->toCrlf($this->buildWindowsInstallPs1()));
            $zip->addFromString("{$root}/Stop Obsisstant Agent.bat", $this->toCrlf($this->buildWindowsStopBat()));
            $zip->addFromString("{$root}/Open Agent Status & Pairing.bat", $this->toCrlf($this->buildWindowsStatusBat()));
            $zip->addFromString("{$root}/obsisstant-tray.ps1", $this->toCrlf($this->buildWindowsTrayPs1()));
            $zip->addFromString("{$root}/Uninstall Obsisstant Agent.bat", $this->toCrlf($this->buildWindowsUninstallBat()));
        }

        $zip->close();

        return $zipPath;
    }

    public function buildMacOneClickScript(string $token): string
    {
        $bundleUrl = "{$this->resolveAppUrl()}/setup-agent/{$token}/bundle/mac.zip";

        $script = <<<'SH'
#!/bin/bash
set -euo pipefail

TMP_DIR="$(mktemp -d)"
cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

echo ""
echo "======================================================"
echo "  Obsisstant OBS Agent - Mac One-Click Installer"
echo "======================================================"
echo ""
echo "Downloading your pre-configured OBS Agent package..."
curl -fsSL "__BUNDLE_URL__" -o "$TMP_DIR/agent.zip"

echo "Extracting installer..."
unzip -q "$TMP_DIR/agent.zip" -d "$TMP_DIR/extracted"

bash "$TMP_DIR/extracted/Obsisstant-OBS-Agent/Install & Run Obsisstant Agent.command" --no-pause
SH;

        return str_replace('__BUNDLE_URL__', $bundleUrl, $script)."\n";
    }

    public function buildWindowsOneClickScript(string $token): string
    {
        $bundleUrl = "{$this->resolveAppUrl()}/setup-agent/{$token}/bundle/windows.zip";

        $script = <<<'PS1'
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$TempDir = Join-Path $env:TEMP ("obsisstant-setup-" + [Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

try {
    Write-Host ""
    Write-Host "======================================================" -ForegroundColor Yellow
    Write-Host "  Obsisstant OBS Agent - Windows One-Click Installer" -ForegroundColor Yellow
    Write-Host "======================================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Downloading your pre-configured OBS Agent package..."

    $ZipFile = Join-Path $TempDir "agent.zip"
    $ExtractDir = Join-Path $TempDir "extracted"
    Invoke-WebRequest -UseBasicParsing -Uri "__BUNDLE_URL__" -OutFile $ZipFile

    Write-Host "Extracting installer..."
    Expand-Archive -Path $ZipFile -DestinationPath $ExtractDir -Force

    $InstallerPs1 = Join-Path $ExtractDir "Obsisstant-OBS-Agent\install-agent.ps1"
    & powershell -NoProfile -ExecutionPolicy Bypass -File $InstallerPs1
}
finally {
    Remove-Item -Path $TempDir -Recurse -Force -ErrorAction SilentlyContinue
}
PS1;

        return $this->toCrlf(str_replace('__BUNDLE_URL__', $bundleUrl, $script));
    }

    public function buildAgentEnvContent(User $user, ?ObsStudio $studio = null): string
    {
        $appUrl = $this->resolveAppUrl();
        $tokenLine = $studio !== null
            ? 'OBSISSTANT_AGENT_TOKEN='.$studio->agent_token
            : 'OBSISSTANT_RELAY_TOKEN='.$user->getOrCreateRelayToken();

        return implode("\n", [
            "OBSISSTANT_URL={$appUrl}",
            $tokenLine,
            'OBS_WS_URL=',
            'OBS_WS_PASSWORD=',
            '',
        ]);
    }

    public function normalizePlatform(string $platform): string
    {
        return match (strtolower($platform)) {
            'mac', 'macos', 'darwin', 'osx' => self::PLATFORM_MAC,
            'win', 'windows' => self::PLATFORM_WINDOWS,
            default => throw new RuntimeException("Unsupported installer platform: {$platform}"),
        };
    }

    private function buildReadmeText(string $platform, ?ObsStudio $studio): string
    {
        $scope = $studio !== null
            ? "Dedicated Studio: {$studio->name}"
            : 'Account Relay Mode (syncs all Studios on your Obsisstant account)';

        $steps = $platform === self::PLATFORM_MAC
            ? "Recommended (Zero Apple Warnings):\nCopy the 1-Click Mac setup command inside Obsisstant and paste it into Terminal.\n\nIf opening this .zip from Finder on macOS 15+:\n1. Try opening 'Install & Run Obsisstant Agent.command' and click Done if macOS blocks it.\n2. Open System Settings -> Privacy & Security, scroll down, and click 'Open Anyway'.\n3. In OBS Studio, open Tools -> WebSocket Server Settings and tick 'Enable WebSocket server' (port 4455)."
            : "1. Double-click 'Install & Run Obsisstant Agent.bat' (click 'More info -> Run anyway' if Windows SmartScreen asks).\n2. Wait a few seconds while it installs Node.js (if needed) and starts OBS Agent silently in the background.\n3. In OBS Studio, open Tools -> WebSocket Server Settings and tick 'Enable WebSocket server' (port 4455).";

        return $this->toCrlf("Obsisstant OBS Agent\n====================\nServer: {$this->resolveAppUrl()}\nMode: {$scope}\n\nQuick Steps:\n{$steps}\n");
    }

    private function buildMacInstallCommand(): string
    {
        return <<<'BASH'
#!/bin/bash
set -euo pipefail

NO_PAUSE=0
if [ "${1:-}" = "--no-pause" ] || [ ! -t 0 ]; then
    NO_PAUSE=1
fi

finish_prompt() {
    if [ "$NO_PAUSE" -eq 0 ] && [ -t 0 ]; then
        echo ""
        read -r -p "Press Return to close this window..." _ || true
    fi
}
trap finish_prompt EXIT

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_AGENT_DIR="$SCRIPT_DIR/agent"
INSTALL_DIR="$HOME/Library/Application Support/ObsisstantAgent"
LOG_DIR="$HOME/Library/Logs"
LOG_FILE="$LOG_DIR/obsisstant-agent.log"
LAUNCH_AGENTS_DIR="$HOME/Library/LaunchAgents"
PLIST_FILE="$LAUNCH_AGENTS_DIR/com.obsisstant.agent.plist"

echo ""
echo "Installing Obsisstant OBS Agent on this Mac..."
echo "Target folder: $INSTALL_DIR"
echo ""

if [ ! -f "$SRC_AGENT_DIR/src/index.js" ] || [ ! -f "$SRC_AGENT_DIR/.env" ]; then
    echo "ERROR: Missing agent bundle files beside this installer."
    exit 1
fi

find_node() {
    local CANDIDATES=(
        "/opt/homebrew/bin/node"
        "/usr/local/bin/node"
        "/usr/bin/node"
    )

    if [ -d "$HOME/.nvm/versions/node" ]; then
        while IFS= read -r ver_dir; do
            CANDIDATES+=("$ver_dir/bin/node")
        done < <(ls -1dt "$HOME/.nvm/versions/node"/v* 2>/dev/null || true)
    fi

    if command -v node >/dev/null 2>&1; then
        CANDIDATES+=("$(command -v node)")
    fi

    for candidate in "${CANDIDATES[@]}"; do
        if [ -x "$candidate" ]; then
            if "$candidate" -e "process.exit(Number(process.versions.node.split('.')[0]) >= 18 ? 0 : 1)" 2>/dev/null; then
                echo "$candidate"
                return 0
            fi
        fi
    done

    return 1
}

NODE_BIN="$(find_node || true)"

if [ -z "$NODE_BIN" ]; then
    echo "Node.js 18+ was not found on this Mac. Installing Node.js LTS automatically..."
    if command -v brew >/dev/null 2>&1; then
        brew install node
        NODE_BIN="$(find_node || true)"
    fi

    if [ -z "$NODE_BIN" ]; then
        PKG_PATH="/tmp/obsisstant-nodejs-lts.pkg"
        echo "Downloading official Node.js LTS installer..."
        curl -fsSL "https://nodejs.org/dist/v22.15.0/node-v22.15.0.pkg" -o "$PKG_PATH"
        echo "Requesting macOS installer permission..."
        if ! osascript -e "do shell script \"installer -pkg '$PKG_PATH' -target /\" with administrator privileges" 2>/dev/null; then
            echo "Opening Apple Node.js installer window (please finish clicking Continue/Install)..."
            open -W "$PKG_PATH"
        fi
        rm -f "$PKG_PATH"
        NODE_BIN="$(find_node || true)"
    fi
fi

if [ -z "$NODE_BIN" ]; then
    echo "ERROR: Node.js could not be detected after installation. Please install Node.js LTS from https://nodejs.org and re-run this installer."
    exit 1
fi

NPM_BIN="$(dirname "$NODE_BIN")/npm"
if [ ! -x "$NPM_BIN" ]; then
    NPM_BIN="$(command -v npm || true)"
fi

if [ -z "$NPM_BIN" ]; then
    echo "ERROR: npm binary not found beside $NODE_BIN."
    exit 1
fi

echo "Using Node: $NODE_BIN ($("$NODE_BIN" -v))"

mkdir -p "$INSTALL_DIR/src" "$LOG_DIR" "$LAUNCH_AGENTS_DIR"
cp "$SRC_AGENT_DIR/package.json" "$INSTALL_DIR/package.json"
cp "$SRC_AGENT_DIR/package-lock.json" "$INSTALL_DIR/package-lock.json"
cp "$SRC_AGENT_DIR/src/index.js" "$INSTALL_DIR/src/index.js"
cp "$SRC_AGENT_DIR/.env" "$INSTALL_DIR/.env"

echo "Installing OBS Agent runtime dependencies..."
cd "$INSTALL_DIR"
PATH="$(dirname "$NODE_BIN"):$PATH" "$NPM_BIN" install --omit=dev --no-audit --no-fund

NODE_DIR="$(dirname "$NODE_BIN")"
UID_NUM="$(id -u)"

cat > "$PLIST_FILE" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.obsisstant.agent</string>
    <key>ProgramArguments</key>
    <array>
        <string>$NODE_BIN</string>
        <string>$INSTALL_DIR/src/index.js</string>
    </array>
    <key>WorkingDirectory</key>
    <string>$INSTALL_DIR</string>
    <key>EnvironmentVariables</key>
    <dict>
        <key>PATH</key>
        <string>$NODE_DIR:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin</string>
    </dict>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardOutPath</key>
    <string>$LOG_FILE</string>
    <key>StandardErrorPath</key>
    <string>$LOG_FILE</string>
</dict>
</plist>
PLIST

echo "Starting Obsisstant OBS Agent in background (auto-starts on login)..."
launchctl bootout "gui/${UID_NUM}/com.obsisstant.agent" >/dev/null 2>&1 || launchctl unload "$PLIST_FILE" >/dev/null 2>&1 || true
pkill -f "$INSTALL_DIR/src/index.js" >/dev/null 2>&1 || true
sleep 0.5

if ! launchctl bootstrap "gui/${UID_NUM}" "$PLIST_FILE" >/dev/null 2>&1; then
    launchctl load -w "$PLIST_FILE"
fi
launchctl kickstart -k "gui/${UID_NUM}/com.obsisstant.agent" >/dev/null 2>&1 || true

sleep 2

echo ""
echo "======================================================"
echo "  Obsisstant OBS Agent is installed & running!"
echo "======================================================"
echo "Log file: $LOG_FILE"
tail -n 6 "$LOG_FILE" 2>/dev/null || true
echo ""
echo "Next step in OBS Studio:"
echo "  Open OBS -> Tools -> WebSocket Server Settings -> Check 'Enable WebSocket server' (port 4455)."
echo "  Your Obsisstant web app will switch to Online automatically!"
BASH;
    }

    private function buildMacUninstallCommand(): string
    {
        return <<<'BASH'
#!/bin/bash
set -euo pipefail

UID_NUM="$(id -u)"
PLIST_FILE="$HOME/Library/LaunchAgents/com.obsisstant.agent.plist"
INSTALL_DIR="$HOME/Library/Application Support/ObsisstantAgent"

launchctl bootout "gui/${UID_NUM}/com.obsisstant.agent" >/dev/null 2>&1 || launchctl unload "$PLIST_FILE" >/dev/null 2>&1 || true
pkill -f "$INSTALL_DIR/src/index.js" >/dev/null 2>&1 || true
rm -f "$PLIST_FILE"
rm -rf "$INSTALL_DIR"

echo "Obsisstant OBS Agent has been stopped and removed from this Mac."
if [ -t 0 ]; then
    read -r -p "Press Return to close this window..." _ || true
fi
BASH;
    }

    private function buildWindowsInstallBat(): string
    {
        return <<<'BAT'
@echo off
title Obsisstant OBS Agent Installer
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-agent.ps1"
echo.
pause
BAT;
    }

    private function buildWindowsUninstallBat(): string
    {
        return <<<'BAT'
@echo off
title Uninstall Obsisstant OBS Agent
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$InstallDir = Join-Path $env:LOCALAPPDATA 'ObsisstantAgent';" ^
  "$Shortcut = Join-Path ([Environment]::GetFolderPath('Startup')) 'Obsisstant OBS Agent.lnk';" ^
  "Get-CimInstance Win32_Process -Filter \"Name='node.exe'\" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -and $_.CommandLine -like '*ObsisstantAgent*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue };" ^
  "if (Test-Path $Shortcut) { Remove-Item $Shortcut -Force };" ^
  "if (Test-Path $InstallDir) { Remove-Item $InstallDir -Recurse -Force };" ^
  "Write-Host 'Obsisstant OBS Agent removed cleanly.' -ForegroundColor Green"
echo.
pause
BAT;
    }

    private function buildWindowsInstallPs1(): string
    {
        return <<<'PS1'
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$SrcAgentDir = Join-Path $ScriptDir 'agent'
$InstallDir = Join-Path $env:LOCALAPPDATA 'ObsisstantAgent'
$LogFile = Join-Path $InstallDir 'agent.log'

Write-Host ""
Write-Host "Installing Obsisstant OBS Agent on Windows..." -ForegroundColor Yellow
Write-Host "Target folder: $InstallDir"

if (-not (Test-Path (Join-Path $SrcAgentDir 'src\index.js')) -or -not (Test-Path (Join-Path $SrcAgentDir '.env'))) {
    Write-Host "ERROR: Missing agent files next to install-agent.ps1." -ForegroundColor Red
    exit 1
}

function Refresh-WindowsPath {
    $MachinePath = [Environment]::GetEnvironmentVariable('Path', 'Machine')
    $UserPath = [Environment]::GetEnvironmentVariable('Path', 'User')
    $env:Path = "$MachinePath;$UserPath"
}

function Find-NodeBinary {
    $Candidates = @()
    $Cmd = Get-Command node -ErrorAction SilentlyContinue
    if ($Cmd) {
        $Candidates += $Cmd.Source
    }
    $Candidates += @(
        (Join-Path $env:ProgramFiles 'nodejs\node.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'nodejs\node.exe'),
        (Join-Path $env:LOCALAPPDATA 'Programs\nodejs\node.exe')
    )

    foreach ($PathItem in $Candidates) {
        if ($PathItem -and (Test-Path $PathItem)) {
            try {
                $Ok = & $PathItem -p "parseInt(process.versions.node.split('.')[0], 10) >= 18" 2>$null
                if ($Ok -eq 'true') {
                    return $PathItem
                }
            }
            catch {
                # Continue scanning candidates
            }
        }
    }

    return $null
}

$NodeExe = Find-NodeBinary

if (-not $NodeExe) {
    Write-Host "Node.js 18+ not detected. Installing Node.js LTS automatically..." -ForegroundColor Cyan

    $WingetCmd = Get-Command winget -ErrorAction SilentlyContinue
    if ($WingetCmd) {
        try {
            & winget install -e --id OpenJS.NodeJS.LTS --silent --accept-package-agreements --accept-source-agreements
            Refresh-WindowsPath
            $NodeExe = Find-NodeBinary
        }
        catch {
            Write-Host "winget fallback unavailable; downloading official Node.js LTS installer..." -ForegroundColor DarkYellow
        }
    }

    if (-not $NodeExe) {
        $ArchSuffix = if ($env:PROCESSOR_ARCHITECTURE -eq 'ARM64') { 'arm64' } else { 'x64' }
        $MsiUrl = "https://nodejs.org/dist/v22.15.0/node-v22.15.0-$ArchSuffix.msi"
        $MsiPath = Join-Path $env:TEMP "obsisstant-nodejs-lts.msi"
        Write-Host "Downloading $MsiUrl..."
        Invoke-WebRequest -UseBasicParsing -Uri $MsiUrl -OutFile $MsiPath
        Write-Host "Running Node.js installer..."
        Start-Process msiexec.exe -ArgumentList "/i `"$MsiPath`" /passive /norestart" -Wait
        Remove-Item $MsiPath -Force -ErrorAction SilentlyContinue
        Refresh-WindowsPath
        $NodeExe = Find-NodeBinary
    }
}

if (-not $NodeExe) {
    Write-Host "ERROR: Node.js 18+ could not be installed automatically. Please install Node.js LTS from https://nodejs.org and re-run." -ForegroundColor Red
    exit 1
}

$NodeDir = Split-Path -Parent $NodeExe
$NpmCmd = Join-Path $NodeDir 'npm.cmd'
if (-not (Test-Path $NpmCmd)) {
    $FoundNpm = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($FoundNpm) {
        $NpmCmd = $FoundNpm.Source
    }
}
if (-not (Test-Path $NpmCmd)) {
    Write-Host "ERROR: npm.cmd not found beside $NodeExe." -ForegroundColor Red
    exit 1
}

Write-Host "Using Node: $NodeExe ($(& $NodeExe -v))" -ForegroundColor Green

# Stop previous agent instance running from this folder before updating files
Get-CimInstance Win32_Process -Filter "Name='node.exe'" -ErrorAction SilentlyContinue | Where-Object {
    $_.CommandLine -and $_.CommandLine -like "*ObsisstantAgent*"
} | ForEach-Object {
    Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
}
Start-Sleep -Milliseconds 400

New-Item -ItemType Directory -Path (Join-Path $InstallDir 'src') -Force | Out-Null
Copy-Item (Join-Path $SrcAgentDir 'package.json') (Join-Path $InstallDir 'package.json') -Force
Copy-Item (Join-Path $SrcAgentDir 'package-lock.json') (Join-Path $InstallDir 'package-lock.json') -Force
Copy-Item (Join-Path $SrcAgentDir 'src\index.js') (Join-Path $InstallDir 'src\index.js') -Force
Copy-Item (Join-Path $SrcAgentDir '.env') (Join-Path $InstallDir '.env') -Force

Write-Host "Installing OBS Agent runtime dependencies..."
Push-Location $InstallDir
try {
    & $NpmCmd install --omit=dev --no-audit --no-fund
}
finally {
    Pop-Location
}

$RunnerBat = Join-Path $InstallDir 'run-agent.cmd'
$RunnerContent = "@echo off`r`ncd /d `"%~dp0`"`r`n`"$NodeExe`" `"src\index.js`" >> `"agent.log`" 2>&1`r`n"
[IO.File]::WriteAllText($RunnerBat, $RunnerContent, [Text.Encoding]::ASCII)

$VbsPath = Join-Path $InstallDir 'start-hidden.vbs'
$VbsContent = "Set shell = CreateObject(""WScript.Shell"")`r`nshell.Run """"""$RunnerBat"""""", 0, False`r`n"
[IO.File]::WriteAllText($VbsPath, $VbsContent, [Text.Encoding]::ASCII)

$StartupDir = [Environment]::GetFolderPath('Startup')
$ShortcutPath = Join-Path $StartupDir 'Obsisstant OBS Agent.lnk'
$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = 'wscript.exe'
$Shortcut.Arguments = "`"$VbsPath`""
$Shortcut.WorkingDirectory = $InstallDir
$Shortcut.Description = 'Starts Obsisstant OBS Agent silently at login'
$Shortcut.Save()

Write-Host "Starting Obsisstant OBS Agent silently in the background..." -ForegroundColor Cyan
Start-Process -FilePath 'wscript.exe' -ArgumentList "`"$VbsPath`"" -WindowStyle Hidden
Start-Sleep -Seconds 2

Write-Host ""
Write-Host "======================================================" -ForegroundColor Green
Write-Host "  Obsisstant OBS Agent is installed & running!" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
Write-Host "Auto-start enabled via Windows Startup folder."
Write-Host "Log file: $LogFile"
if (Test-Path $LogFile) {
    Get-Content $LogFile -Tail 6
}
Write-Host ""
Write-Host "Next step in OBS Studio:" -ForegroundColor Yellow
Write-Host "  Open OBS -> Tools -> WebSocket Server Settings -> Check 'Enable WebSocket server' (port 4455)."
Write-Host "  Your Obsisstant web app will switch to Online automatically!"
PS1;
    }

    private function buildMacStopCommand(): string
    {
        return <<<'SH'
#!/bin/bash
echo "Stopping Obsisstant OBS Agent..."
launchctl bootout "gui/$(id -u)" "$HOME/Library/LaunchAgents/com.obsisstant.agent.plist" 2>/dev/null || true
pkill -f "ObsisstantAgent/src/index.js" 2>/dev/null || true
pkill -f "obs-agent/src/index.js" 2>/dev/null || true
curl -s -X POST "http://127.0.0.1:4458/stop" >/dev/null 2>&1 || true
echo "Obsisstant OBS Agent stopped."
SH;
    }

    private function buildMacStatusCommand(): string
    {
        return <<<'SH'
#!/bin/bash
open "http://127.0.0.1:4458" || echo "Open http://127.0.0.1:4458 in your browser."
SH;
    }

    private function buildWindowsStopBat(): string
    {
        return <<<'BAT'
@echo off
echo Stopping Obsisstant OBS Agent...
schtasks /End /TN "Obsisstant OBS Agent" >nul 2>&1
powershell -NoProfile -Command "try { Invoke-RestMethod -Method Post -Uri 'http://127.0.0.1:4458/stop' | Out-Null } catch {}; Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -match 'ObsisstantAgent|obs-agent' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"
echo Obsisstant OBS Agent stopped.
pause
BAT;
    }

    private function buildWindowsStatusBat(): string
    {
        return <<<'BAT'
@echo off
start "" "http://127.0.0.1:4458"
BAT;
    }

    private function buildWindowsTrayPs1(): string
    {
        return <<<'PS1'
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$notify = New-Object System.Windows.Forms.NotifyIcon
$notify.Icon = [System.Drawing.SystemIcons]::Application
$notify.Text = "Obsisstant OBS Agent"
$notify.Visible = $true

$menu = New-Object System.Windows.Forms.ContextMenuStrip
$openItem = $menu.Items.Add("Open Agent Status & Pairing")
$openItem.Add_Click({ Start-Process "http://127.0.0.1:4458" })

$stopItem = $menu.Items.Add("Stop OBS Agent & Quit")
$stopItem.Add_Click({
    try { Invoke-RestMethod -Method Post -Uri "http://127.0.0.1:4458/stop" | Out-Null } catch {}
    $notify.Visible = $false
    [System.Windows.Forms.Application]::Exit()
})

$notify.ContextMenuStrip = $menu
[System.Windows.Forms.Application]::Run()
PS1;
    }

    private function toCrlf(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);

        return str_replace("\n", "\r\n", $normalized);
    }

    private function resolveAppUrl(): string
    {
        return rtrim((string) config('app.url', 'https://ob'), '/');
    }

    private function cacheStore(): CacheRepository
    {
        return Cache::store((string) config('cache.default', 'file'));
    }

    private function cacheKey(string $token): string
    {
        return "obs.agent.setup.{$token}";
    }
}
