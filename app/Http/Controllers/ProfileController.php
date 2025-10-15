<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Community;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(): View
    {
        $communities = Community::query()
            ->whereHas('memberships', fn($q) => $q->where('user_id', Auth::id()))
            ->get();

        return view('profile', [
            'communities' => $communities
        ]);
    }

    public function edit(): View
    {
        $communities = Community::query()
            ->whereHas('memberships', fn($q) => $q->where('user_id', Auth::id()))
            ->get();

        return view('profile-edit', [
            'user' => Auth::user(),
            'communities' => $communities
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Auth::user()->update($validated);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully');
    }
}