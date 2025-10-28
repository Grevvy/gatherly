<?php

namespace App\Notifications;

use App\Models\Channel;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class MessageReceived extends Notification
{
    use Queueable;

    public function __construct(private Message $message)
    {
        $this->message->loadMissing([
            'user:id,name',
            'messageable',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $message = $this->message;
        $sender = $message->user;
        $preview = Str::limit(trim((string) $message->body), 140);
        $payload = [
            'type' => 'message',
            'message_id' => $message->id,
            'sender_id' => $sender?->id,
            'sender_name' => $sender?->name,
            'body' => $preview,
        ];

        $target = $message->messageable;

        if ($target instanceof Channel) {
            $target->loadMissing('community:id,name,slug');

            $payload['title'] = "New message in #{$target->name}";
            $payload['url'] = route('messages', [
                'tab' => 'channel',
                'community' => $target->community?->slug,
                'channel_id' => $target->id,
            ]);
            $payload['channel_id'] = $target->id;
            $payload['community_id'] = $target->community?->id;
            $payload['community_slug'] = $target->community?->slug;
            $payload['community_name'] = $target->community?->name;
        } elseif ($target instanceof MessageThread) {
            $target->loadMissing('community:id,name,slug');

            $payload['title'] = "{$sender?->name} sent a direct message";
            $payload['url'] = route('messages', [
                'tab' => 'direct',
                'community' => $target->community?->slug,
                'thread_id' => $target->id,
            ]);
            $payload['thread_id'] = $target->id;
            $payload['community_id'] = $target->community?->id;
            $payload['community_slug'] = $target->community?->slug;
            $payload['community_name'] = $target->community?->name;
        } else {
            $payload['title'] = 'New message received';
            $payload['url'] = route('messages');
        }

        return $payload;
    }
}