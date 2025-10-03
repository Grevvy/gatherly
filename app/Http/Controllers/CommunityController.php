<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $q = Community::query()
            ->when($request->filled('q'), fn($qq) => $qq->where('name', 'ILIKE', '%'.$request->q.'%'))
            ->when($request->filled('visibility'), fn($qq) => $qq->where('visibility', $request->visibility))
            ->when($request->boolean('mine'), fn($qq) => $qq->whereHas('memberships', fn($m) => $m->where('user_id', auth()->id())))
            ->latest();

        return response()->json($q->paginate(12));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:120'],
            'description' => ['nullable','string'],
            'visibility'  => ['nullable','in:public,private,hidden'],
            'join_policy' => ['nullable','in:open,request,invite'],
        ]);

        $data['owner_id'] = auth()->id();
        $data['visibility'] = $data['visibility'] ?? 'public';
        $data['join_policy'] = $data['join_policy'] ?? 'open';

        return DB::transaction(function () use ($data) {
            $community = Community::create($data);

            CommunityMembership::create([
                'community_id' => $community->id,
                'user_id'      => $community->owner_id,
                'role'         => 'owner',
                'status'       => 'active',
            ]);

            return response()->json($community, 201);
        });
    }

    public function show(Community $community)
    {
        return response()->json($community->load(['owner:id,name']));
    }

    public function update(Request $request, Community $community)
    {
        $this->authorizeOwnerOrAdmin($community);

        $data = $request->validate([
            'name'        => ['sometimes','string','max:120'],
            'description' => ['sometimes','nullable','string'],
            'visibility'  => ['sometimes','in:public,private,hidden'],
            'join_policy' => ['sometimes','in:open,request,invite'],
        ]);

        $community->update($data);
        return response()->json($community->fresh());
    }

    public function destroy(Community $community)
    {
        $this->authorizeOwnerOrAdmin($community);
        $community->delete();
        return response()->json(['message' => 'Community deleted']);
    }

    private function authorizeOwnerOrAdmin(Community $community): void
    {
        $uid = auth()->id();
        $isOwner = $community->owner_id === $uid;
        $isAdmin = $community->memberships()
            ->where('user_id', $uid)
            ->whereIn('role', ['owner','admin'])
            ->where('status','active')
            ->exists();

        abort_unless($isOwner || $isAdmin, 403, 'Forbidden');
    }
}
