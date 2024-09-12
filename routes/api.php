<?php

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('tournament')->group(function () {
    Route::get('/{id}', [ApiController::class, 'index']);
});

Route::prefix('match')->group(function () {
    Route::get('/', [ApiController::class, 'matchTeams']);
});
