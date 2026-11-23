<?php

namespace App\Http\Controllers;

use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Services\ChangelogService;
use App\Services\Obs\ObsCommandService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class StudioControlCenterController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $studio = ObsStudio::query()->where('is_active', true)->orderBy('id')->first();

        if (! $studio instanceof ObsStudio) {
            return redirect('/admin/obs-studios');
        }

        return $this->show($studio);
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

        $recentLogs = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->latest('id')
            ->limit(12)
            ->get(['id', 'command_uuid', 'request_type', 'status', 'error_message', 'created_at']);

        return view('studio.control-center', [
            'currentVersion' => ChangelogService::getCurrentVersion(),
            'studio' => $studio,
            'studios' => $studios,
            'screens' => $screens,
            'macros' => $macros,
            'recentLogs' => $recentLogs,
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

    public function commandResult(ObsStudio $studio, string $commandUuid): JsonResponse
    {
        $log = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->where('command_uuid', $commandUuid)
            ->firstOrFail();

        return response()->json([
            'command_uuid' => $log->command_uuid,
            'request_type' => $log->request_type,
            'status' => $log->status,
            'response_data' => $log->response_data,
            'error_message' => $log->error_message,
            'executed_at' => $log->executed_at?->toIso8601String(),
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

    public function updateScreenLive(Request $request, ObsStudio $studio, ObsScreen $screen, ObsCommandService $commandService): JsonResponse
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

    public function pushScreenToObs(Request $request, ObsStudio $studio, ObsScreen $screen, ObsCommandService $commandService): JsonResponse
    {
        $validated = $request->validate([
            'scene_name' => ['nullable', 'string', 'max:255'],
        ]);

        $log = $commandService->pushScreenToObs(
            studio: $studio,
            screen: $screen,
            sceneName: $validated['scene_name'] ?? null,
            user: $request->user(),
        );

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
        ]);
    }

    public function togglePreview(Request $request, ObsStudio $studio, ObsCommandService $commandService): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
            'scene_name' => ['nullable', 'string', 'max:255'],
            'interval_ms' => ['nullable', 'integer', 'min:350', 'max:5000'],
            'width' => ['nullable', 'integer', 'min:320', 'max:1920'],
        ]);

        $log = $commandService->dispatch(
            studio: $studio,
            requestType: 'obsisstant.SetPreviewStream',
            requestData: [
                'active' => (bool) $validated['active'],
                'sceneName' => $validated['scene_name'] ?? null,
                'intervalMs' => (int) ($validated['interval_ms'] ?? 650),
                'width' => (int) ($validated['width'] ?? 960),
            ],
            user: $request->user(),
        );

        return response()->json([
            'ok' => true,
            'active' => (bool) $validated['active'],
            'command_uuid' => $log->command_uuid,
        ]);
    }

    public function previewFrame(ObsStudio $studio): Response
    {
        $frame = Cache::get("obs.preview.frame.{$studio->id}");

        if (! is_array($frame) || empty($frame['bytes'])) {
            return response('', 204, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        return response($frame['bytes'], 200, [
            'Content-Type' => $frame['mime'] ?? 'image/jpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-OBS-Preview-Scene' => (string) ($frame['scene_name'] ?? ''),
            'X-OBS-Preview-Version' => (string) ($frame['version'] ?? ''),
        ]);
    }
}
