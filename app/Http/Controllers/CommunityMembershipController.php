<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMembership;
use App\Notifications\MemberBanned;
use App\Notifications\MemberJoined;
use App\Notifications\MemberLeft;
use App\Notifications\MemberRemoved;
use App\Notifications\MembershipApproved;
use App\Notifications\MembershipRoleChanged;
use App\Notifications\MembershipRequested;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CommunityMembershipController extends Controller
{
    public function showMembers(Request $request)
    {
        $slug = $request->query('community');
        $community = $slug
            ? Community::with(['owner', 'memberships.user'])
                ->where('slug', $slug)
                ->firstOrFail()
            : abort(404, 'Community not found');

        // load communities the current user belongs to (for sidebar)
        $communities = Auth::check()
            ? Community::whereHas('memberships', fn($q) => $q->where('user_id', Auth::id()))->get()
            : collect();

        return view('members', [
            'community' => $community,
            'communities' => $communities
        ]);
    }

    public function index(Community $community, Request $request)
    {
        $status = $request->get('status');

        $members = $community->memberships()
            ->with('user:id,name,email')
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END")
            ->paginate(20);

        return response()->json($members);
    }

    public function join(Community $community)
    {
        $uid = Auth::id();

        $existing = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $uid)->first();

        if ($existing) {
            return response()->json($existing, 200);
        }

        $status = match ($community->join_policy) {
            'open'   => 'active',
            'request'=> 'pending',
            'invite' => 'pending',
            default  => 'pending',
        };

        $membership = CommunityMembership::create([
            'community_id' => $community->id,
            'user_id'      => $uid,
            'role'         => 'member',
            'status'       => $status,
            'notification_preferences' => CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES,
        ]);

        $membership->loadMissing('user:id,name,avatar', 'community:id,name,slug');
        $notifier = app(NotificationService::class);

        if ($status === 'pending') {
            $notifier->notifyCommunityModerators(
                $community,
                new MembershipRequested($membership),
                $uid,
                'memberships'
            );
        } else {
            $notifier->notifyCommunityModerators(
                $community,
                new MemberJoined($membership),
                $uid,
                'memberships'
            );
        }

        return response()->json($membership, 201);
    }

    public function leave(Community $community)
    {
        $uid = auth()->id();
        abort_if($community->owner_id === $uid, 400, 'Owner must transfer ownership before leaving');

        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $uid)
            ->delete();

        $user = Auth::user();
        app(NotificationService::class)->notifyCommunityModerators(
            $community,
            new MemberLeft($community, $user),
            $uid,
            'memberships'
        );

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Left community']);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Successfully left ' . $community->name);
    }

    public function approve(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        $m = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $m->update(['status' => 'active']);
        $m->loadMissing('user:id,name,avatar', 'community:id,name,slug');

        app(NotificationService::class)->notifyCommunityModerators(
            $community,
            new MemberJoined($m),
            $m->user_id,
            'memberships'
        );

        if ($m->user) {
            $m->user->notify(new MembershipApproved($m));
        }
        
        if ($request->wantsJson()) {
            return response()->json($m);
        }
        
        return redirect()->route('members', ['community' => $community->slug])
            ->with('success', 'Member approved successfully');
    }

    public function reject(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Request rejected']);
        }
        
        return redirect()->route('members', ['community' => $community->slug])
            ->with('success', 'Member request rejected');
    }

    public function invite(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);

        $membership = CommunityMembership::firstOrCreate(
            ['community_id' => $community->id, 'user_id' => $data['user_id']],
            ['role' => 'member', 'status' => 'pending']
        );

        if ($membership->wasRecentlyCreated && empty($membership->notification_preferences)) {
            $membership->notification_preferences = CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES;
            $membership->save();
        }

        return response()->json($membership, 201);
    }

    public function setRole(Request $request, Community $community)
    {
        $this->authorizeOwner($community);

        $data = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'role'    => ['required','in:owner,admin,moderator,member'],
        ]);

        $changes = DB::transaction(function () use ($community, $data) {
            $changes = [];

            $membership = CommunityMembership::where('community_id', $community->id)
                ->where('user_id', $data['user_id'])
                ->where('status', 'active')
                ->firstOrFail();

            $oldRole = $membership->role;

            if ($data['role'] === 'owner' && $membership->user_id !== $community->owner_id) {
                $previousOwnerId = $community->owner_id;
                $community->update(['owner_id' => $membership->user_id]);

                $demoted = CommunityMembership::updateOrCreate(
                    ['community_id' => $community->id, 'user_id' => $previousOwnerId],
                    ['role' => 'admin', 'status' => 'active']
                );
                if ($demoted->wasRecentlyCreated && empty($demoted->notification_preferences)) {
                    $demoted->notification_preferences = CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES;
                    $demoted->save();
                }

                $membership->update(['role' => 'owner']);
                $changes[] = ['membership' => $membership->fresh(['user', 'community']), 'old' => $oldRole];

                if ($demoted && $demoted->user_id !== $membership->user_id) {
                    $changes[] = ['membership' => $demoted->fresh(['user', 'community']), 'old' => 'owner'];
                }
            } else {
                $membership->update(['role' => $data['role']]);
                $membership->load('user', 'community');
                if ($oldRole !== $membership->role) {
                    $changes[] = ['membership' => $membership, 'old' => $oldRole];
                }
            }

            return $changes;
        });

        foreach ($changes as $change) {
            $memberModel = $change['membership'];
            $oldRole = $change['old'] ?? null;

            if ($memberModel?->user) {
                $memberModel->user->notify(new MembershipRoleChanged($memberModel, $oldRole));
            }
        }

        $updatedMembership = $changes[0]['membership'] ?? null;

        return response()->json($updatedMembership);
    }

    public function ban(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        $m = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $m->update(['status' => 'banned', 'role' => 'member']);
        $m->loadMissing('user:id,name', 'community:id,name,slug');

        if ($m->user) {
            $m->user->notify(new MemberBanned($m));
        }

        return response()->json($m);
    }

    public function remove(Community $community, int $userId)
    {
        $this->authorizeModerator($community);

        abort_if($community->owner_id === $userId, 400, 'Cannot remove owner');

        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $userId)
            ->delete();

        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->notify(new MemberRemoved($community));
        }

        app(NotificationService::class)->notifyCommunityModerators(
            $community,
            new MemberLeft($community, $user),
            null,
            'memberships'
        );

        return response()->json(['message' => 'Member removed']);
    }

    // ---- Inline guards ----
    private function authorizeModerator(Community $community): void
    {
        $uid = Auth::id();
        $isMod = $community->owner_id === $uid
            || $community->memberships()
                ->where('user_id', $uid)
                ->whereIn('role', ['owner','admin','moderator'])
                ->where('status', 'active')
                ->exists();

        abort_unless($isMod, 403, 'Forbidden');
    }

    private function authorizeOwner(Community $community): void
    {
        abort_unless($community->owner_id === Auth::id(), 403, 'Owner only');
    }
}
