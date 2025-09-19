<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    // Show register form
    public function show()
    {
        return view('auth.register');
    }

    // Handle registration
    public function store(Request $request)
    {
        // Validate form
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','string','lowercase','email','max:255','unique:'.User::class],
            'password' => ['required','confirmed','min:8'],
        ]);

        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        // Log them in immediately
        Auth::login($user);

        // Redirect to dashboard
        return redirect()->intended('/dashboard');
    }
}
