<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberJoined extends Notification
{
    use Queueable;

    public function __construct(private CommunityMembership $membership)
    {
        $this->membership->loadMissing([
            'user:id,name,avatar',
            'community:id,name,slug',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $membership = $this->membership;
        $community = $membership->community;
        $member = $membership->user;

        return [
            'type' => 'member_joined',
            'title' => "{$member?->name} joined {$community?->name}",
            'body' => 'Say hi and welcome them to the community!',
            'url' => route('members', ['community' => $community?->slug, 'status' => 'active']),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'membership_id' => $membership->id,
            'member_id' => $member?->id,
            'member_name' => $member?->name,
            'member_avatar' => $member?->avatar_url,
        ];
    }
}