<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebSocketService;

class ObsController extends Controller
{
    protected $webSocketService;

    public function __construct(WebSocketService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    public function startStreaming()
    {
        $this->webSocketService->sendCommand('StartStreaming');
        return response()->json(['status' => 'Streaming started']);
    }

    public function stopStreaming()
    {
        $this->webSocketService->sendCommand('StopStreaming');
        return response()->json(['status' => 'Streaming stopped']);
    }
}
