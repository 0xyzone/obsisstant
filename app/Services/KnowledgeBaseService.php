<?php

namespace App\Services;

use App\Services\Obs\ObsRequestCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class KnowledgeBaseService
{
    public static function getDirectory(): string
    {
        return base_path('docs/knowledge-base');
    }

    /**
     * Parse all Markdown Knowledge Base guides ordered by frontmatter `order`.
     *
     * @return array<int, array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     order: int,
     *     summary: string,
     *     markdown: string,
     *     html: string,
     *     headings: array<int, array{id: string, text: string}>,
     *     filename: string
     * }>
     */
    public static function getArticles(?string $directory = null): array
    {
        $path = $directory ?? self::getDirectory();

        if (! File::isDirectory($path)) {
            return [];
        }

        $files = collect(File::files($path))
            ->filter(static fn (SplFileInfo $file): bool => strtolower($file->getExtension()) === 'md')
            ->sortBy(static fn (SplFileInfo $file): string => $file->getFilename());

        $articles = [];

        foreach ($files as $file) {
            $parsed = self::parseFile($file->getPathname(), $file->getFilename());
            if ($parsed !== null) {
                $articles[] = $parsed;
            }
        }

        usort(
            $articles,
            static fn (array $a, array $b): int => [$a['order'], $a['filename']] <=> [$b['order'], $b['filename']]
        );

        return $articles;
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     order: int,
     *     summary: string,
     *     markdown: string,
     *     html: string,
     *     headings: array<int, array{id: string, text: string}>,
     *     filename: string
     * }|null
     */
    public static function findArticle(string $slug, ?string $directory = null): ?array
    {
        foreach (self::getArticles($directory) as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }

        return null;
    }

    /**
     * Group the OBS WebSocket v5 request catalog cleanly for the interactive docs reference.
     *
     * @return array<string, array<int, array{
     *     request_type: string,
     *     category: string,
     *     label: string,
     *     summary: string,
     *     fields: array<int, string>,
     *     example_json: string,
     *     notes: string
     * }>>
     */
    public static function getCommandReference(): array
    {
        $grouped = [];

        foreach (ObsRequestCatalog::definitions() as $requestType => $definition) {
            $example = $definition['example_payload'];
            $exampleJson = $example === []
                ? '{}'
                : (string) json_encode($example, JSON_UNESCAPED_SLASHES);

            $grouped[$definition['category']][] = [
                'request_type' => $requestType,
                'category' => $definition['category'],
                'label' => $definition['label'],
                'summary' => $definition['summary'],
                'fields' => $definition['fields'],
                'example_json' => $exampleJson,
                'notes' => (string) ($definition['notes'] ?? ''),
            ];
        }

        return $grouped;
    }

    /**
     * @return array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     order: int,
     *     summary: string,
     *     markdown: string,
     *     html: string,
     *     headings: array<int, array{id: string, text: string}>,
     *     filename: string
     * }|null
     */
    protected static function parseFile(string $filepath, string $filename): ?array
    {
        $raw = File::get($filepath);
        if (trim($raw) === '') {
            return null;
        }

        $meta = [];
        $body = $raw;

        if (preg_match('/^\s*---\s*\R(.*?)\R---\s*\R?(.*)$/s', $raw, $matches)) {
            foreach (preg_split("/\r\n|\n|\r/", $matches[1]) ?: [] as $line) {
                if (preg_match('/^([a-zA-Z0-9_-]+)\s*:\s*(.+)$/', trim($line), $kv)) {
                    $meta[strtolower($kv[1])] = trim($kv[2], " \t\"'");
                }
            }
            $body = $matches[2];
        }

        $baseSlug = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $slug = (string) ($meta['slug'] ?? $baseSlug);
        $title = (string) ($meta['title'] ?? Str::headline($baseSlug));
        $category = (string) ($meta['category'] ?? 'General');
        $order = (int) ($meta['order'] ?? 99);
        $summary = (string) ($meta['summary'] ?? '');

        $renderedHtml = Str::markdown($body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        [$enrichedHtml, $headings] = self::enrichHeadings($renderedHtml, $slug);

        return [
            'slug' => $slug,
            'title' => $title,
            'category' => $category,
            'order' => $order,
            'summary' => $summary,
            'markdown' => trim($body),
            'html' => $enrichedHtml,
            'headings' => $headings,
            'filename' => $filename,
        ];
    }

    /**
     * Inject deterministic anchor IDs on <h2> headings and extract article outline links.
     *
     * @return array{0: string, 1: array<int, array{id: string, text: string}>}
     */
    protected static function enrichHeadings(string $html, string $articleSlug): array
    {
        $headings = [];

        $enriched = preg_replace_callback('/<h2>(.*?)<\/h2>/si', static function (array $matches) use ($articleSlug, &$headings): string {
            $plainText = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $headingId = $articleSlug.'-'.Str::slug($plainText);
            $headings[] = [
                'id' => $headingId,
                'text' => $plainText,
            ];

            return '<h2 id="'.e($headingId).'">'.$matches[1].'</h2>';
        }, $html) ?? $html;

        return [$enriched, $headings];
    }
}
