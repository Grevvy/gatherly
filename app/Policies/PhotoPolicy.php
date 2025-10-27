<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PhotoPolicy
{
    /**
     * Determine if the user can create photos in the community
     */
    public function create(User $user, Community $community): bool
    {
        // Active members can upload, but photos will need approval unless they're an admin/owner
        return $community->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine if the user can approve or reject photos
     */
    public function review(User $user, Photo $photo): bool
    {
        // Site admins can always review
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Community owners and admins can review
        $membership = $photo->community->memberships()
            ->where('user_id', $user->id)
            ->first();

        return $membership && in_array($membership->role, ['owner', 'admin']);
    }

    /**
     * Determine if the user can view the photo
     */
    public function view(User $user, Photo $photo): bool
    {
        // Photo owner can always see their own photos
        if ($photo->user_id === $user->id) {
            return true;
        }

        // Community owners and admins can see all photos
        $membership = $photo->community->memberships()
            ->where('user_id', $user->id)
            ->first();
        if ($membership && in_array($membership->role, ['owner', 'admin'])) {
            return true;
        }

        // Others can only see approved photos
        return $photo->isApproved();
    }

    /**
     * Determine if the user can delete the photo
     */
    public function delete(User $user, Photo $photo): bool
    {
        // Site admins can delete any photo
        if ($user->isSiteAdmin()) {
            return true;
        }

        // Community owners/admins can delete any photo
        $membership = $photo->community->memberships()
            ->where('user_id', $user->id)
            ->first();
        if ($membership && in_array($membership->role, ['owner', 'admin'])) {
            return true;
        }

        // Photo owner can only delete their own photos if they're still pending
        if ($photo->user_id === $user->id) {
            return $photo->isPending();
        }

        return false;
    }
}