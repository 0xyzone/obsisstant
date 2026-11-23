<?php

use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\ObsMobileDeckController;
use App\Http\Controllers\ObsOverlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/changelog', ChangelogController::class)->name('changelog');

// Public token-scoped Browser Source overlay endpoint for OBS Studio
Route::get('/screens/{token}', [ObsOverlayController::class, 'show'])->name('obs.screen.show');

// Authenticated mobile-first wireless Stream Deck UI
Route::middleware('auth')->group(function (): void {
    Route::get('/deck', [ObsMobileDeckController::class, 'index'])->name('obs.deck.index');
    Route::get('/deck/{studio:slug}', [ObsMobileDeckController::class, 'show'])->name('obs.deck.show');
    Route::post('/deck/{studio:slug}/command', [ObsMobileDeckController::class, 'dispatchCommand'])->name('obs.deck.command');
    Route::post('/deck/{studio:slug}/macro/{macro}', [ObsMobileDeckController::class, 'runMacro'])->name('obs.deck.macro');
    Route::post('/deck/screens/{screen}/live', [ObsMobileDeckController::class, 'updateScreenLive'])->name('obs.deck.screen-live');
});
