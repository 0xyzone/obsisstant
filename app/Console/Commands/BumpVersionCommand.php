<?php

namespace App\Console\Commands;

use App\Services\ChangelogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('version:bump {type=macro : Release update type: major, minor, or macro} {--summary= : Release summary line} {--added=* : Added items for the release} {--changed=* : Changed items for the release} {--fixed=* : Fixed items for the release} {--write : Prepend the release entry to changelog.md}')]
#[Description('Calculate the next semantic version (major, minor, macro) and optionally write it to changelog.md')]
class BumpVersionCommand extends Command
{
    public function handle(): int
    {
        $rawType = strtolower((string) $this->argument('type'));

        if (! in_array($rawType, ['major', 'minor', 'macro', 'micro', 'patch'], true)) {
            $this->error("Invalid update type '{$rawType}'. Allowed values: major, minor, macro.");

            return self::FAILURE;
        }

        $type = ChangelogService::normalizeBumpType($rawType);
        $current = ChangelogService::getCurrentVersion();
        $next = ChangelogService::getNextVersion($type, $current);
        $summary = trim((string) ($this->option('summary') ?: "Release v{$next}"));

        $this->table(
            ['Property', 'Value'],
            [
                ['Update Type', strtoupper($type)],
                ['Current Version', "v{$current}"],
                ['Next Version', "v{$next}"],
                ['Summary', $summary],
            ]
        );

        if ($this->option('write')) {
            /** @var array<int, string> $added */
            $added = array_values(array_filter((array) $this->option('added')));
            /** @var array<int, string> $changed */
            $changed = array_values(array_filter((array) $this->option('changed')));
            /** @var array<int, string> $fixed */
            $fixed = array_values(array_filter((array) $this->option('fixed')));

            $sections = array_filter([
                'Added' => $added,
                'Changed' => $changed,
                'Fixed' => $fixed,
            ]);

            if ($sections === []) {
                $sections = [
                    'Changed' => ["**Release v{$next}**: {$summary}"],
                ];
            }

            ChangelogService::prependRelease($next, $type, $summary, $sections);
            $this->info("Updated changelog.md with v{$next} ({$type}).");
        }

        return self::SUCCESS;
    }
}
