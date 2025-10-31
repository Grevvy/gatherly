<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Community;
use App\Models\Post;
use App\Models\CommunityMembership;

class DashboardController extends \Illuminate\Routing\Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        
        if (!$user->memberships()->exists()) {
            return redirect()->route('explore')
                ->with('info', 'Join a community to start your feed!');
        }

        
        $community = $request->query('community')
            ? Community::where('slug', $request->query('community'))->first()
            : null;

        $posts = collect();

        if ($community) {
            $posts = Post::where('community_id', $community->id)
                ->with(['user:id,name,avatar'])
                ->when(!Auth::user()->isSiteAdmin(), function ($query) use ($community) {
                    // Check if user is a community moderator/admin/owner
                    $isAdmin = CommunityMembership::where('community_id', $community->id)
                        ->where('user_id', Auth::id())
                        ->where('status', 'active')
                        ->whereIn('role', ['owner', 'admin', 'moderator'])
                        ->exists();

                    if ($isAdmin) {
                        return $query; // show all posts
                    }

                    // Regular users
                    return $query->where(function ($q) {
                        $q->where('status', 'published')
                          ->orWhere(function ($q) {
                              $q->where('user_id', Auth::id())
                                ->whereIn('status', ['draft', 'pending']);
                          });
                    });
                })
                ->latest()
                ->get();
        }

        return view('dashboard', ['posts' => $posts]);
    }
}
