<?php

namespace App\Http\Controllers;

use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\Obs\ObsAgentInstallerService;
use App\Services\Obs\ObsCommandService;
use App\Services\Obs\ObsMediaStorageService;
use App\Services\ReverbStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudioControlCenterController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $studio = ObsStudio::query()
            ->ownedBy($request->user())
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $studio instanceof ObsStudio) {
            return redirect('/admin/obs-studios');
        }

        return $this->show($request, $studio);
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
            'echoConfig' => ReverbStatusService::clientEchoConfig(),
            'mediaStoragePath' => app(ObsMediaStorageService::class)->resolveConfiguredDirectory($studio, $user),
            'agentReachable' => $studio->isAgentReachable(),
            'installerPayload' => app(ObsAgentInstallerService::class)->issueBootstrapPayload($user),
        ]);
    }

    public function snapshot(Request $request, ObsStudio $studio): JsonResponse
    {
        $this->authorizeStudio($request, $studio);

        $recentLogs = ObsCommandLog::query()
            ->where('obs_studio_id', $studio->id)
            ->latest('id')
            ->limit(15)
            ->get(['id', 'command_uuid', 'request_type', 'status', 'error_message', 'created_at'])
            ->map(static fn (ObsCommandLog $log): array => [
                'command_uuid' => $log->command_uuid,
                'request_type' => $log->request_type,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at?->diffForHumans(),
            ])
            ->values();

        $frame = ObsCommandService::previewCacheStore()->get("obs.preview.frame.{$studio->id}");

        return response()->json([
            'status' => $studio->status,
            'is_online' => $studio->isOnline(),
            'agent_reachable' => $studio->isAgentReachable(),
            'live_state' => $studio->live_state ?? [],
            'recent_logs' => $recentLogs,
            'preview_version' => is_array($frame) ? ($frame['version'] ?? null) : null,
            'preview_scene' => is_array($frame) ? ($frame['scene_name'] ?? null) : null,
        ]);
    }

    public function dispatchCommand(Request $request, ObsStudio $studio, ObsCommandService $commandService): JsonResponse
    {
        $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'command_uuid' => ['nullable', 'uuid'],
            'request_type' => ['required', 'string', 'max:120'],
            'request_data' => ['nullable', 'array'],
        ]);

        $log = $commandService->dispatch(
            studio: $studio,
            requestType: $validated['request_type'],
            requestData: $validated['request_data'] ?? [],
            user: $request->user(),
            commandUuid: $validated['command_uuid'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
            'request_type' => $log->request_type,
        ]);
    }

    public function commandResult(Request $request, ObsStudio $studio, string $commandUuid): JsonResponse
    {
        $this->authorizeStudio($request, $studio);

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
        $user = $this->authorizeStudio($request, $studio);
        abort_if((int) $macro->user_id !== $user->id, 403);

        $results = $commandService->executeMacro($studio, $macro, $user);

        return response()->json([
            'ok' => true,
            'results' => $results,
        ]);
    }

    public function updateScreenLive(Request $request, ObsStudio $studio, ObsScreen $screen, ObsCommandService $commandService): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);
        abort_if((int) $screen->user_id !== $user->id, 403);

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
        $user = $this->authorizeStudio($request, $studio);
        abort_if((int) $screen->user_id !== $user->id, 403);

        $validated = $request->validate([
            'scene_name' => ['nullable', 'string', 'max:255'],
        ]);

        $log = $commandService->pushScreenToObs(
            studio: $studio,
            screen: $screen,
            sceneName: $validated['scene_name'] ?? null,
            user: $user,
        );

        return response()->json([
            'ok' => true,
            'command_uuid' => $log->command_uuid,
        ]);
    }

    public function togglePreview(Request $request, ObsStudio $studio, ObsCommandService $commandService): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
            'scene_name' => ['nullable', 'string', 'max:255'],
            'interval_ms' => ['nullable', 'integer', 'min:800', 'max:10000'],
            'width' => ['nullable', 'integer', 'min:320', 'max:1280'],
        ]);

        $log = $commandService->dispatchTransient(
            studio: $studio,
            requestType: 'obsisstant.SetPreviewStream',
            requestData: [
                'active' => (bool) $validated['active'],
                'sceneName' => $validated['scene_name'] ?? null,
                'intervalMs' => (int) ($validated['interval_ms'] ?? 2500),
                'width' => (int) ($validated['width'] ?? 640),
            ],
            user: $user,
        );

        return response()->json([
            'ok' => true,
            'active' => (bool) $validated['active'],
            'command_uuid' => $log->command_uuid,
        ]);
    }

    public function previewFrame(Request $request, ObsStudio $studio): Response
    {
        $this->authorizeStudio($request, $studio);

        $frame = ObsCommandService::previewCacheStore()->get("obs.preview.frame.{$studio->id}");

        $bytes = null;
        if (is_array($frame)) {
            if (! empty($frame['base64'])) {
                $decoded = base64_decode((string) $frame['base64'], true);
                $bytes = $decoded !== false ? $decoded : null;
            } elseif (! empty($frame['bytes'])) {
                $bytes = $frame['bytes'];
            }
        }

        if (! is_string($bytes) || $bytes === '') {
            return response('', 204, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        return response($bytes, 200, [
            'Content-Type' => $frame['mime'] ?? 'image/jpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-OBS-Preview-Scene' => (string) ($frame['scene_name'] ?? ''),
            'X-OBS-Preview-Version' => (string) ($frame['version'] ?? ''),
        ]);
    }

    public function uploadMedia(
        Request $request,
        ObsStudio $studio,
        ObsMediaStorageService $mediaStorage,
        ObsCommandService $commandService,
    ): JsonResponse {
        $user = $this->authorizeStudio($request, $studio);

        $request->validate([
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,gif,webp,bmp', 'max:15360'],
        ]);

        $prepared = $mediaStorage->prepareUpload($request->file('image'), $studio, $user);

        if (! $studio->isOnline() && is_string($prepared['direct_saved_path'])) {
            ObsCommandService::previewCacheStore()->forget("obs.media.upload.{$prepared['token']}");

            return response()->json([
                'ok' => true,
                'mode' => 'direct',
                'file' => $prepared['direct_saved_path'],
                'filename' => $prepared['filename'],
                'target_dir' => $prepared['target_dir'],
            ]);
        }

        $log = $commandService->dispatch(
            studio: $studio,
            requestType: 'obsisstant.SaveMediaFile',
            requestData: [
                'targetDir' => $prepared['target_dir'],
                'filename' => $prepared['filename'],
                'downloadUrl' => route('api.obs-agent.media.download', [
                    'token' => $prepared['token'],
                    'studio_id' => $studio->id,
                ]),
            ],
            user: $user,
        );

        return response()->json([
            'ok' => true,
            'mode' => 'agent',
            'command_uuid' => $log->command_uuid,
            'filename' => $prepared['filename'],
            'target_dir' => $prepared['target_dir'],
        ]);
    }

    public function updateMediaStoragePath(Request $request, ObsStudio $studio): JsonResponse
    {
        $user = $this->authorizeStudio($request, $studio);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'scope' => ['nullable', 'string', 'in:user,studio'],
        ]);

        $cleanPath = trim($validated['path']);
        if (($validated['scope'] ?? 'user') === 'studio') {
            $studio->update(['media_storage_path' => $cleanPath]);
        } else {
            $user->update(['media_storage_path' => $cleanPath]);
            if (filled($studio->media_storage_path)) {
                $studio->update(['media_storage_path' => null]);
            }
        }

        return response()->json([
            'ok' => true,
            'media_storage_path' => $cleanPath,
        ]);
    }

    private function authorizeStudio(Request $request, ObsStudio $studio): User
    {
        $user = $request->user();
        abort_if(! $user instanceof User || (int) $studio->user_id !== $user->id, 403);

        return $user;
    }
}
