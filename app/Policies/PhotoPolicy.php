<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Photo;
use App\Models\User;

class PhotoPolicy
{
    /**
     * Determine if the user can upload photos to the community
     */
    public function upload(User $user, Community $community): bool
    {
        // User must be a member of the community to upload photos
        return $community->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can delete the photo
     */
    public function delete(User $user, Photo $photo): bool
    {
        // User can delete if they:
        // 1. Are the photo owner
        // 2. Are a community admin/owner
        // 3. Are a site admin
        if ($user->isSiteAdmin()) {
            return true;
        }

        if ($photo->user_id === $user->id) {
            return true;
        }

        $membership = $photo->community->memberships()
            ->where('user_id', $user->id)
            ->first();

        return $membership && in_array($membership->role, ['admin', 'owner']);
    }
}