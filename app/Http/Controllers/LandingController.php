<?php

namespace App\Http\Controllers;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\ChangelogService;
use App\Services\Obs\ObsScreenPresetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = [
            'studios_total' => 1,
            'studios_online' => 0,
            'screens_total' => 3,
            'macros_total' => 2,
            'primary_studio_slug' => null,
        ];

        try {
            if (Schema::hasTable('obs_studios')) {
                $studioQuery = ObsStudio::query()->where('is_active', true);
                if ($user !== null) {
                    $studioQuery->ownedBy($user);
                }
                $metrics['studios_total'] = (clone $studioQuery)->count();
                $metrics['studios_online'] = (clone $studioQuery)
                    ->whereIn('status', ['online', 'streaming', 'recording'])
                    ->count();
                $metrics['primary_studio_slug'] = $user !== null
                    ? (clone $studioQuery)->orderBy('id')->value('slug')
                    : null;
            }

            if (Schema::hasTable('obs_screens')) {
                $metrics['screens_total'] = $user !== null
                    ? ObsScreen::query()->ownedBy($user)->count()
                    : ObsScreen::query()->count();
            }

            if (Schema::hasTable('obs_macros')) {
                $metrics['macros_total'] = $user !== null
                    ? ObsMacro::query()->ownedBy($user)->count()
                    : ObsMacro::query()->count();
            }
        } catch (Throwable) {
            // Fall back safely if the landing page renders before database migrations run.
        }

        $presets = [];
        foreach (ObsScreenPresetService::templateOptions() as $key => $label) {
            $presets[] = [
                'key' => $key,
                'label' => $label,
                'theme' => ObsScreenPresetService::defaultTheme($key),
                'state' => ObsScreenPresetService::defaultState($key),
            ];
        }

        return view('welcome', [
            'currentVersion' => ChangelogService::getCurrentVersion(),
            'metrics' => $metrics,
            'presets' => $presets,
        ]);
    }
}
