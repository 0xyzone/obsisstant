<?php

namespace App\Events;

use App\Models\ObsCommandLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsCommandAcknowledged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ObsCommandLog $commandLog) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("obs.studio.{$this->commandLog->obs_studio_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.command.acknowledged';
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
            'status' => $this->commandLog->status,
            'has_response_data' => $this->commandLog->response_data !== null,
            'error_message' => $this->commandLog->error_message,
            'executed_at' => $this->commandLog->executed_at?->toIso8601String(),
        ];
    }
}
