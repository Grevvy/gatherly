<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use App\Models\Channel;
use App\Models\MessageThread;

class MessagePolicy
{
    public function create(User $user, Message $message, $messageable): bool
    {
        if ($messageable instanceof Channel) {
            return $messageable->community->members->contains($user);
        }
        
        if ($messageable instanceof MessageThread) {
            return $messageable->participants->contains($user);
        }
        
        return false;
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->user->is($user);
    }
}