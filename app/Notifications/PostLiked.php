<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostLiked extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post,
        public User $liker
    ) {}

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
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "{$this->liker->name} liked your post",
            'body' => $this->truncateContent($this->post->content),
            'type' => 'post_liked',
            'url' => url("/dashboard") . "?community=" . $this->post->community->slug
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