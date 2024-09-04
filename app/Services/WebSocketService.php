<?php

namespace App\Services;

use Ratchet\Client\Factory;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory as EventLoopFactory;

class WebSocketService
{
    protected $loop;
    protected $factory;

    public function __construct()
    {
        $this->loop = EventLoopFactory::create();
        $this->factory = new Factory($this->loop);
    }

    public function sendCommand($command, $params = [])
    {
        $this->factory('ws://192.168.1.74:4455')
            ->then(function (WebSocket $conn) use ($command, $params) {
                $payload = json_encode([
                    'request-type' => $command,
                    'message-id' => uniqid(),
                ] + $params);

                $conn->send($payload);

                $conn->on('message', function ($msg) use ($conn) {
                    echo "Received: {$msg}\n";
                    $conn->close();
                });

                $conn->on('error', function ($error) use ($conn) {
                    echo "Error: {$error->getMessage()}\n";
                    $conn->close();
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                });
            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });

        $this->loop->run();
    }
}
