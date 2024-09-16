<?php

use Carbon\Carbon;
use App\Livewire\GroupScreen;
use App\Livewire\TeamArooster;
use App\Livewire\TeamBrooster;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObsController;

Route::view('/', 'welcome');

Route::view('/demo', 'control');

Route::get('/group', GroupScreen::class)->name('groupScreen');
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

Route::prefix('/capture')->group(function () {
    Route::get('/activeGroup', function () {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $path = public_path('images/downloadable/' . $timestamp . '.png');
        Browsershot::url(route('groupScreen'))
            ->waitUntilNetworkIdle()
            ->noSandbox()  // Disable sandbox if permissions are an issue
            ->setDelay(10000)
            ->windowSize(1920, 1080)
            ->fullPage()
            ->hideBackground()
            ->setOption('executablePath', "C:\Program Files\Google\Chrome\Application\chrome.exe")
            ->save($path);  // Path where the image will be saved

        return response()->download($path);

    });

});

require __DIR__ . '/auth.php';