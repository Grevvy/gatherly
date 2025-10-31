<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Community;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


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

    /**
     * Update the user's profile information including avatar upload.
     *
     * @param UpdateProfileRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if (Auth::user()->avatar) {
                Storage::disk('s3')->delete(Auth::user()->avatar);
            }
            
            // Store the new avatar
            $path = $request->file('avatar')->store('avatars', 's3');
            $validated['avatar'] = $path;
        }

        Auth::user()->update($validated);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully');
    }
}