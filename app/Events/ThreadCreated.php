<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ThreadCreated implements ShouldBroadcastNow
{
    public $thread;

    public function __construct(\App\Models\MessageThread $thread)
    {
        // load relations needed by listeners (community id, participants)
        $this->thread = $thread->load('community');
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("community.{$this->thread->community_id}")];
        $channels[] = new PrivateChannel("messagethread.{$this->thread->id}");
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->thread->id,
            'community_id' => $this->thread->community_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ThreadCreated';
    }
}
