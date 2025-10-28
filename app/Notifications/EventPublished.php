<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class EventPublished extends Notification
{
    use Queueable;

    public function __construct(private Event $event)
    {
        $this->event->loadMissing([
            'community:id,name,slug',
            'owner:id,name',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $event = $this->event;
        $community = $event->community;
        $owner = $event->owner;
        $excerpt = Str::limit(trim(strip_tags((string) $event->description)), 140);

        return [
            'type' => 'event',
            'title' => "{$owner?->name} scheduled a new event",
            'body' => $excerpt ?: $event->title,
            'url' => $community
                ? url('/events?community=' . $community->slug)
                : url('/events'),
            'event_id' => $event->id,
            'event_title' => $event->title,
            'starts_at' => optional($event->starts_at)?->toIso8601String(),
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'owner_id' => $owner?->id,
            'owner_name' => $owner?->name,
        ];
    }
}