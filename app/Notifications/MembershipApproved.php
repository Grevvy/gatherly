<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MembershipApproved extends Notification
{
    use Queueable;

    public function __construct(private CommunityMembership $membership)
    {
        $this->membership->loadMissing([
            'community:id,name,slug',
            'user:id,name,avatar',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $community = $this->membership->community;

        return [
            'type' => 'membership_approved',
            'title' => "You're in! {$community?->name} approved your request",
            'body' => 'Jump in and start connecting with members.',
            'url' => route('dashboard', ['community' => $community?->slug]),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'membership_id' => $this->membership->id,
        ];
    }
}