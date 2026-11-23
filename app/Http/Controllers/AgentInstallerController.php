<?php

namespace App\Http\Controllers;

use App\Models\ObsStudio;
use App\Models\User;
use App\Services\Obs\ObsAgentInstallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentInstallerController extends Controller
{
    public function __construct(public ObsAgentInstallerService $installer) {}

    public function download(Request $request, string $platform): BinaryFileResponse
    {
        /** @var User $user */
        $user = $request->user();
        $studio = $this->resolveAuthorizedStudio($user, $request->query('studio'));
        $normalized = $this->installer->normalizePlatform($platform);

        $zipPath = $this->installer->buildBundleZip($user, $normalized, $studio);
        $fileName = $normalized === ObsAgentInstallerService::PLATFORM_MAC
            ? 'Obsisstant-OBS-Agent-Mac.zip'
            : 'Obsisstant-OBS-Agent-Windows.zip';

        return response()
            ->download($zipPath, $fileName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function createBootstrapToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $studio = $this->resolveAuthorizedStudio($user, $request->input('studio'));

        return response()->json($this->installer->issueBootstrapPayload($user, $studio));
    }

    public function setupScript(string $token, string $script): Response
    {
        $context = $this->installer->resolveBootstrapContext($token);
        abort_if($context === null, 404, 'Setup link expired or invalid. Generate a new installer link inside Obsisstant.');

        return match ($script) {
            'mac.sh' => response(
                $this->installer->buildMacOneClickScript($token),
                200,
                ['Content-Type' => 'text/x-shellscript; charset=utf-8'],
            ),
            'win.ps1' => response(
                $this->installer->buildWindowsOneClickScript($token),
                200,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            ),
            default => abort(404),
        };
    }

    public function setupBundle(string $token, string $platform): BinaryFileResponse
    {
        $context = $this->installer->resolveBootstrapContext($token);
        abort_if($context === null, 404, 'Setup token expired or invalid. Generate a new installer link inside Obsisstant.');

        $normalized = $this->installer->normalizePlatform($platform);
        $zipPath = $this->installer->buildBundleZip($context['user'], $normalized, $context['studio']);
        $fileName = $normalized === ObsAgentInstallerService::PLATFORM_MAC
            ? 'Obsisstant-OBS-Agent-Mac.zip'
            : 'Obsisstant-OBS-Agent-Windows.zip';

        return response()
            ->download($zipPath, $fileName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function resolveAuthorizedStudio(User $user, mixed $studioSlug): ?ObsStudio
    {
        if (! is_string($studioSlug) || trim($studioSlug) === '') {
            return null;
        }

        $studio = ObsStudio::query()
            ->ownedBy($user)
            ->where('slug', trim($studioSlug))
            ->first();

        abort_if(! $studio instanceof ObsStudio, 403, 'Unauthorized studio scope for installer.');

        return $studio;
    }
}
