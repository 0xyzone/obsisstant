<?php

use App\Http\Controllers\AgentInstallerController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ObsMobileDeckController;
use App\Http\Controllers\ObsOverlayController;
use App\Http\Controllers\OxyAssistantController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ScreenCollectionController;
use App\Http\Controllers\StagePlayerController;
use App\Http\Controllers\StudioControlCenterController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::get('/changelog', ChangelogController::class)->name('changelog');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::post('/demo-requests', [PricingController::class, 'storeDemo'])
    ->middleware('throttle:10,1')
    ->name('demo-requests.store');

Route::get('/knowledge-base', KnowledgeBaseController::class)->name('knowledge-base');

Route::post('/oxy/ask', OxyAssistantController::class)
    ->middleware('throttle:45,1')
    ->name('oxy.ask');

// Public token-scoped Browser Source endpoints for OBS Studio
Route::get('/screens/{token}', [ObsOverlayController::class, 'show'])->name('obs.screen.show');
Route::get('/stage/{token}', [StagePlayerController::class, 'show'])->name('obs.collection.show');
Route::get('/stage/{token}/state', [StagePlayerController::class, 'state'])->name('obs.collection.state');
Route::post('/stage/{token}/logic-trigger', [StagePlayerController::class, 'logicTrigger'])
    ->middleware('throttle:30,1')
    ->name('obs.collection.logic-trigger');
Route::get('/stage-assets/{asset}/{filename?}', [StagePlayerController::class, 'serveAsset'])
    ->where('filename', '.*')
    ->name('obs.screen-asset.show');

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
    Route::post('/studio/{studio:slug}/agent/stop', [StudioControlCenterController::class, 'stopAgent'])->name('studio.agent.stop');

    // Unified Single-Browser Screen Collections, Views Builder & Remote Controls
    Route::post('/studio/{studio:slug}/control-mode', [ScreenCollectionController::class, 'updateControlMode'])->name('studio.control-mode');
    Route::get('/studio/{studio:slug}/collections', [ScreenCollectionController::class, 'index'])->name('studio.collections.index');
    Route::post('/studio/{studio:slug}/collections', [ScreenCollectionController::class, 'store'])->name('studio.collections.store');
    Route::get('/studio/{studio:slug}/collections/{collection}/builder', [ScreenCollectionController::class, 'builder'])->name('studio.collections.builder');
    Route::get('/studio/{studio:slug}/collections/{collection}/state', [ScreenCollectionController::class, 'state'])->name('studio.collections.state');
    Route::put('/studio/{studio:slug}/collections/{collection}', [ScreenCollectionController::class, 'update'])->name('studio.collections.update');
    Route::delete('/studio/{studio:slug}/collections/{collection}', [ScreenCollectionController::class, 'destroy'])->name('studio.collections.destroy');
    Route::post('/studio/{studio:slug}/collections/{collection}/switch', [ScreenCollectionController::class, 'switchView'])->name('studio.collections.switch');
    Route::post('/studio/{studio:slug}/collections/{collection}/runtime', [ScreenCollectionController::class, 'updateRuntime'])->name('studio.collections.runtime');
    Route::put('/studio/{studio:slug}/collections/{collection}/deck-layout', [ScreenCollectionController::class, 'updateDeckLayout'])->name('studio.collections.deck-layout');
    Route::post('/studio/{studio:slug}/collections/{collection}/push', [ScreenCollectionController::class, 'pushToObs'])->name('studio.collections.push');
    Route::post('/studio/{studio:slug}/collections/{collection}/views', [ScreenCollectionController::class, 'storeView'])->name('studio.collections.views.store');
    Route::post('/studio/{studio:slug}/collections/{collection}/views/reorder', [ScreenCollectionController::class, 'reorderViews'])->name('studio.collections.views.reorder');
    Route::put('/studio/{studio:slug}/collections/{collection}/views/{view}', [ScreenCollectionController::class, 'updateView'])->name('studio.collections.views.update');
    Route::post('/studio/{studio:slug}/collections/{collection}/views/{view}/duplicate', [ScreenCollectionController::class, 'duplicateView'])->name('studio.collections.views.duplicate');
    Route::delete('/studio/{studio:slug}/collections/{collection}/views/{view}', [ScreenCollectionController::class, 'destroyView'])->name('studio.collections.views.destroy');
    Route::post('/studio/{studio:slug}/collections/{collection}/assets', [ScreenCollectionController::class, 'storeAsset'])->name('studio.collections.assets.store');
    Route::post('/studio/{studio:slug}/collections/{collection}/assets/cleanup', [ScreenCollectionController::class, 'cleanupAssets'])->name('studio.collections.assets.cleanup');
    Route::delete('/studio/{studio:slug}/collections/assets/{asset}', [ScreenCollectionController::class, 'destroyAsset'])->name('studio.collections.assets.destroy');

    Route::get('/deck', [ObsMobileDeckController::class, 'index'])->name('obs.deck.index');
    Route::get('/deck/{studio:slug}', [ObsMobileDeckController::class, 'show'])->name('obs.deck.show');
    Route::post('/deck/{studio:slug}/command', [ObsMobileDeckController::class, 'dispatchCommand'])->name('obs.deck.command');
    Route::post('/deck/{studio:slug}/macro/{macro}', [ObsMobileDeckController::class, 'runMacro'])->name('obs.deck.macro');
    Route::post('/deck/screens/{screen}/live', [ObsMobileDeckController::class, 'updateScreenLive'])->name('obs.deck.screen-live');
});
