<?php

namespace App\Notifications;

use App\Models\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChannelCreated extends Notification
{
    use Queueable;

    public function __construct(private Channel $channel)
    {
        $this->channel->loadMissing('community:id,name,slug', 'community.owner:id');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $channel = $this->channel;
        $community = $channel->community;

        return [
            'type' => 'channel_created',
            'title' => "New channel #{$channel->name}",
            'body' => "A new channel was added in {$community?->name}.",
            'url' => route('messages', [
                'tab' => 'channel',
                'community' => $community?->slug,
                'channel_id' => $channel->id,
            ]),
            'channel_id' => $channel->id,
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
        ];
    }
}