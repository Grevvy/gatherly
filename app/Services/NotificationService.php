<?php

namespace App\Services;

use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\MessageThread;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyCommunityMembers(
        Community $community,
        BaseNotification $notification,
        ?int $excludeUserId = null,
        ?string $category = null
    ): void {
        $recipients = $community->members()
            ->wherePivot('status', 'active')
            ->when($excludeUserId, fn($query) => $query->where('users.id', '!=', $excludeUserId))
            ->get();

        if ($community->owner && (! $excludeUserId || $community->owner_id !== $excludeUserId)) {
            $recipients->push($community->owner);
        }

        $collection = $recipients->unique('id');

        if ($category) {
            $collection = $collection->filter(fn ($user) => $this->allowsCategory($user, $category));
        }

        $this->dispatch($collection, $notification);
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
        ?int $excludeUserId = null,
        ?string $category = null
    ): void {
        $recipients = $community->members()
            ->wherePivot('status', 'active')
            ->whereIn('community_memberships.role', ['owner', 'admin', 'moderator'])
            ->when($excludeUserId, fn($query) => $query->where('users.id', '!=', $excludeUserId))
            ->get();

        if ($community->owner && (! $excludeUserId || $community->owner_id !== $excludeUserId)) {
            $recipients->push($community->owner);
        }

        $collection = $recipients->unique('id');

        if ($category) {
            $collection = $collection->filter(fn ($user) => $this->allowsCategory($user, $category));
        }

        $this->dispatch($collection, $notification);
    }

    public function dispatch(iterable $recipients, BaseNotification $notification): void
    {
        $collection = $recipients instanceof Collection
            ? $recipients
            : collect($recipients);

        $unique = $collection->unique('id')->filter(function ($user) {
            $until = $user->notifications_snoozed_until ?? null;

            if ($until instanceof \Illuminate\Support\Carbon) {
                return ! $until->isFuture();
            }

            return empty($until);
        });

        if ($unique->isEmpty()) {
            return;
        }

        Notification::send($unique, $notification);
    }

    private function allowsCategory($user, string $category): bool
    {
        $prefs = $user->pivot?->notification_preferences ?? [];
        $allowed = array_merge(
            CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES,
            is_array($prefs) ? $prefs : []
        );

        return $allowed[$category] ?? true;
    }
}
