<?php

namespace App\Services;

use App\Models\Community;
use App\Models\MessageThread;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyCommunityMembers(
        Community $community,
        BaseNotification $notification,
        ?int $excludeUserId = null
    ): void {
        $recipients = $community->members()
            ->wherePivot('status', 'active')
            ->when($excludeUserId, fn($query) => $query->where('users.id', '!=', $excludeUserId))
            ->get();

        if ($community->owner && (! $excludeUserId || $community->owner_id !== $excludeUserId)) {
            $recipients->push($community->owner);
        }

        $this->dispatch($recipients, $notification);
    }

    public function notifyThreadParticipants(
        MessageThread $thread,
        BaseNotification $notification,
        ?int $excludeUserId = null
    ): void {
        $recipients = $thread->participants()
            ->when($excludeUserId, fn($query) => $query->where('users.id', '!=', $excludeUserId))
            ->get();

        $this->dispatch($recipients, $notification);
    }

    public function notifyCommunityModerators(
        Community $community,
        BaseNotification $notification,
        ?int $excludeUserId = null
    ): void {
        $recipients = $community->members()
            ->wherePivot('status', 'active')
            ->whereIn('community_memberships.role', ['owner', 'admin', 'moderator'])
            ->when($excludeUserId, fn($query) => $query->where('users.id', '!=', $excludeUserId))
            ->get();

        if ($community->owner && (! $excludeUserId || $community->owner_id !== $excludeUserId)) {
            $recipients->push($community->owner);
        }

        $this->dispatch($recipients, $notification);
    }

    public function dispatch(iterable $recipients, BaseNotification $notification): void
    {
        $collection = $recipients instanceof Collection
            ? $recipients
            : collect($recipients);

        $unique = $collection->unique('id')->filter();

        if ($unique->isEmpty()) {
            return;
        }

        Notification::send($unique, $notification);
    }
}