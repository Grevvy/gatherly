<?php

namespace App\Http\Controllers;

use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    protected function transformNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? 'notification',
            'title' => $notification->data['title'] ?? null,
            'body' => $notification->data['body'] ?? null,
            'url' => $notification->data['url'] ?? null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at
        ];
    }
    /**
     * Display the notifications center page.
     */
    public function page()
    {
        $paginator = Auth::user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Transform the items in the paginator
        $transformedItems = $paginator->getCollection()->map(function ($notification) {
            return $this->transformNotification($notification);
        });
        
        // Put the transformed items back into the paginator
        $notifications = $paginator->setCollection($transformedItems);

        $user = Auth::user();

        $unreadCount = $user
            ->unreadNotifications()
            ->count();

        $preferenceMemberships = $user->memberships()
            ->where('status', 'active')
            ->with(['community:id,name,slug'])
            ->orderByDesc('role')
            ->orderBy('created_at')
            ->get()
            ->map(function ($membership) {
                $community = $membership->community;

                if (! $community) {
                    return null;
                }

                $prefs = array_merge(
                    CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES,
                    is_array($membership->notification_preferences) ? $membership->notification_preferences : []
                );

                return [
                    'community_id' => $community->id,
                    'community_slug' => $community->slug,
                    'community_name' => $community->name,
                    'role' => $membership->role,
                    'preferences' => [
                        'posts' => (bool) ($prefs['posts'] ?? true),
                        'events' => (bool) ($prefs['events'] ?? true),
                        'photos' => (bool) ($prefs['photos'] ?? true),
                        'memberships' => (bool) ($prefs['memberships'] ?? true),
                    ],
                ];
            })
            ->filter()
            ->values();

        $snoozedUntil = $user->notifications_snoozed_until;

        return view('notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'preferenceMemberships' => $preferenceMemberships,
            'snoozedUntil' => $snoozedUntil,
        ]);
    }

    /**
     * Get notifications for the dropdown menu.
     */
    public function index(Request $request)
    {
        $limit = min((int) $request->input('limit', 15), 50);
        
        $notifications = Auth::user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                return $this->transformNotification($notification);
            });

        $unreadCount = Auth::user()
            ->unreadNotifications()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }

        $unreadCount = Auth::user()
            ->unreadNotifications()
            ->count();

        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Clear all notifications for the user.
     */
    public function clearAll()
    {
        Auth::user()->notifications()->delete();

        return response()->json([
            'message' => 'All notifications cleared',
            'unread_count' => 0,
        ]);
    }
}
