<?php

namespace App\Events;

use App\Models\ObsStudio;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsStudioStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ObsStudio $studio) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("obs.studio.{$this->studio->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.studio.state';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'studio_id' => $this->studio->id,
            'slug' => $this->studio->slug,
            'status' => $this->studio->status,
            'is_online' => $this->studio->isOnline(),
            'last_seen_at' => $this->studio->last_seen_at?->toIso8601String(),
            'live_state' => $this->studio->live_state ?? [],
        ];
    }
}
