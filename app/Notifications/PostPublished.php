<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PostPublished extends Notification
{
    use Queueable;

    public function __construct(private Post $post)
    {
        $this->post->loadMissing([
            'user:id,name',
            'community:id,name,slug',
        ]);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $post = $this->post;
        $author = $post->user;
        $community = $post->community;
        $excerpt = Str::limit(trim(strip_tags((string) $post->content)), 140);

        return [
            'type' => 'post',
            'title' => "{$author?->name} posted in {$community?->name}",
            'body' => $excerpt,
            'url' => url('/dashboard?community=' . $community?->slug),
            'post_id' => $post->id,
            'community_id' => $community?->id,
            'community_slug' => $community?->slug,
            'community_name' => $community?->name,
            'author_id' => $author?->id,
            'author_name' => $author?->name,
        ];
    }
}