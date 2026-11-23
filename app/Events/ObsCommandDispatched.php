<?php

namespace App\Events;

use App\Models\ObsCommandLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsCommandDispatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ObsCommandLog $commandLog) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("obs.studio.{$this->commandLog->obs_studio_id}.commands"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.command.dispatched';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'command_uuid' => $this->commandLog->command_uuid,
            'studio_id' => $this->commandLog->obs_studio_id,
            'request_type' => $this->commandLog->request_type,
            'request_data' => $this->commandLog->request_data ?? [],
            'dispatched_at' => now()->toIso8601String(),
        ];
    }
}
