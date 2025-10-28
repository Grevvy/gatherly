<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Community;
use App\Models\CommunityMembership;
use App\Notifications\PostPublished;
use App\Notifications\PostPendingApproval;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PostController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get posts for a community
     */
    public function index(Community $community): JsonResponse
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::where('community_id', $community->id)
            ->when(!Auth::user()->isSiteAdmin(), function ($query) use ($community) {
                // Check if user is a community moderator/admin/owner
                $isAdmin = CommunityMembership::where('community_id', $community->id)
                    ->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'admin', 'moderator'])
                    ->exists();

                // If not a moderator, check regular membership
                if (!$isAdmin) {
                    $isMember = CommunityMembership::where('community_id', $community->id)
                        ->where('user_id', Auth::id())
                        ->where('status', 'active')
                        ->exists();

                    if (!$isMember) {
                        return $query->where('id', 0); // Return no posts if not a member
                    }
                }

                // If moderator/admin/owner, see all posts
                if ($isAdmin) {
                    return $query;
                }

                // Regular members see:
                // - Published posts
                // - Their own drafts and pending posts
                return $query->where(function ($q) {
                    $q->where('status', 'published')
                        ->orWhere(function ($q) {
                            $q->where('user_id', Auth::id())
                                ->whereIn('status', ['draft', 'pending']);
                        });
                });
            })
            ->with(['user:id,name'])
            ->latest()
            ->paginate(15);

        return response()->json($posts);
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request, Community $community)
    {
        $this->authorize('create', Post::class);

        // Validate common fields
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $post = new Post();
        $post->content = $validated['content'];
        $post->user_id = Auth::id();
        $post->community_id = $community->id;
        
        // Use policy to check if post can be auto-published
        if (Auth::user()->can('autoPublish', $post)) {
            $post->status = 'published';
            $post->published_at = now();
        } else {
            $post->status = 'pending';
            $post->published_at = null;
        }

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('post-images', 'public');
            $post->image_path = $path;
        }

        $post->save();

        if ($post->status === 'published') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Notify all community members including the author about the published post
            app(NotificationService::class)->notifyCommunityMembers(
                $post->community,
                new PostPublished($post)
            );
        } else {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Only notify moderators about pending posts
            app(NotificationService::class)->notifyCommunityModerators(
                $post->community,
                new PostPendingApproval($post),
                $post->user_id
            );
        }

        // Return response based on request type
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post
            ]);
        }

        return redirect()->back()->with('success', 'Post created successfully!');
    }

    /**
     * Get a specific post
     */
    public function show(Community $community, Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        return response()->json(
            $post->load(['user:id,name'])
        );
    }

    /**
     * Update the post
     */
    public function update(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $originalStatus = $post->status;

        $data = $request->validate([
            'content' => ['sometimes', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:draft,pending,published'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'], // 5MB max
        ]);

        // If changing to pending and user is moderator/admin/owner, auto-publish
        if (
            ($data['status'] ?? null) === 'pending' &&
            $post->user_id === Auth::id()
        ) {
            $membership = Auth::user()->memberships()
                ->where('community_id', $community->id)
                ->first();

            if ($membership && in_array($membership->role, ['owner', 'admin', 'moderator'])) {
                $data['status'] = 'published';
                $data['published_at'] = now();
            }
        }

        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $data['image_path'] = $request->file('image')->store('post-images', 'public');
        }

        $post->update($data);
        $post->refresh();

        if ($originalStatus !== 'published' && $post->status === 'published') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Notify all community members including the author about the published post
            app(NotificationService::class)->notifyCommunityMembers(
                $post->community,
                new PostPublished($post)
            );
        } elseif ($originalStatus !== 'pending' && $post->status === 'pending') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            app(NotificationService::class)->notifyCommunityModerators(
                $post->community,
                new PostPendingApproval($post),
                $post->user_id
            );
        }

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->fresh()->load('user:id,name')
        ]);
    }

    /**
     * Remove the post
     */
    public function destroy(Community $community, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Approve or reject a pending post
     */
    public function moderate(Request $request, Community $community, Post $post): JsonResponse
    {
        $this->authorize('moderate', $post);

        $data = $request->validate([
            'status' => ['required', 'in:published,rejected'],
        ]);

        if ($data['status'] === 'published') {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            $post->refresh();
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Notify all community members including the author about the published post
            app(NotificationService::class)->notifyCommunityMembers(
                $post->community,
                new PostPublished($post)
            );
        } else {
            $post->update([
                'status' => 'rejected',
                'published_at' => null,
            ]);
        }

        return response()->json($post->fresh());
    }
}