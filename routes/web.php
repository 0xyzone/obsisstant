<?php

use App\Livewire\GroupScreen;
use App\Livewire\TeamArooster;
use App\Livewire\TeamBrooster;
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

require __DIR__ . '/auth.php';
