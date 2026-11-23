<?php

namespace App\Http\Controllers;

use App\Services\ChangelogService;
use Illuminate\Contracts\View\View;

class ChangelogController extends Controller
{
    public function __invoke(): View
    {
        $releases = ChangelogService::getReleases();
        $currentVersion = ChangelogService::getCurrentVersion();
        $stats = ChangelogService::getStats($releases);

        return view('changelog', [
            'releases' => $releases,
            'currentVersion' => $currentVersion,
            'stats' => $stats,
        ]);
    }
}
