<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ChangelogService
{
    public const string DEFAULT_VERSION = '1.0.0';

    public static function getChangelogPath(): string
    {
        return base_path('changelog.md');
    }

    /**
     * Parse all releases from changelog.md in reverse-chronological order.
     *
     * @return array<int, array{
     *     version: string,
     *     tag: string,
     *     date: string,
     *     type: string,
     *     summary: string,
     *     sections: array<int, array{
     *         category: string,
     *         items: array<int, array{title: ?string, body: string, body_html: string}>
     *     }>
     * }>
     */
    public static function getReleases(?string $path = null): array
    {
        $changelogPath = $path ?? self::getChangelogPath();

        if (! File::exists($changelogPath)) {
            return [];
        }

        $content = File::get($changelogPath);
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];

        $releases = [];
        $currentRelease = null;
        $currentSectionIndex = null;

        foreach ($lines as $rawLine) {
            $line = rtrim($rawLine);

            if (preg_match('/^##\s+\[v?(\d+\.\d+\.\d+)\]\s*-\s*(\d{4}-\d{2}-\d{2})(?:\s*\|\s*type:\s*(major|minor|macro|patch|micro))?/i', $line, $matches)) {
                if ($currentRelease !== null) {
                    $releases[] = $currentRelease;
                }

                $version = $matches[1];
                $rawType = strtolower($matches[3] ?? '');
                $type = self::normalizeType($rawType, $version);

                $currentRelease = [
                    'version' => $version,
                    'tag' => "v{$version}",
                    'date' => $matches[2],
                    'type' => $type,
                    'summary' => '',
                    'sections' => [],
                ];
                $currentSectionIndex = null;

                continue;
            }

            if ($currentRelease === null) {
                continue;
            }

            if (preg_match('/^>\s*(.+)$/', trim($line), $summaryMatch)) {
                $summaryText = trim($summaryMatch[1]);
                $currentRelease['summary'] = $currentRelease['summary'] === ''
                    ? $summaryText
                    : $currentRelease['summary'].' '.$summaryText;

                continue;
            }

            if (preg_match('/^###\s+(.+)$/', trim($line), $sectionMatch)) {
                $currentRelease['sections'][] = [
                    'category' => trim($sectionMatch[1]),
                    'items' => [],
                ];
                $currentSectionIndex = array_key_last($currentRelease['sections']);

                continue;
            }

            if ($currentSectionIndex !== null && preg_match('/^\s*-\s+(.+)$/', $line, $itemMatch)) {
                $rawItem = trim($itemMatch[1]);
                $parsedItem = self::parseItem($rawItem);
                $currentRelease['sections'][$currentSectionIndex]['items'][] = $parsedItem;
            }
        }

        if ($currentRelease !== null) {
            $releases[] = $currentRelease;
        }

        return $releases;
    }

    public static function getCurrentVersion(?string $path = null): string
    {
        $releases = self::getReleases($path);

        return $releases[0]['version'] ?? self::DEFAULT_VERSION;
    }

    /**
     * Parse semantic version parts [major, minor, macro].
     *
     * @return array{major: int, minor: int, macro: int}
     */
    public static function parseVersion(?string $version = null): array
    {
        $version = $version ?? self::getCurrentVersion();

        if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', trim($version), $matches)) {
            return [
                'major' => (int) $matches[1],
                'minor' => (int) $matches[2],
                'macro' => (int) $matches[3],
            ];
        }

        return [
            'major' => 1,
            'minor' => 0,
            'macro' => 0,
        ];
    }

    public static function getNextVersion(string $type = 'macro', ?string $currentVersion = null): string
    {
        $parts = self::parseVersion($currentVersion);
        $normalizedType = self::normalizeBumpType($type);

        return match ($normalizedType) {
            'major' => ($parts['major'] + 1).'.0.0',
            'minor' => $parts['major'].'.'.($parts['minor'] + 1).'.0',
            'macro' => $parts['major'].'.'.$parts['minor'].'.'.($parts['macro'] + 1),
        };
    }

    /**
     * Calculate aggregate release counts by update type.
     *
     * @param  array<int, array<string, mixed>>  $releases
     * @return array{total: int, major: int, minor: int, macro: int}
     */
    public static function getStats(array $releases): array
    {
        $stats = [
            'total' => count($releases),
            'major' => 0,
            'minor' => 0,
            'macro' => 0,
        ];

        foreach ($releases as $release) {
            $type = $release['type'] ?? 'macro';
            if (array_key_exists($type, $stats)) {
                $stats[$type]++;
            }
        }

        return $stats;
    }

    /**
     * Prepend a new release block to changelog.md.
     *
     * @param  array<string, array<int, string>>  $sections
     */
    public static function prependRelease(
        string $version,
        string $type,
        string $summary,
        array $sections,
        ?string $date = null,
        ?string $path = null,
    ): void {
        $changelogPath = $path ?? self::getChangelogPath();
        $cleanVersion = ltrim(trim($version), 'vV');
        $normalizedType = self::normalizeBumpType($type);
        $releaseDate = $date ?? now()->timezone('Asia/Kathmandu')->toDateString();

        $blockLines = [
            "## [v{$cleanVersion}] - {$releaseDate} | type: {$normalizedType}",
            '',
            "> {$summary}",
        ];

        foreach ($sections as $category => $items) {
            if ($items === []) {
                continue;
            }

            $blockLines[] = '';
            $blockLines[] = "### {$category}";
            foreach ($items as $item) {
                $blockLines[] = "- {$item}";
            }
        }

        $newBlock = implode("\n", $blockLines)."\n";

        if (! File::exists($changelogPath)) {
            File::put($changelogPath, "# Changelog\n\n{$newBlock}");

            return;
        }

        $existing = File::get($changelogPath);
        $firstReleasePos = strpos($existing, "\n## ");

        if ($firstReleasePos === false) {
            File::put($changelogPath, rtrim($existing)."\n\n{$newBlock}");

            return;
        }

        $header = rtrim(substr($existing, 0, $firstReleasePos));
        $rest = ltrim(substr($existing, $firstReleasePos));

        File::put($changelogPath, "{$header}\n\n{$newBlock}\n{$rest}");
    }

    public static function normalizeBumpType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'major' => 'major',
            'minor' => 'minor',
            'macro', 'micro', 'patch' => 'macro',
            default => throw new InvalidArgumentException("Invalid release type '{$type}'. Expected major, minor, or macro."),
        };
    }

    protected static function normalizeType(string $rawType, string $version): string
    {
        if (in_array($rawType, ['major', 'minor', 'macro'], true)) {
            return $rawType;
        }

        if (in_array($rawType, ['patch', 'micro'], true)) {
            return 'macro';
        }

        $parts = self::parseVersion($version);
        if ($parts['minor'] === 0 && $parts['macro'] === 0) {
            return 'major';
        }

        if ($parts['macro'] === 0) {
            return 'minor';
        }

        return 'macro';
    }

    /**
     * @return array{title: ?string, body: string, body_html: string}
     */
    protected static function parseItem(string $rawItem): array
    {
        $title = null;
        $body = $rawItem;

        if (preg_match('/^\*\*(.+?)\*\*:\s*(.+)$/', $rawItem, $matches)) {
            $title = trim($matches[1]);
            $body = trim($matches[2]);
        }

        return [
            'title' => $title,
            'body' => $body,
            'body_html' => self::formatInlineMarkdown($body),
        ];
    }

    protected static function formatInlineMarkdown(string $text): string
    {
        $escaped = e($text);
        $escaped = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;

        return $escaped;
    }
}
