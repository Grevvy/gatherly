<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    /**
     * Display the photo gallery for a community
     */
    public function index(Request $request)
    {
        $community = null;
        $photos = collect();

        if ($slug = $request->query('community')) {
            $community = Community::where('slug', $slug)->firstOrFail();
            $membership = $community->memberships()
                ->where('user_id', $request->user()->id)
                ->first();

            $membership = $community->memberships()
                ->where('user_id', $request->user()->id)
                ->first();

            $query = $community->photos()->with(['user']);

            // Determine if user is owner/admin
            $isModeratorOrOwner = $membership && in_array($membership->role, ['owner', 'admin']);

            // If not owner/admin, filter photos based on view permission
            $query->where(function($query) use ($request, $isModeratorOrOwner) {
                // Always show approved photos
                $query->where('status', Photo::STATUS_APPROVED);
                
                // Show pending photos if user is the owner or a moderator
                if ($isModeratorOrOwner) {
                    $query->orWhere('status', Photo::STATUS_PENDING);
                }
                // Show user's own pending photos
                else {
                    $query->orWhere(function($q) use ($request) {
                        $q->where('status', Photo::STATUS_PENDING)
                          ->where('user_id', $request->user()->id);
                    });
                }
            });

            $photos = $query->latest()->paginate(12);

            // Pass the role check to the view
            return view('photo-gallery', compact('community', 'photos', 'isModeratorOrOwner'));
        }

        return view('photo-gallery', compact('community', 'photos'));
    }

    /**
     * Show the form for uploading a new photo
     */
    public function create(Request $request)
    {
        if ($slug = $request->query('community')) {
            $community = Community::with(['owner', 'memberships' => function($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }])
                ->where('slug', $slug)
                ->firstOrFail();
            
            // Debug information
            $isOwner = $community->owner_id === $request->user()->id;
            $membership = $community->memberships()
                ->where('user_id', $request->user()->id)
                ->first();

            Log::info('Photo Upload Authorization Debug', [
                'user_id' => $request->user()->id,
                'community_owner_id' => $community->owner_id,
                'is_owner' => $isOwner,
                'membership_status' => $membership ? $membership->status : null,
                'membership_role' => $membership ? $membership->role : null
            ]);

            try {
                // Check if user can upload photos to this community
                $this->authorize('create', [Photo::class, $community]);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return redirect()->route('photos.index', ['community' => $slug])
                    ->with('error', sprintf(
                        'Authorization failed. Debug: Owner=%s, Role=%s, Status=%s', 
                        $isOwner ? 'yes' : 'no',
                        $membership ? $membership->role : 'none',
                        $membership ? $membership->status : 'none'
                    ));
            }

            // Also get all communities for the sidebar
            $communities = $request->user()
                ? Community::whereHas('memberships', fn($q) => $q->where('user_id', $request->user()->id))->get()
                : collect();
            
            return view('upload-photo', compact('community', 'communities'));
        }
        
        return redirect()->route('photos.index')->with('error', 'Please select a community first.');
    }

    /**
     * Store a new photo
     */
    public function store(Request $request)
    {
        $community = Community::where('slug', $request->query('community'))->firstOrFail();
        
        // Check if user can create photos in this community
        $this->authorize('create', [Photo::class, $community]);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5MB max
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('photo')->store('community-photos', 'public');

        // Check if user is owner/admin - their photos are auto-approved
        $membership = $community->memberships()
            ->where('user_id', $request->user()->id)
            ->first();

        $status = ($membership && in_array($membership->role, ['owner', 'admin']))
            ? Photo::STATUS_APPROVED
            : Photo::STATUS_PENDING;

        $photo = new Photo([
            'image_path' => $path,
            'caption' => $validated['caption'],
            'user_id' => $request->user()->id,
            'community_id' => $community->id,
            'status' => $status,
            'reviewed_at' => $status === Photo::STATUS_APPROVED ? now() : null,
            'reviewed_by' => $status === Photo::STATUS_APPROVED ? $request->user()->id : null,
        ]);

        $photo->save();

        // If photo requires approval, notify community owner and moderators
        if ($status === Photo::STATUS_PENDING) {
            // Load users with owner/admin/moderator roles
            $moderators = $community->memberships()
                ->whereIn('role', ['owner', 'admin', 'moderator'])
                ->where('status', 'active')
                ->with('user')
                ->get()
                ->pluck('user');

            // Send notification
            \Illuminate\Support\Facades\Notification::send(
                $moderators,
                new \App\Notifications\PhotoPendingApproval($photo, $request->user())
            );
        }

        return redirect()
            ->route('photos.index', ['community' => $community->slug])
            ->with('success', 'Photo uploaded successfully!');
    }

    /**
     * Delete a photo
     */
    public function destroy(Photo $photo)
    {
        // Check if user can delete this photo
        Gate::authorize('delete', $photo);

        // Delete the file from storage
        Storage::disk('public')->delete($photo->image_path);

        // Delete the database record
        $photo->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Photo deleted successfully']);
        }

        return back()->with('success', 'Photo deleted successfully!');
    }

    /**
     * Approve a photo
     */
    public function approve(Photo $photo)
    {
        $this->authorize('review', $photo);
        $photo->approve(request()->user());

        // Notify community members that a new photo has been approved
        $community = $photo->community;
        $members = $community->memberships()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->pluck('user');

        \Illuminate\Support\Facades\Notification::send(
            $members,
            new \App\Notifications\PhotoApproved($photo, $photo->user)
        );
        
        return back()->with('success', 'Photo approved successfully!');
    }

    /**
     * Reject a photo
     */
    public function reject(Photo $photo)
    {
        $this->authorize('review', $photo);
        $photo->reject(request()->user());
        return back()->with('success', 'Photo rejected successfully!');
    }
}