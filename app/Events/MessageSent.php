<?php

namespace App\Events;

use App\Models\Message; // ✅ Import the Message model
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    public $message;

    public function __construct(Message $message)
    {
        // Load user and parent so we can include community id for
        // sidebar-level broadcasts.
        $this->message = $message->load('user', 'messageable');
    }

    public function broadcastOn(): array
    {
        $type = strtolower(class_basename($this->message->messageable_type));
        $id = $this->message->messageable_id;

        $channels = [new PrivateChannel("{$type}.{$id}")];

        // Also broadcast to the community channel so sidebar previews stay
        // in sync for users not currently subscribed to the private convo.
        $communityId = $this->message->messageable->community_id ?? null;
        if ($communityId) {
            $channels[] = new PrivateChannel("community.{$communityId}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        $type = strtolower(class_basename($this->message->messageable_type));
        $id = $this->message->messageable_id;
        $communityId = $this->message->messageable->community_id ?? null;

        return [
            'id' => $this->message->id,
            'body' => $this->message->body,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar' => $this->message->user->avatar ?? null,
            ],
            'created_at' => $this->message->created_at->toIso8601String(),
            'messageable_type' => $type,
            'messageable_id' => $id,
            'community_id' => $communityId,
        ];
    }
}
