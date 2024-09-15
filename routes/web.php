<?php

use App\Livewire\GroupScreen;
use App\Livewire\TeamArooster;
use App\Livewire\TeamBrooster;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObsController;

Route::view('/', 'welcome');

Route::view('/demo','control');

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

Route::get('/capture', function () {
    Browsershot::url('http://obsisstant.local/group')  // Change this to your route or view
        ->setDelay(2000)  // Add delay if you have animations or dynamic content
        ->save('output.jpg');  // Path where the image will be saved

    return response()->download('output.png');
});

require __DIR__ . '/auth.php';
