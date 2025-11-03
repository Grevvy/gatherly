<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Community;
use App\Models\CommunityMembership;
use App\Models\Like;
use App\Notifications\PostPublished;
use App\Notifications\PostPendingApproval;
use App\Notifications\PostLiked;
use App\Notifications\PostReplied;
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
            ->ordered()
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
        $post->content_updated_at = now(); // Set initial content timestamp
        
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
            $path = $request->file('image')->store('post-images', 's3');
            $post->image_path = $path;
        }

        $post->save();

        if ($post->status === 'published') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Notify all community members including the author about the published post
            app(NotificationService::class)->notifyCommunityMembers(
                $post->community,
                new PostPublished($post),
                null,
                'posts'
            );
        } else {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Only notify moderators about pending posts
            app(NotificationService::class)->notifyCommunityModerators(
                $post->community,
                new PostPendingApproval($post),
                $post->user_id,
                'posts'
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
            'remove_image' => ['sometimes', 'boolean'],
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

        // Track if content was actually modified
        $contentChanged = false;
        
        // Check if content was changed
        if (isset($data['content']) && $data['content'] !== $post->content) {
            $contentChanged = true;
        }

        // Handle image removal if requested
        if ($request->filled('remove_image') && $request->remove_image) {
            if ($post->image_path) {
                Storage::disk('s3')->delete($post->image_path);
                $contentChanged = true; // Image removal is a content change
            }
            $data['image_path'] = null;
        }
        // Handle new image upload if present
        elseif ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($post->image_path) {
                Storage::disk('s3')->delete($post->image_path);
            }
            $data['image_path'] = $request->file('image')->store('post-images', 's3');
            $contentChanged = true; // Image change is a content change
        }

        // Only update content_updated_at if content actually changed
        if ($contentChanged) {
            $data['content_updated_at'] = now();
        }

        // Set published_at when status changes to published (but this isn't a content change)
        if (isset($data['status']) && $data['status'] === 'published' && $originalStatus !== 'published') {
            $data['published_at'] = now();
        }

        $post->update($data);
        $post->refresh();

        if ($originalStatus !== 'published' && $post->status === 'published') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            // Notify all community members including the author about the published post
            app(NotificationService::class)->notifyCommunityMembers(
                $post->community,
                new PostPublished($post),
                null,
                'posts'
            );
        } elseif ($originalStatus !== 'pending' && $post->status === 'pending') {
            $post->loadMissing('community:id,name,slug', 'user:id,name');
            app(NotificationService::class)->notifyCommunityModerators(
                $post->community,
                new PostPendingApproval($post),
                $post->user_id,
                'posts'
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
                new PostPublished($post),
                null,
                'posts'
            );
        } else {
            $post->update([
                'status' => 'rejected',
                'published_at' => null,
            ]);
        }

        return response()->json($post->fresh());
    }

    public function boost(Request $request, Community $community, Post $post): JsonResponse
    {
        abort_if((int) $post->community_id !== (int) $community->id, 404);
        $this->authorize('boost', $post);

        $data = $request->validate([
            'duration_days' => ['nullable','integer','min:1','max:14'],
        ]);

        $duration = $data['duration_days'] ?? 3;
        $boostedAt = now();

        $post->forceFill([
            'boosted_at' => $boostedAt,
            'boosted_until' => $boostedAt->copy()->addDays($duration),
        ])->save();

        return response()->json([
            'message' => 'Post boosted successfully.',
            'boosted_until' => $post->boosted_until,
        ]);
    }

    public function unboost(Community $community, Post $post): JsonResponse
    {
        abort_if((int) $post->community_id !== (int) $community->id, 404);
        $this->authorize('boost', $post);

        $post->forceFill([
            'boosted_at' => null,
            'boosted_until' => null,
        ])->save();

        return response()->json([
            'message' => 'Post boost removed.',
        ]);
    }

    public function toggleLike(Community $community, Post $post): JsonResponse
    {
        $user = Auth::user();

        // If already liked → unlike
        $existing = $post->likes()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;

            // Only notify post author when someone else likes their post
            if ($post->user_id !== $user->id) {
                $post->loadMissing(['community:id,name,slug', 'user:id,name']);
                app(NotificationService::class)->notifyCommunityMembers(
                    $post->community,
                    new PostLiked($post, $user),
                    $user->id, // Exclude the liker
                    'posts'
                );
            }
        }

        // Return JSON response
        return response()->json([
            'liked' => $liked,
            'like_count' => $post->likes()->count()
        ]);
    }
}
