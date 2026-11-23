<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsPreviewFrameUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $studioId,
        public string $sceneName,
        public string $frameVersion,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("obs.studio.{$this->studioId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.preview.frame';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'studio_id' => $this->studioId,
            'scene_name' => $this->sceneName,
            'version' => $this->frameVersion,
        ];
    }
}
