<?php

namespace App\Events;

use App\Models\ScreenCollection;
use App\Services\Obs\ScreenCollectionService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScreenCollectionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ScreenCollection $collection) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("obs.collection.{$this->collection->public_token}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.collection.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ScreenCollectionService::buildStagePayload($this->collection);
    }
}
