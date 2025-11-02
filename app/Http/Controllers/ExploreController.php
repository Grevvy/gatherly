<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Community;

class ExploreController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userInterests = $user->interests ?? [];

        
        if (is_string($userInterests)) {
            $userInterests = json_decode($userInterests, true) ?? [];
        } elseif (!is_array($userInterests)) {
            $userInterests = [];
        }

        // All communities - site admins can see everything
        $allCommunities = Community::with('memberships')
            ->when(!$user->isSiteAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('visibility', 'public')
                        ->orWhereHas('memberships', function ($membership) use ($user) {
                            $membership->where('user_id', $user->id);
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Recommended communities based on user interests
        $recommended = collect();
        if (!empty($userInterests)) {
            $recommended = Community::with('memberships')
                ->when(!$user->isSiteAdmin(), function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->where('visibility', 'public')
                            ->orWhereHas('memberships', function ($membership) use ($user) {
                                $membership->where('user_id', $user->id);
                            });
                    });
                })
                ->where(function ($query) use ($userInterests) {
                    foreach ($userInterests as $interest) {
                        $query->orWhereJsonContains('tags', $interest);
                    }
                })
                ->get();
        }

        
        $filteredAll = $allCommunities->reject(function ($community) use ($recommended) {
            return $recommended->pluck('id')->contains($community->id);
        })->values();

        return view('explore', [
            'recommended' => $recommended,
            'communities' => $filteredAll,
        ]);
    }

    public function search(Request $request)
    {
        $user = Auth::user();
        $searchTerm = $request->input('q', '');

        if (empty($searchTerm)) {
            return response()->json([
                'recommended' => [],
                'communities' => []
            ]);
        }

        $term = '%' . strtolower($searchTerm) . '%';

        // Get user interests for recommendations
        $userInterests = $user->interests ?? [];
        if (is_string($userInterests)) {
            $userInterests = json_decode($userInterests, true) ?? [];
        } elseif (!is_array($userInterests)) {
            $userInterests = [];
        }

        // Search all accessible communities - site admins can see everything
        $communities = Community::with(['memberships' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->withCount('memberships')
            ->when(!$user->isSiteAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('visibility', 'public')
                        ->orWhereHas('memberships', function ($membership) use ($user) {
                            $membership->where('user_id', $user->id);
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter by search term (name, description, and tags) in PHP for better compatibility
        $searchTermClean = trim($term, '%');
        $allCommunities = $communities->filter(function ($community) use ($searchTermClean) {
            // Check name
            if (stripos($community->name, $searchTermClean) !== false) {
                return true;
            }
            
            // Check description
            if ($community->description && stripos($community->description, $searchTermClean) !== false) {
                return true;
            }
            
            // Check tags
            if ($community->tags && is_array($community->tags)) {
                foreach ($community->tags as $tag) {
                    if (stripos($tag, $searchTermClean) !== false) {
                        return true;
                    }
                }
            }
            
            return false;
        });

        // Filter recommended communities from search results
        $recommended = collect();
        if (!empty($userInterests)) {
            $recommended = $allCommunities->filter(function ($community) use ($userInterests) {
                $communityTags = $community->tags ?? [];
                return !empty(array_intersect($communityTags, $userInterests));
            });
        }

        // Filter out recommended from all communities
        $filteredAll = $allCommunities->reject(function ($community) use ($recommended) {
            return $recommended->pluck('id')->contains($community->id);
        })->values();

        return response()->json([
            'recommended' => $recommended->values(),
            'communities' => $filteredAll
        ]);
    }
}
