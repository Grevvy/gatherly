<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use App\Models\Channel;
use App\Models\MessageThread;

Broadcast::channel('channel.{id}', function ($user, $id) {
    // Log auth attempts for debugging broadcasting issues
    try {
        Log::info('Broadcast auth attempt', ['channel' => "channel.$id", 'user_id' => $user?->id]);
    } catch (\Throwable $e) {
        // don't let logging break authorization
    }

    $channel = \App\Models\Channel::find($id);
    return $channel && $channel->community->members->contains($user);
});

Broadcast::channel('messagethread.{id}', function ($user, $id) {
    try {
        Log::info('Broadcast auth attempt', ['channel' => "messagethread.$id", 'user_id' => $user?->id]);
    } catch (\Throwable $e) {
    }

    $thread = \App\Models\MessageThread::find($id);
    return $thread && $thread->participants->contains($user);
});

Broadcast::channel('community.{id}', function ($user, $id) {
    try {
        Log::info('Broadcast auth attempt', ['channel' => "community.$id", 'user_id' => $user?->id]);
    } catch (\Throwable $e) {
    }

    $community = \App\Models\Community::find($id);
    return $community && $community->members->contains($user);
});


