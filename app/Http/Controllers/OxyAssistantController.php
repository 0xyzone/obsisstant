<?php

namespace App\Http\Controllers;

use App\Services\Obs\OxyAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OxyAssistantController extends Controller
{
    public function __invoke(Request $request, OxyAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:400'],
            'studio_slug' => ['nullable', 'string', 'max:120'],
            'history' => ['nullable', 'array', 'max:8'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:600'],
        ]);

        $result = $assistant->reply(
            message: (string) $validated['message'],
            user: $request->user(),
            studioSlug: $validated['studio_slug'] ?? null,
            history: $validated['history'] ?? [],
        );

        return response()->json([
            'ok' => true,
            ...$result,
        ]);
    }
}
