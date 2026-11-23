<?php

namespace App\Http\Controllers;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\Obs\ObsCommandService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ObsMobileDeckController extends Controller
{
    public function index(): RedirectResponse
    {
        $studio = ObsStudio::query()->where('is_active', true)->orderBy('id')->first();

        if (! $studio instanceof ObsStudio) {
            return redirect('/admin/obs-studios');
        }

        return redirect()->route('obs.deck.show', ['studio' => $studio->slug]);
    }

    public function show(ObsStudio $studio): View
    {
        $studios = ObsStudio::query()->where('is_active', true)->orderBy('name')->get();
        $screens = ObsScreen::query()
            ->where(fn ($query) => $query->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->orderBy('name')
            ->get();
        $macros = ObsMacro::query()
            ->where(fn ($query) => $query->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('obs.mobile-deck', [
            'studio' => $studio,
            'studios' => $studios,
            'screens' => $screens,
            'macros' => $macros,
            'echoConfig' => config('filament.broadcasting.echo'),
        ]);
    }

    public function dispatchCommand(Request $request, ObsStudio $studio, ObsCommandService $commandService): JsonResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', 'string', 'max:120'],
            'request_data' => ['nullable', 'array'],
        ]);

        $log = $commandService->dispatch(
            studio: $studio,
            requestType: $validated['request_type'],
            requestData: $validated['request_data'] ?? [],
            user: $request->user(),
        );

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
            'request_type' => $log->request_type,
        ]);
    }

    public function runMacro(Request $request, ObsStudio $studio, ObsMacro $macro, ObsCommandService $commandService): JsonResponse
    {
        $results = $commandService->executeMacro($studio, $macro, $request->user());

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function updateScreenLive(Request $request, ObsScreen $screen, ObsCommandService $commandService): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'array'],
        ]);

        $updated = $commandService->updateScreenState($screen, $validated['state']);

        return response()->json([
            'ok' => true,
            'state' => $updated->getMergedState(),
        ]);
    }
}
