<?php

namespace App\Http\Controllers;

use App\Models\ObsScreen;
use Illuminate\Contracts\View\View;

class ObsOverlayController extends Controller
{
    public function show(string $token): View
    {
        $screen = ObsScreen::query()
            ->where('public_token', $token)
            ->firstOrFail();

        return view('obs.overlay', [
            'screen' => $screen,
            'theme' => $screen->getMergedTheme(),
            'state' => $screen->getMergedState(),
            'echoConfig' => config('filament.broadcasting.echo'),
        ]);
    }
}
