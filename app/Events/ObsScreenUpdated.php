<?php

namespace App\Events;

use App\Models\ObsScreen;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObsScreenUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ObsScreen $screen) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("obs.screen.{$this->screen->public_token}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'obs.screen.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->screen->id,
            'public_token' => $this->screen->public_token,
            'template_type' => $this->screen->template_type,
            'is_live' => $this->screen->is_live,
            'theme' => $this->screen->getMergedTheme(),
            'state' => $this->screen->getMergedState(),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
