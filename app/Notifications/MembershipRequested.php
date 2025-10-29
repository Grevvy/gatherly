<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MembershipRequested extends Notification
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
        $applicant = $membership->user;

        return [
            'type' => 'membership_request',
            'title' => "{$applicant?->name} requested to join {$community?->name}",
            'body' => 'Review the pending request in the members panel.',
            'url' => route('members', ['community' => $community?->slug, 'status' => 'pending']),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'membership_id' => $membership->id,
            'applicant_id' => $applicant?->id,
            'applicant_name' => $applicant?->name,
            'applicant_avatar' => $applicant?->avatar ? asset('storage/' . $applicant->avatar) : null,
        ];
    }
}