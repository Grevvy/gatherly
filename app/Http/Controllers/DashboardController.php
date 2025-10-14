<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends \Illuminate\Routing\Controller
{
    // Ensure only authenticated users can access
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $community = request('community') ? \App\Models\Community::where('slug', request('community'))->first() : null;
        
        $posts = collect();
        if ($community) {
            $posts = \App\Models\Post::where('community_id', $community->id)
                ->with(['user:id,name'])
                ->when(!\Illuminate\Support\Facades\Auth::user()->isSiteAdmin(), function ($query) {
                    return $query->where(function ($q) {
                        $q->where('status', 'published')
                            ->orWhere(function ($q) {
                                $q->where('user_id', \Illuminate\Support\Facades\Auth::id())
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
