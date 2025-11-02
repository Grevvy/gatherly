<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Search for users by name or email for invitation purposes
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'community' => ['sometimes', 'string', 'exists:communities,slug'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $query = $request->input('q');
        $communitySlug = $request->input('community');
        $limit = $request->input('limit', 10);

        // Build base user search query
        $userQuery = User::select('id', 'name', 'email', 'avatar')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            });

        // If searching for a specific community, exclude existing members/pending invitations
        if ($communitySlug) {
            $community = Community::where('slug', $communitySlug)->first();
            
            if ($community) {
                $userQuery->whereNotExists(function ($q) use ($community) {
                    $q->select('*')
                      ->from('community_memberships')
                      ->whereColumn('community_memberships.user_id', 'users.id')
                      ->where('community_memberships.community_id', $community->id);
                });
            }
        }

        // Exclude the current user from results
        $userQuery->where('id', '!=', Auth::id());

        $users = $userQuery->limit($limit)->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            })
        ]);
    }
}