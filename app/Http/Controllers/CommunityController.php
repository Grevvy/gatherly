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
        $user = Auth::user();

        // If debug=1 is provided, return broader results for troubleshooting
        $isDebug = $request->boolean('debug');

        // Return communities that are public OR that the current user already belongs to OR if user is site admin
        $q = Community::query()
            ->when(!$isDebug && !$user->isSiteAdmin(), function ($qq) use ($uid) {
                $qq->where(function ($q2) use ($uid) {
                    $q2->where('visibility', 'public')
                        ->orWhereHas('memberships', fn($m) => $m->where('user_id', $uid));
                });
            })
            ->when($request->filled('q'), function ($qq) use ($request) {
                $term = '%'.strtolower($request->q).'%';
                $qq->where(function($q) use ($term) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                      ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                      ->orWhereRaw('JSON_SEARCH(LOWER(tags), "one", ?) IS NOT NULL', [$term]);
                });
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
        'tags'         => ['nullable','array'],
        'tags.*'       => ['string'],
    ]);

    $data['owner_id']   = Auth::id();
    $data['visibility'] = $data['visibility'] ?? 'public';
    $data['join_policy'] = $data['join_policy'] ?? 'open';

    // Normalize selected tags
    if (array_key_exists('tags', $data) && is_array($data['tags'])) {
        $data['tags'] = collect($data['tags'])
            ->filter(fn ($tag) => filled($tag))
            ->map(fn ($tag) => strtolower(trim($tag)))
            ->unique()
            ->values()
            ->all();
    }

    // ✅ banner image
    if ($request->hasFile('banner_image')) {
        $path = $request->file('banner_image')->store('communities/banners', 's3');
        $data['banner_image'] = $path; // Store path only, URL will be generated when needed
    }

    return DB::transaction(function () use ($data) {
        $community = Community::create($data);

        CommunityMembership::create([
            'community_id' => $community->id,
            'user_id'      => $community->owner_id,
            'role'         => 'owner',
            'status'       => 'active',
            'notification_preferences' => CommunityMembership::DEFAULT_NOTIFICATION_PREFERENCES,
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
            'tags'         => ['sometimes','nullable','array'],
            'tags.*'       => ['string'],
        ]);

        if (array_key_exists('tags', $data) && is_array($data['tags'])) {
            $data['tags'] = collect($data['tags'])
                ->filter(fn ($tag) => filled($tag))
                ->map(fn ($tag) => strtolower(trim($tag)))
                ->unique()
                ->values()
                ->all();
        }
        // If a new banner is uploaded, remove old file then store new one
        if ($request->hasFile('banner_image')) {
            if ($community->banner_image) {
                // Try to delete from both old (public) and new (s3) storage
                if (str_starts_with($community->banner_image, '/storage/')) {
                    $old = str_replace('/storage/', '', $community->banner_image);
                    Storage::disk('public')->delete($old);
                } else {
                    Storage::disk('s3')->delete($community->banner_image);
                }
            }
            $path = $request->file('banner_image')->store('communities/banners', 's3');
            $data['banner_image'] = $path; // Store path only
        }

        $community->update($data);
        return response()->json($community->fresh());
    }

    public function destroy(Community $community)
    {
        $this->authorizeOwnerOrAdmin($community);

        // Delete banner file
        if ($community->banner_image) {
            if (str_starts_with($community->banner_image, '/storage/')) {
                $old = str_replace('/storage/', '', $community->banner_image);
                Storage::disk('public')->delete($old);
            } else {
                Storage::disk('s3')->delete($community->banner_image);
            }
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
