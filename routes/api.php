<?php

use App\Http\Controllers\Api\ObsAgentController;
use App\Http\Middleware\AuthenticateObsAgent;
use Illuminate\Support\Facades\Route;

Route::prefix('obs-agent')
    ->middleware(AuthenticateObsAgent::class)
    ->group(function (): void {
        Route::get('/bootstrap', [ObsAgentController::class, 'bootstrap'])->name('api.obs-agent.bootstrap');
        Route::post('/broadcasting/auth', [ObsAgentController::class, 'authenticateBroadcast'])->name('api.obs-agent.broadcast-auth');
        Route::post('/state', [ObsAgentController::class, 'updateState'])->name('api.obs-agent.state');
        Route::post('/preview-frame', [ObsAgentController::class, 'uploadPreviewFrame'])->name('api.obs-agent.preview-frame');
        Route::post('/heartbeat', [ObsAgentController::class, 'heartbeat'])->name('api.obs-agent.heartbeat');
        Route::post('/commands/{commandUuid}/ack', [ObsAgentController::class, 'acknowledgeCommand'])->name('api.obs-agent.commands.ack');
        Route::get('/media/{token}', [ObsAgentController::class, 'downloadMediaFile'])->name('api.obs-agent.media.download');
    });
