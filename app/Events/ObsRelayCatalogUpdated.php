<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsRelayCatalogUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $reason = 'updated',
        public ?int $studioId = null,
        public ?int $userId = null,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('obs.relay'),
        ];

        if ($this->userId !== null) {
            $channels[] = new PrivateChannel("obs.user.{$this->userId}.relay");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'obs.relay.catalog-updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'reason' => $this->reason,
            'studio_id' => $this->studioId,
        ];
    }
}
