<?php

namespace App\Http\Controllers;

use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\ScreenCollection;
use App\Models\User;
use App\Services\Obs\ObsCommandService;
use App\Services\Obs\ScreenCollectionPresetService;
use App\Services\Obs\ScreenCollectionService;
use App\Services\ReverbStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ObsMobileDeckController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $studio = ObsStudio::query()
            ->ownedBy($request->user())
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $studio instanceof ObsStudio) {
            return redirect('/admin/obs-studios');
        }

        return redirect()->route('obs.deck.show', ['studio' => $studio->slug]);
    }

    public function show(Request $request, ObsStudio $studio): View
    {
        $user = $this->authorizeStudio($request, $studio);

        $studios = ObsStudio::query()
            ->ownedBy($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $screens = ObsScreen::query()
            ->ownedBy($user)
            ->where(fn ($query) => $query->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->orderBy('name')
            ->get();
        $macros = ObsMacro::query()
            ->ownedBy($user)
            ->where(fn ($query) => $query->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $collections = ScreenCollection::query()
            ->ownedBy($user)
            ->where(fn ($query) => $query->where('obs_studio_id', $studio->id)->orWhereNull('obs_studio_id'))
            ->with(['views', 'assets'])
            ->orderBy('name')
            ->get();

        $collectionsPayload = $collections
            ->map(static fn (ScreenCollection $col): array => ScreenCollectionService::buildStagePayload($col))
            ->values()
            ->all();

        $activeCollectionId = $studio->active_screen_collection_id ?? $collections->first()?->id;

        return view('obs.mobile-deck', [
            'studio' => $studio,
            'studios' => $studios,
            'screens' => $screens,
            'macros' => $macros,
            'collectionsPayload' => $collectionsPayload,
            'activeCollectionId' => $activeCollectionId,
            'controlMode' => $studio->control_mode ?: 'obs',
            'transitionTypes' => ScreenCollectionPresetService::transitionOptions(),
            'echoConfig' => ReverbStatusService::clientEchoConfig(),
        ]);
    }

    public function dispatchCommand(Request $request, ObsStudio $studio, ObsCommandService $commandService): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'command_uuid' => ['nullable', 'uuid'],
            'request_type' => ['required', 'string', 'max:120'],
            'request_data' => ['nullable', 'array'],
        ]);

        $log = $commandService->dispatch(
            studio: $studio,
            requestType: $validated['request_type'],
            requestData: $validated['request_data'] ?? [],
            user: $user,
            commandUuid: $validated['command_uuid'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
            'request_type' => $log->request_type,
        ]);
    }

    public function runMacro(Request $request, ObsStudio $studio, ObsMacro $macro, ObsCommandService $commandService): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);
        abort_if((int) $macro->user_id !== $user->id, 403);

        $results = $commandService->executeMacro($studio, $macro, $user);

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function updateScreenLive(Request $request, ObsScreen $screen, ObsCommandService $commandService): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof User || (int) $screen->user_id !== $user->id, 403);

        $validated = $request->validate([
            'state' => ['required', 'array'],
        ]);

        $updated = $commandService->updateScreenState($screen, $validated['state']);

        return response()->json([
            'ok' => true,
            'state' => $updated->getMergedState(),
        ]);
    }

    private function authorizeStudio(Request $request, ObsStudio $studio): User
    {
        $user = $request->user();
        abort_if(! $user instanceof User || (int) $studio->user_id !== $user->id, 403);

        return $user;
    }
}
