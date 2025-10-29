<?php

namespace App\Notifications;

use App\Models\Community;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberLeft extends Notification
{
    use Queueable;

    public function __construct(private Community $community, private ?User $user = null)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $user = $this->user;

        return [
            'type' => 'member_left',
            'title' => $user
                ? "{$user->name} left {$this->community->name}"
                : "A member left {$this->community->name}",
            'body' => $user
                ? "{$user->name} is no longer part of this community."
                : 'A member has left the community.',
            'url' => route('members', ['community' => $this->community->slug, 'status' => 'active']),
            'community_id' => $this->community->id,
            'community_slug' => $this->community->slug,
            'community_name' => $this->community->name,
            'member_id' => $user?->id,
            'member_name' => $user?->name,
        ];
    }
}