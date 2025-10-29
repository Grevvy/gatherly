<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostReplied extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Post $post,
        public Comment $comment,
        public User $commenter
    ) {
        $this->post->loadMissing([
            'community:id,name,slug',
            'user:id,name'
        ]);
        $this->comment->loadMissing('user:id,name');
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
        $isAuthor = $notifiable->id === $this->post->user_id;
        
        return [
            'type' => 'post_replied',
            'title' => $isAuthor 
                ? "{$this->commenter->name} replied to your post"
                : "{$this->commenter->name} also replied to this post",
            'body' => $this->truncateContent($this->comment->content),
            'url' => url('/dashboard?community=' . $this->post->community->slug),
            'post_id' => $this->post->id,
            'community_id' => $this->post->community->id,
            'community_slug' => $this->post->community->slug,
            'community_name' => $this->post->community->name,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name
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