<?php

namespace App\Http\Middleware;

use App\Models\ObsStudio;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateObsAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Obs-Agent-Token');

        if (! is_string($token) || trim($token) === '') {
            return response()->json(['message' => 'Missing OBS Studio agent token.'], 401);
        }

        $normalized = trim($token);
        $relayToken = trim((string) config('obs.relay_token', ''));

        if ($relayToken !== '' && hash_equals($relayToken, $normalized)) {
            $request->attributes->set('obs_relay_mode', true);
            $request->attributes->set('obs_relay_scope', 'global');

            return $this->resolveRelayStudio($request, $next);
        }

        $relayUser = User::query()
            ->where('relay_token', $normalized)
            ->first();

        if ($relayUser instanceof User) {
            $request->attributes->set('obs_relay_mode', true);
            $request->attributes->set('obs_relay_scope', 'user');
            $request->attributes->set('obs_relay_user', $relayUser);

            return $this->resolveRelayStudio($request, $next, $relayUser);
        }

        $studio = ObsStudio::query()
            ->where('agent_token', $normalized)
            ->where('is_active', true)
            ->first();

        if (! $studio instanceof ObsStudio) {
            return response()->json(['message' => 'Invalid or inactive OBS Studio agent token.'], 401);
        }

        $request->attributes->set('obs_relay_mode', false);
        $request->attributes->set('obs_relay_scope', 'studio');
        $request->attributes->set('obs_studio', $studio);

        return $next($request);
    }

    private function resolveRelayStudio(Request $request, Closure $next, ?User $scopedUser = null): Response
    {
        $studioId = $request->input('studio_id') ?? $request->query('studio_id');
        if ($studioId !== null && $studioId !== '') {
            $studio = ObsStudio::query()
                ->where('id', (int) $studioId)
                ->where('is_active', true)
                ->when($scopedUser instanceof User, fn ($q) => $q->where('user_id', $scopedUser->id))
                ->first();

            if (! $studio instanceof ObsStudio) {
                return response()->json(['message' => 'Target OBS Studio not found or unauthorized.'], 404);
            }

            $request->attributes->set('obs_studio', $studio);
        }

        return $next($request);
    }
}
