<?php

namespace App\Notifications;

use App\Models\CommunityMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommunityInvitation extends Notification
{
    use Queueable;

    public function __construct(private CommunityMembership $membership)
    {
        $this->membership->loadMissing([
            'user:id,name,email',
            'community:id,name,slug,description,banner_image',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        $membership = $this->membership;
        $community = $membership->community;

        return [
            'type' => 'community_invitation',
            'title' => "You're invited to join {$community?->name}",
            'body' => 'Accept the invitation to become a member and start connecting.',
            'url' => route('invitation.handle', ['membershipId' => $membership->id]),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'membership_id' => $membership->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $membership = $this->membership;
        $community = $membership->community;

        return (new MailMessage)
            ->subject("You're invited to join {$community?->name}")
            ->view('emails.community-invitation', [
                'membership' => $membership,
                'community' => $community,
                'user' => $notifiable,
                'acceptUrl' => route('invitation.accept', ['membershipId' => $membership->id]),
                'declineUrl' => route('invitation.decline', ['membershipId' => $membership->id]),
            ]);
    }
}