<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferenceController extends Controller
{
    public function updateCommunity(Request $request, Community $community)
    {
        $user = Auth::user();

        $membership = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $data = $request->validate([
            'posts' => ['sometimes', 'boolean'],
            'events' => ['sometimes', 'boolean'],
            'photos' => ['sometimes', 'boolean'],
            'memberships' => ['sometimes', 'boolean'],
        ]);

        if (empty($data)) {
            return response()->json(['message' => 'No preferences provided'], 422);
        }

        $prefs = array_merge(
            CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES,
            is_array($membership->notification_preferences) ? $membership->notification_preferences : []
        );

        foreach ($data as $key => $value) {
            $prefs[$key] = (bool) $value;
        }

        $membership->notification_preferences = $prefs;
        $membership->save();

        return response()->json([
            'message' => 'Preferences updated',
            'preferences' => $prefs,
        ]);
    }

    public function toggleSnooze(Request $request)
    {
        $data = $request->validate([
            'state' => ['required', 'in:on,off'],
        ]);

        $user = Auth::user();

        if ($data['state'] === 'on') {
            $user->notifications_snoozed_until = now()->addDay();
        } else {
            $user->notifications_snoozed_until = null;
        }

        $user->save();

        return response()->json([
            'message' => $data['state'] === 'on'
                ? 'Notifications snoozed for 24 hours'
                : 'Notifications resumed',
            'snoozed_until' => optional($user->notifications_snoozed_until)?->toIso8601String(),
        ]);
    }
}
