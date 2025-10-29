<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MembershipRoleChanged extends Notification
{
    use Queueable;

    public function __construct(private CommunityMembership $membership, private string $oldRole)
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
        $newRole = $this->membership->role;
        $oldRole = $this->oldRole;

        $title = match ($newRole) {
            'owner' => "You're now the owner of {$community?->name}",
            'admin' => "You're now an admin in {$community?->name}",
            'moderator' => "You're now a moderator in {$community?->name}",
            default => "Your role changed in {$community?->name}",
        };

        $body = "Previous role: " . ucfirst($oldRole ?? 'member') . '. Current role: ' . ucfirst($newRole ?? 'member') . '.';

        return [
            'type' => 'membership_role_changed',
            'title' => $title,
            'body' => $body,
            'url' => route('dashboard', ['community' => $community?->slug]),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'role_old' => $oldRole,
            'role_new' => $newRole,
        ];
    }
}