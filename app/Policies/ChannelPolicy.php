<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\Community;
use App\Models\User;

class ChannelPolicy
{
    public function create(User $user, Community $community): bool
    {
        return $community->members->contains($user);
    }

    public function view(User $user, Channel $channel): bool
    {
        return $channel->community->members->contains($user);
    }

    public function update(User $user, Channel $channel): bool
    {
        return $channel->community->owner->is($user);
    }

    public function delete(User $user, Channel $channel): bool
    {
        return $channel->community->owner->is($user);
    }
}