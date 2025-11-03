<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Models\CommunityMembership;

class PostPolicy
{
    /**
     * Determine if the user can view any posts in the community.
     */
    public function viewAny(User $user): bool
    {
        // Site admins can view all posts
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Users can view posts in communities they're members of
        return true; // Basic viewing is allowed, individual posts will be filtered
    }

    /**
     * Determine if the user can view the post.
     */
    /**
     * Determine if the post can be auto-published by the user
     */
    public function autoPublish(User $user, Post $post): bool
    {
        // Site admins can auto-publish
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Check if user has moderator privileges or higher in the community
        return CommunityMembership::where('community_id', $post->community_id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->exists();
    }

    public function view(User $user, Post $post): bool
    {
        // Site admins can view all posts
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Draft posts can only be viewed by their authors
        if ($post->isDraft()) {
            return $post->user_id === $user->id;
        }

        // Pending and rejected posts can be viewed by:
        // - The author
        // - Community moderators/admins/owners
        if ($post->isPending() || $post->isRejected()) {
            if ($post->user_id === $user->id) {
                return true;
            }

            return CommunityMembership::where('community_id', $post->community_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'admin', 'moderator'])
                ->where('status', 'active')
                ->exists();
        }

        // Published posts can be viewed by community members
        return $post->isPublished() && CommunityMembership::where('community_id', $post->community_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(User $user): bool
    {
        // Site admins and community members can create posts
        return $user->isSiteAdmin() || CommunityMembership::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Site admins can update any post
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Users can update their own posts if they're drafts or pending
        if ($post->user_id === $user->id && ($post->isDraft() || $post->isPending())) {
            return true;
        }

        // Community moderators/admins/owners can update any post in their community
        return CommunityMembership::where('community_id', $post->community_id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Same rules as update
        return $this->update($user, $post);
    }

    /**
     * Determine if the user can approve or reject the post.
     */
    public function moderate(User $user, Post $post): bool
    {
        // Site admins can moderate any post
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Community moderators/admins/owners can moderate posts in their community
        return CommunityMembership::where('community_id', $post->community_id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine if the user can boost the post.
     */
    public function boost(User $user, Post $post): bool
    {
        if ($user->isSiteAdmin()) {
            return true;
        }

        if ($post->user_id === $user->id) {
            return true;
        }

        return CommunityMembership::where('community_id', $post->community_id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->where('status', 'active')
            ->exists();
    }
}
