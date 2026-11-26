<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Package;
use App\Services\ChangelogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(): View
    {
        $packages = Package::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_cents')
            ->get();

        return view('pricing', [
            'packages' => $packages,
            'featureCatalog' => Package::featureCatalog(),
            'limitCatalog' => Package::limitCatalog(),
            'currentVersion' => ChangelogService::getCurrentVersion(),
        ]);
    }

    public function storeDemo(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'channel_or_org' => ['nullable', 'string', 'max:150'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'message' => ['nullable', 'string', 'max:1200'],
        ]);

        $demo = DemoRequest::query()->create([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
            'channel_or_org' => filled($validated['channel_or_org'] ?? null) ? trim((string) $validated['channel_or_org']) : null,
            'package_id' => $validated['package_id'] ?? null,
            'message' => filled($validated['message'] ?? null) ? trim((string) $validated['message']) : null,
            'status' => DemoRequest::STATUS_PENDING,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $demo->id,
                'message' => 'Thanks! Your demo request has been received. We will reach out shortly.',
            ], 201);
        }

        return back()->with('demo_submitted', 'Thanks! Your demo request has been received. We will reach out shortly.');
    }
}
