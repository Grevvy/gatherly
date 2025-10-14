<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile');
    }

    public function edit(): View
    {
        return view('profile-edit', [
            'user' => Auth::user()
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