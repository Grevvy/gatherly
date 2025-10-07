<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityMembershipController extends Controller
{
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
        $uid = auth()->id();

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
        ]);

        return response()->json($membership, 201);
    }

    public function leave(Community $community)
    {
        $uid = auth()->id();
        abort_if($community->owner_id === $uid, 400, 'Owner must transfer ownership before leaving');

        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $uid)
            ->delete();

        return response()->json(['message' => 'Left community']);
    }

    public function approve(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        $m = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $m->update(['status' => 'active']);
        return response()->json($m->fresh());
    }

    public function reject(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->delete();

        return response()->json(['message' => 'Request rejected']);
    }

    public function invite(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);

        $membership = CommunityMembership::firstOrCreate(
            ['community_id' => $community->id, 'user_id' => $data['user_id']],
            ['role' => 'member', 'status' => 'pending']
        );

        return response()->json($membership, 201);
    }

    public function setRole(Request $request, Community $community)
    {
        $this->authorizeOwner($community);

        $data = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'role'    => ['required','in:owner,admin,moderator,member'],
        ]);

        return DB::transaction(function () use ($community, $data) {
            $membership = CommunityMembership::where('community_id', $community->id)
                ->where('user_id', $data['user_id'])
                ->where('status', 'active')
                ->firstOrFail();

            if ($data['role'] === 'owner') {
                $oldOwnerId = $community->owner_id;
                $community->update(['owner_id' => $membership->user_id]);

                CommunityMembership::updateOrCreate(
                    ['community_id' => $community->id, 'user_id' => $oldOwnerId],
                    ['role' => 'admin', 'status' => 'active']
                );

                $membership->update(['role' => 'owner']);
            } else {
                $membership->update(['role' => $data['role']]);
            }

            return response()->json($membership->fresh());
        });
    }

    public function ban(Request $request, Community $community)
    {
        $this->authorizeModerator($community);

        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        $m = CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $m->update(['status' => 'banned', 'role' => 'member']);
        return response()->json($m->fresh());
    }

    public function remove(Community $community, int $userId)
    {
        $this->authorizeModerator($community);

        abort_if($community->owner_id === $userId, 400, 'Cannot remove owner');

        CommunityMembership::where('community_id', $community->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Member removed']);
    }

    // ---- Inline guards ----
    private function authorizeModerator(Community $community): void
    {
        $uid = auth()->id();
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
        abort_unless($community->owner_id === auth()->id(), 403, 'Owner only');
    }
}
