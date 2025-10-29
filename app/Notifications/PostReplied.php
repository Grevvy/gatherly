<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostReplied extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post,
        public Comment $comment,
        public User $commenter
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
        $isAuthor = $notifiable->id === $this->post->user_id;
        
        return [
            'title' => $isAuthor 
                ? "{$this->commenter->name} replied to your post"
                : "{$this->commenter->name} also replied to this post",
            'body' => $this->truncateContent($this->comment->content),
            'type' => 'post_replied',
            'url' => url("/dashboard") . "?community=" . $this->post->community->slug,
            'meta' => [
                'community_slug' => $this->post->community->slug
            ]
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