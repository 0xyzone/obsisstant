<?php

use App\Http\Controllers\AgentInstallerController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ObsMobileDeckController;
use App\Http\Controllers\ObsOverlayController;
use App\Http\Controllers\StudioControlCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::get('/changelog', ChangelogController::class)->name('changelog');

Route::get('/knowledge-base', KnowledgeBaseController::class)->name('knowledge-base');

// Public token-scoped Browser Source overlay endpoint for OBS Studio
Route::get('/screens/{token}', [ObsOverlayController::class, 'show'])->name('obs.screen.show');

// Short-lived cached bootstrap setup endpoints for one-click Mac/Windows terminal/PowerShell install
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/setup-agent/{token}/{script}', [AgentInstallerController::class, 'setupScript'])
        ->where('script', 'mac\.sh|win\.ps1')
        ->name('agent-installer.setup-script');
    Route::get('/setup-agent/{token}/bundle/{platform}.zip', [AgentInstallerController::class, 'setupBundle'])
        ->where('platform', 'mac|windows')
        ->name('agent-installer.setup-bundle');
});

// Authenticated mobile-first wireless Stream Deck UI & full Studio Control Center
Route::middleware('auth')->group(function (): void {
    Route::get('/agent-installer/download/{platform}', [AgentInstallerController::class, 'download'])
        ->where('platform', 'mac|windows')
        ->name('agent-installer.download');
    Route::post('/agent-installer/bootstrap-token', [AgentInstallerController::class, 'createBootstrapToken'])
        ->name('agent-installer.bootstrap-token');

    Route::get('/studio', [StudioControlCenterController::class, 'index'])->name('studio.index');
    Route::get('/studio/{studio:slug}', [StudioControlCenterController::class, 'show'])->name('studio.show');
    Route::get('/studio/{studio:slug}/snapshot', [StudioControlCenterController::class, 'snapshot'])->name('studio.snapshot');
    Route::post('/studio/{studio:slug}/command', [StudioControlCenterController::class, 'dispatchCommand'])->name('studio.command');
    Route::get('/studio/{studio:slug}/commands/{commandUuid}', [StudioControlCenterController::class, 'commandResult'])->name('studio.command-result');
    Route::post('/studio/{studio:slug}/macro/{macro}', [StudioControlCenterController::class, 'runMacro'])->name('studio.macro');
    Route::post('/studio/{studio:slug}/screens/{screen}/live', [StudioControlCenterController::class, 'updateScreenLive'])->name('studio.screen-live');
    Route::post('/studio/{studio:slug}/screens/{screen}/push', [StudioControlCenterController::class, 'pushScreenToObs'])->name('studio.screen-push');
    Route::post('/studio/{studio:slug}/preview', [StudioControlCenterController::class, 'togglePreview'])->name('studio.preview-toggle');
    Route::get('/studio/{studio:slug}/preview-frame', [StudioControlCenterController::class, 'previewFrame'])->name('studio.preview-frame');
    Route::post('/studio/{studio:slug}/media/upload', [StudioControlCenterController::class, 'uploadMedia'])->name('studio.media-upload');
    Route::post('/studio/{studio:slug}/media/storage-path', [StudioControlCenterController::class, 'updateMediaStoragePath'])->name('studio.media-storage-path');

    Route::get('/deck', [ObsMobileDeckController::class, 'index'])->name('obs.deck.index');
    Route::get('/deck/{studio:slug}', [ObsMobileDeckController::class, 'show'])->name('obs.deck.show');
    Route::post('/deck/{studio:slug}/command', [ObsMobileDeckController::class, 'dispatchCommand'])->name('obs.deck.command');
    Route::post('/deck/{studio:slug}/macro/{macro}', [ObsMobileDeckController::class, 'runMacro'])->name('obs.deck.macro');
    Route::post('/deck/screens/{screen}/live', [ObsMobileDeckController::class, 'updateScreenLive'])->name('obs.deck.screen-live');
});
