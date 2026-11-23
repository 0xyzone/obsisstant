<?php

use App\Http\Controllers\ChangelogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/changelog', ChangelogController::class)->name('changelog');
