<?php

namespace App\Notifications;

use App\Models\Community;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberRemoved extends Notification
{
    use Queueable;

    public function __construct(private Community $community)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'member_removed',
            'title' => "You were removed from {$this->community->name}",
            'body' => 'You no longer have access to this community.',
            'url' => route('dashboard'),
            'community_id' => $this->community->id,
            'community_slug' => $this->community->slug,
            'community_name' => $this->community->name,
        ];
    }
}