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

Route::get('/phpinfo', function () {
    return phpinfo();
});

require __DIR__ . '/auth.php';