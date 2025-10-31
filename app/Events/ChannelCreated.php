<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChannelCreated implements ShouldBroadcastNow
{
    public $channelModel;

    public function __construct(\App\Models\Channel $channel)
    {
        $this->channelModel = $channel->load('community');
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("community.{$this->channelModel->community_id}")];
        $channels[] = new PrivateChannel("channel.{$this->channelModel->id}");
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->channelModel->id,
            'community_id' => $this->channelModel->community_id,
            'name' => $this->channelModel->name ?? null,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChannelCreated';
    }
}
