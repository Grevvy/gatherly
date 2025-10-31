<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostPendingApproval extends Notification
{
    use Queueable;

    public function __construct(private Post $post)
    {
        $this->post->loadMissing('community:id,name,slug', 'user:id,name');
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $community = $this->post->community;
        $author = $this->post->user;

        return [
            'type' => 'post_pending',
            'title' => "New post pending in {$community?->name}",
            'body' => Str::limit(strip_tags((string) $this->post->content), 140) ?: 'A new post is awaiting review.',
            'url' => url('/dashboard?community=' . $community?->slug . '&tab=feed'),
            'post_id' => $this->post->id,
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'author_id' => $author?->id,
            'author_name' => $author?->name,
        ];
    }
}