<?php

namespace App\Notifications;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PhotoApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Photo $photo,
        public User $uploader
    ) {
        $this->photo->loadMissing('community:id,name,slug');
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
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "New photo uploaded in {$this->photo->community->name}",
            'body' => "A photo by {$this->uploader->name} has been approved and is now visible in the gallery.",
            'type' => 'photo_approved',
            'url' => url("/photos") . "?community=" . $this->photo->community->slug
        ];
    }
}