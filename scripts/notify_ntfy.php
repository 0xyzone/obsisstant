<?php

/**
 * ntfy.sh Task Completion Notification Helper
 * Usage: php scripts/notify_ntfy.php <success|failure> "Summary text"
 */
$status = strtolower($argv[1] ?? 'success');
$summary = $argv[2] ?? 'Task completed.';

$topic = getenv('NTFY_TOPIC') ?: null;
$server = getenv('NTFY_SERVER') ?: null;

$envPath = __DIR__.'/../.env';
if (file_exists($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (! $topic && str_starts_with($line, 'NTFY_TOPIC=')) {
            $topic = trim(substr($line, 11), " \t\n\r\0\x0B\"'");
        }
        if (! $server && str_starts_with($line, 'NTFY_SERVER=')) {
            $server = trim(substr($line, 12), " \t\n\r\0\x0B\"'");
        }
    }
}

$topic = $topic ?: 'obsisstant-tasks';
$server = rtrim($server ?: 'https://ntfy.sh', '/');

$version = 'v1.0.0';
$changelogPath = __DIR__.'/../changelog.md';
if (file_exists($changelogPath)) {
    $changelog = file_get_contents($changelogPath) ?: '';
    if (preg_match('/^##\s+\[v?(\d+\.\d+\.\d+)\]/m', $changelog, $matches)) {
        $version = 'v'.$matches[1];
    }
}

$isSuccess = in_array($status, ['success', 'ok', 'done'], true);
$title = $isSuccess
    ? "Obsisstant {$version}: Task Completed"
    : "Obsisstant {$version}: Task Failed";
$priority = $isSuccess ? 4 : 5;
$tags = $isSuccess ? ['white_check_mark', 'sparkles'] : ['x', 'warning'];

date_default_timezone_set('Asia/Kathmandu');
$now = (new DateTime('now', new DateTimeZone('Asia/Kathmandu')))->format('Y-m-d h:i:s A T');

$body = "{$summary}\n\nVersion: {$version}\nTimestamp: {$now}";

$payload = json_encode([
    'topic' => $topic,
    'title' => $title,
    'message' => $body,
    'priority' => $priority,
    'tags' => $tags,
    'click' => 'https://ob/changelog',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($server);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: '.strlen((string) $payload),
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    fwrite(STDERR, "ntfy cURL Error: {$error}\n");
    exit(1);
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo "ntfy notification sent successfully to '{$topic}' (HTTP {$httpCode}).\n";
    exit(0);
}

fwrite(STDERR, "ntfy error: HTTP {$httpCode} - {$response}\n");
exit(1);
