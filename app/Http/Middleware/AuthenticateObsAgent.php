<?php

namespace App\Http\Middleware;

use App\Models\ObsStudio;
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

        $studio = ObsStudio::query()
            ->where('agent_token', trim($token))
            ->where('is_active', true)
            ->first();

        if (! $studio instanceof ObsStudio) {
            return response()->json(['message' => 'Invalid or inactive OBS Studio agent token.'], 401);
        }

        $request->attributes->set('obs_studio', $studio);

        return $next($request);
    }
}
