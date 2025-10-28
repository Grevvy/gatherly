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
            'accepted' => 'accepted',
            'waitlist' => 'joined the waitlist',
            'declined' => 'declined',
            default => $this->status,
        };

        $title = "{$this->attendee->name} {$statusLabel} for {$this->event->title}";

        $body = match ($this->status) {
            'accepted' => "{$this->attendee->name} is attending.",
            'waitlist' => "{$this->attendee->name} is on the waitlist." . ($this->context['waitlist_position'] ?? null ? " Position {$this->context['waitlist_position']}." : ''),
            'declined' => "{$this->attendee->name} declined the invite.",
            default => "{$this->attendee->name} updated their RSVP.",
        };

        return [
            'type' => 'event_rsvp',
            'title' => $title,
            'body' => $body,
            'url' => route('event.details', $this->event),
        ];
    }
}