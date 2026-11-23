<?php

namespace Tests\Unit;

use App\Services\ChangelogService;
use InvalidArgumentException;
use Tests\TestCase;

class ChangelogServiceTest extends TestCase
{
    public function test_it_parses_releases_from_changelog_md_with_v1_baseline(): void
    {
        $releases = ChangelogService::getReleases();
        $currentVersion = ChangelogService::getCurrentVersion();
        $baselineRelease = collect($releases)->firstWhere('tag', 'v1.0.0');

        $this->assertNotEmpty($releases);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $currentVersion);
        $this->assertSame("v{$currentVersion}", $releases[0]['tag']);
        $this->assertNotNull($baselineRelease);
        $this->assertSame('major', $baselineRelease['type']);
        $this->assertNotEmpty($baselineRelease['sections']);
    }

    public function test_it_computes_next_major_minor_and_macro_versions(): void
    {
        $this->assertSame('2.0.0', ChangelogService::getNextVersion('major', '1.2.3'));
        $this->assertSame('1.3.0', ChangelogService::getNextVersion('minor', '1.2.3'));
        $this->assertSame('1.2.4', ChangelogService::getNextVersion('macro', '1.2.3'));
        $this->assertSame('1.0.1', ChangelogService::getNextVersion('macro', '1.0.0'));
    }

    public function test_it_rejects_invalid_bump_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChangelogService::getNextVersion('invalid', '1.0.0');
    }

    public function test_it_prepends_new_release_to_a_changelog_file(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'changelog_test_');
        $this->assertIsString($tempFile);

        file_put_contents($tempFile, <<<'MD'
# Changelog

## [v1.0.0] - 2026-11-23 | type: major

> Initial stage

### Added
- **Bootstrap**: Initial release.
MD);

        try {
            ChangelogService::prependRelease(
                version: '1.0.1',
                type: 'macro',
                summary: 'Patch release',
                sections: ['Fixed' => ['**Login**: Trimmed whitespace on username input.']],
                date: '2026-11-24',
                path: $tempFile,
            );

            $releases = ChangelogService::getReleases($tempFile);

            $this->assertCount(2, $releases);
            $this->assertSame('1.0.1', $releases[0]['version']);
            $this->assertSame('macro', $releases[0]['type']);
            $this->assertSame('2026-11-24', $releases[0]['date']);
            $this->assertSame('1.0.0', $releases[1]['version']);
        } finally {
            @unlink($tempFile);
        }
    }
}
