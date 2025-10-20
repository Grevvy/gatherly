<?php

namespace App\Policies;

use App\Models\MessageThread;
use App\Models\Community;
use App\Models\User;

class MessageThreadPolicy
{
    public function create(User $user, Community $community): bool
    {
        return $community->members->contains($user);
    }

    public function view(User $user, MessageThread $thread): bool
    {
        return $thread->participants->contains($user);
    }

    public function delete(User $user, MessageThread $thread): bool
    {
        return $thread->participants->contains($user) &&
               $thread->messages()->count() === 0;
    }
}