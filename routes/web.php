<?php

use Carbon\Carbon;
use App\Livewire\GroupScreen;
use App\Livewire\TeamArooster;
use App\Livewire\TeamBrooster;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObsController;
use App\Http\Controllers\DownloadController;

Route::view('/', 'welcome');

Route::view('/demo', 'control');

Route::get('/group', GroupScreen::class)->name('groupScreen');
Route::get('/groupStatic', [DownloadController::class, 'groupStatic'])->name('groupScreenStatic');
Route::get('/matchStatic', [DownloadController::class, 'Match1080Static'])->name('matchScreenStatic');
Route::get('/teamArooster', TeamArooster::class)->name('teamArooster');
Route::get('/teamBrooster', TeamBrooster::class)->name('teamBrooster');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

// Route::view('profile', 'profile')
//     ->middleware(['auth'])
//     ->name('profile');

Route::get('/start-streaming', [ObsController::class, 'startStreaming']);
Route::get('/stop-streaming', [ObsController::class, 'stopStreaming']);

Route::get('/phpinfo', function () {
    return phpinfo();
});

Route::get('/download-image', [DownloadController::class, 'downloadImage'])->name('download-image');

Route::prefix('/capture')->group(function () {
    Route::get('/activeGroup', [DownloadController::class, 'downloadGroupStatic']);
});

require __DIR__ . '/auth.php';