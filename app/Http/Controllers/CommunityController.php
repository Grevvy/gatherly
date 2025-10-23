<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $q = Community::query()
            ->when($request->filled('q'), function ($qq) use ($request) {
                $term = '%'.strtolower($request->q).'%';
                $qq->whereRaw('LOWER(name) LIKE ?', [$term]);
            })
            ->when($request->filled('visibility'), fn($qq) => $qq->where('visibility', $request->visibility))
            ->when($request->boolean('mine'), fn($qq) => $qq->whereHas('memberships', fn($m) => $m->where('user_id', Auth::id())))
            ->latest();

        return response()->json($q->paginate(12));
    }

    /**
     * Search public communities for the sidebar dropdown.
     * Returns a small list of public communities the current user is not a member of.
     */
    public function search(Request $request)
    {
    $uid = Auth::id();

        // If debug=1 is provided, return broader results for troubleshooting
        $isDebug = $request->boolean('debug');

        // Return communities that are public OR that the current user already belongs to.
        $q = Community::query()
            ->when(!$isDebug, function ($qq) use ($uid) {
                $qq->where(function ($q2) use ($uid) {
                    $q2->where('visibility', 'public')
                        ->orWhereHas('memberships', fn($m) => $m->where('user_id', $uid));
                });
            })
            ->when($request->filled('q'), function ($qq) use ($request) {
                $term = '%'.strtolower($request->q).'%';
                $qq->whereRaw('LOWER(name) LIKE ?', [$term]);
            })
            // eager-load the current user's membership (if any)
            ->with(['memberships' => fn($m) => $m->where('user_id', $uid)])
            ->withCount('memberships')
            ->orderBy('memberships_count', 'desc')
            ->limit(7)
            ->get(['id','name','slug','description']);

        // Map to include a single membership object (role, status) if present
        $results = $q->map(function ($c) {
            $m = $c->memberships->first();
            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'memberships_count' => $c->memberships_count ?? 0,
                'membership' => $m ? ['role' => $m->role, 'status' => $m->status] : null,
            ];
        });

        return response()->json($results);
    }
public function store(Request $request)
{
    $data = $request->validate([
        'name'         => ['required','string','max:120'],
        'description'  => ['nullable','string'],
        'banner_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        'visibility'   => ['nullable','in:public,private'],
        'join_policy'  => ['nullable','in:open,request,invite'],
        'tags'         => ['nullable','string'], // ✅ added
    ]);

    $data['owner_id']   = Auth::id();
    $data['visibility'] = $data['visibility'] ?? 'public';
    $data['join_policy'] = $data['join_policy'] ?? 'open';

    // ✅ convert comma-separated tags to array
    if (!empty($data['tags'])) {
        $data['tags'] = array_map('trim', explode(',', strtolower($data['tags'])));
    }

    // ✅ banner image
    if ($request->hasFile('banner_image')) {
        $path = $request->file('banner_image')->store('communities/banners', 'public');
        $data['banner_image'] = "/storage/{$path}";
    }

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
            'name'         => ['sometimes','string','max:120'],
            'description'  => ['sometimes','nullable','string'],
            'banner_image' => ['sometimes','nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'visibility'   => ['sometimes','in:public,private'],
            'join_policy'  => ['sometimes','in:open,request,invite'],
        ]);

        // If a new banner is uploaded, remove old local file then store new one
        if ($request->hasFile('banner_image')) {
            if ($community->banner_image && str_starts_with($community->banner_image, '/storage/')) {
                $old = str_replace('/storage/', '', $community->banner_image);
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('banner_image')->store('communities/banners', 'public');
            $data['banner_image'] = "/storage/{$path}";
        }

        $community->update($data);
        return response()->json($community->fresh());
    }

    public function destroy(Community $community)
    {
        $this->authorizeOwnerOrAdmin($community);

        // Delete banner file if stored locally
        if ($community->banner_image && str_starts_with($community->banner_image, '/storage/')) {
            $old = str_replace('/storage/', '', $community->banner_image);
            Storage::disk('public')->delete($old);
        }

        $community->delete();
        return response()->json(['message' => 'Community deleted']);
    }

    private function authorizeOwnerOrAdmin(Community $community): void
    {
    $uid = Auth::id();
        $isOwner = $community->owner_id === $uid;
        $isAdmin = $community->memberships()
            ->where('user_id', $uid)
            ->whereIn('role', ['owner','admin'])
            ->where('status','active')
            ->exists();

        abort_unless($isOwner || $isAdmin, 403, 'Forbidden');
    }
}
