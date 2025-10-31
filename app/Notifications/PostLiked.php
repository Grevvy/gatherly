<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostLiked extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post,
        public User $liker
    ) {
        $this->post->loadMissing([
            'community:id,name,slug',
            'user:id,name'
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'post_liked',
            'title' => "{$this->liker->name} liked your post",
            'body' => $this->truncateContent($this->post->content),
            'url' => url('/dashboard?community=' . $this->post->community->slug),
            'post_id' => $this->post->id,
            'community_id' => $this->post->community->id,
            'community_slug' => $this->post->community->slug,
            'community_name' => $this->post->community->name,
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name
        ];
    }

    /**
     * Truncate the content for the notification preview
     */
    private function truncateContent(string $content): string
    {
        if (strlen($content) <= 100) {
            return $content;
        }
        return substr($content, 0, 97) . '...';
    }
}