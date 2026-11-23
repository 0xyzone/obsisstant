<?php

namespace App\Http\Controllers;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\ChangelogService;
use App\Services\Obs\ObsScreenPresetService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'studios_total' => 1,
            'studios_online' => 0,
            'screens_total' => 3,
            'macros_total' => 2,
            'primary_studio_slug' => null,
        ];

        try {
            if (Schema::hasTable('obs_studios')) {
                $metrics['studios_total'] = ObsStudio::query()->where('is_active', true)->count();
                $metrics['studios_online'] = ObsStudio::query()
                    ->whereIn('status', ['online', 'streaming', 'recording'])
                    ->count();
                $metrics['primary_studio_slug'] = ObsStudio::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->value('slug');
            }

            if (Schema::hasTable('obs_screens')) {
                $metrics['screens_total'] = ObsScreen::query()->count();
            }

            if (Schema::hasTable('obs_macros')) {
                $metrics['macros_total'] = ObsMacro::query()->count();
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
