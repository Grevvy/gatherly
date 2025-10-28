<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventRsvpUpdated extends Notification
{
    use Queueable;

    public function __construct(
        private Event $event,
        private User $attendee,
        private string $status,
        private array $context = []
    ) {
        $this->event->loadMissing('community:id,name,slug', 'owner:id,name');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $statusLabel = match ($this->status) {
            'accepted' => 'is attending',
            'waitlist' => 'joined the waitlist for',
            'declined' => 'cannot attend',
            default => 'updated their RSVP for',
        };

        $title = "{$this->attendee->name} {$statusLabel} your event";
        
        $body = $this->event->title;
        if ($this->status === 'waitlist' && isset($this->context['waitlist_position'])) {
            $body .= " (Waitlist position: #{$this->context['waitlist_position']})";
        }

        return [
            'type' => 'event_rsvp',
            'title' => $title,
            'body' => $body,
            'url' => url("/events/{$this->event->id}/details")
        ];
    }
}