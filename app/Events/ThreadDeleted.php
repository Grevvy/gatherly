<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ThreadDeleted implements ShouldBroadcastNow
{
    public $id;
    public $communityId;

    // Match controller usage: (communityId, threadId)
    public function __construct(int $communityId, int $id)
    {
        $this->id = $id;
        $this->communityId = $communityId;
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("community.{$this->communityId}")];
        // Also include the specific thread room for any listeners there
        $channels[] = new PrivateChannel("messagethread.{$this->id}");
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'community_id' => $this->communityId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ThreadDeleted';
    }
}
