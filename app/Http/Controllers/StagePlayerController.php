<?php

namespace App\Http\Controllers;

use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Services\Obs\ScreenCollectionService;
use App\Services\ReverbStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StagePlayerController extends Controller
{
    public function show(string $token): View
    {
        $collection = ScreenCollection::query()
            ->where('public_token', $token)
            ->with(['views', 'assets'])
            ->firstOrFail();

        return view('stage.player', [
            'collection' => $collection,
            'stagePayload' => ScreenCollectionService::buildStagePayload($collection),
            'echoConfig' => ReverbStatusService::clientEchoConfig(),
        ]);
    }

    public function state(string $token): JsonResponse
    {
        $collection = ScreenCollection::query()
            ->where('public_token', $token)
            ->with(['views', 'assets'])
            ->firstOrFail();

        return response()->json(ScreenCollectionService::buildStagePayload($collection));
    }

    public function logicTrigger(
        Request $request,
        string $token,
        ScreenCollectionService $collectionService,
    ): JsonResponse {
        $collection = ScreenCollection::query()
            ->where('public_token', $token)
            ->firstOrFail();

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:timer_end'],
            'view_id' => ['required', 'integer'],
            'marker' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $collectionService->fireTimerEndLogic(
            collection: $collection,
            expectedViewId: (int) $validated['view_id'],
            marker: $validated['marker'] ?? null,
        );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }

    public function serveAsset(ScreenAsset $asset): BinaryFileResponse
    {
        $disk = Storage::disk($asset->disk ?: 'public');
        abort_unless($disk->exists($asset->path), 404);

        return response()->file($disk->path($asset->path), [
            'Content-Type' => $asset->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
