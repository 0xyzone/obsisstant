<?php

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

// Authenticated mobile-first wireless Stream Deck UI & full Studio Control Center
Route::middleware('auth')->group(function (): void {
    Route::get('/studio', [StudioControlCenterController::class, 'index'])->name('studio.index');
    Route::get('/studio/{studio:slug}', [StudioControlCenterController::class, 'show'])->name('studio.show');
    Route::post('/studio/{studio:slug}/command', [StudioControlCenterController::class, 'dispatchCommand'])->name('studio.command');
    Route::get('/studio/{studio:slug}/commands/{commandUuid}', [StudioControlCenterController::class, 'commandResult'])->name('studio.command-result');
    Route::post('/studio/{studio:slug}/macro/{macro}', [StudioControlCenterController::class, 'runMacro'])->name('studio.macro');
    Route::post('/studio/{studio:slug}/screens/{screen}/live', [StudioControlCenterController::class, 'updateScreenLive'])->name('studio.screen-live');
    Route::post('/studio/{studio:slug}/screens/{screen}/push', [StudioControlCenterController::class, 'pushScreenToObs'])->name('studio.screen-push');
    Route::post('/studio/{studio:slug}/preview', [StudioControlCenterController::class, 'togglePreview'])->name('studio.preview-toggle');
    Route::get('/studio/{studio:slug}/preview-frame', [StudioControlCenterController::class, 'previewFrame'])->name('studio.preview-frame');

    Route::get('/deck', [ObsMobileDeckController::class, 'index'])->name('obs.deck.index');
    Route::get('/deck/{studio:slug}', [ObsMobileDeckController::class, 'show'])->name('obs.deck.show');
    Route::post('/deck/{studio:slug}/command', [ObsMobileDeckController::class, 'dispatchCommand'])->name('obs.deck.command');
    Route::post('/deck/{studio:slug}/macro/{macro}', [ObsMobileDeckController::class, 'runMacro'])->name('obs.deck.macro');
    Route::post('/deck/screens/{screen}/live', [ObsMobileDeckController::class, 'updateScreenLive'])->name('obs.deck.screen-live');
});
