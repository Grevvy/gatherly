<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Community;
use Illuminate\Support\Facades\Auth;
use App\Models\CommunityMembership;
use App\Notifications\PostReplied;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller
{
    public function store(Request $request, $communitySlug, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $post = Post::findOrFail($postId);
        $community = Community::where('slug', $communitySlug)->firstOrFail();

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
        ]);

        // Load essential relationships
        $post->loadMissing(['community:id,name,slug', 'user:id,name']);

        // Send notification to post author only if they're not the commenter
        if ($post->user_id !== Auth::id()) {
            $post->user->notify(new PostReplied($post, $comment, Auth::user()));
        }

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'user' => $comment->user->name,
                'avatar' => $comment->user->avatar,
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
                'is_author' => $comment->user_id === Auth::id(),
            ],
        ]);
    }

    public function destroy($communitySlug, $postId, Comment $comment)
    {
        $post = Post::findOrFail($postId);
        $community = Community::where('slug', $communitySlug)->firstOrFail();
        
        // Check if user is author or community moderator
        $canDelete = $comment->user_id === Auth::id();
        
        if (!$canDelete) {
            $membership = CommunityMembership::where('community_id', $community->id)
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->whereIn('role', ['owner', 'admin', 'moderator'])
                ->first();
                
            $canDelete = $membership !== null;
        }

        if ($canDelete) {
            $comment->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }
}