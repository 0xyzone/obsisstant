<?php

namespace App\Http\Controllers;

use App\Services\Obs\LumiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LumiAssistantController extends Controller
{
    public function __invoke(Request $request, LumiAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:400'],
            'studio_slug' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $assistant->reply(
            message: (string) $validated['message'],
            user: $request->user(),
            studioSlug: $validated['studio_slug'] ?? null,
        );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }
}
