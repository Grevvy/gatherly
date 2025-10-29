<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberBanned extends Notification
{
    use Queueable;

    public function __construct(private CommunityMembership $membership)
    {
        $this->membership->loadMissing('community:id,name,slug', 'user:id,name');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $community = $this->membership->community;

        return [
            'type' => 'member_banned',
            'title' => "Access revoked in {$community?->name}",
            'body' => 'Your membership status is now banned.',
            'url' => route('dashboard'),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
        ];
    }
}