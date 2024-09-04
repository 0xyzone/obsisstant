<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ObsController;

Route::view('/', 'control');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/start-streaming', [ObsController::class, 'startStreaming']);
Route::get('/stop-streaming', [ObsController::class, 'stopStreaming']);

require __DIR__ . '/auth.php';
