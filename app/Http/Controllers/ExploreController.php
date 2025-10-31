<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Community;

class ExploreController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userInterests = $user->interests ?? [];

        
        if (is_string($userInterests)) {
            $userInterests = json_decode($userInterests, true) ?? [];
        } elseif (!is_array($userInterests)) {
            $userInterests = [];
        }

        
        $allCommunities = Community::with('memberships')
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhereHas('memberships', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        
        $recommended = collect();
        if (!empty($userInterests)) {
            $recommended = Community::with('memberships')
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
}
