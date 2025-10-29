<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class OnboardingController extends Controller
{
    public function show()
    {
        return view('onboarding');
    }

    public function save(Request $request)
    {
        $request->validate([
            'interests' => 'required|array',
        ]);

        $user = auth()->user();
        $user->interests = $request->interests;
        $user->save();

        return redirect()
            ->route('community-welcome')
            ->with('success', 'Your interests have been saved!');
    }
}
