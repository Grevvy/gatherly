<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    public $id;
    public $messageableType;
    public $messageableId;
    public $communityId;

    public function __construct(int $id, string $messageableType, int $messageableId, ?int $communityId = null)
    {
        $this->id = $id;
        $this->messageableType = $messageableType;
        $this->messageableId = $messageableId;
        $this->communityId = $communityId;
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("{$this->messageableType}.{$this->messageableId}")];
        if ($this->communityId) {
            $channels[] = new PrivateChannel("community.{$this->communityId}");
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'messageable_type' => $this->messageableType,
            'messageable_id' => $this->messageableId,
            'community_id' => $this->communityId,
        ];
    }
}
