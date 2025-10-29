<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Gatherly - Login</title>
    @vite(['resources/js/app.jsx'])
</head>

<body
    class="bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen flex flex-col items-center justify-center">

    <!-- Brand Section -->
    <div class="text-center mb-8">
        <h1 class="text-5xl font-extrabold text-blue-800 tracking-tight">Gatherly</h1>
        <p class="mt-3 text-slate-600 text-lg">
            Build communities, host events, and collaborate in real time.
        </p>

        <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm text-slate-600">
            <span class="px-3 py-1 bg-white rounded-full shadow-sm border border-slate-200">Community Spaces</span>
            <span class="px-3 py-1 bg-white rounded-full shadow-sm border border-slate-200">Collaboration Tools</span>
            <span class="px-3 py-1 bg-white rounded-full shadow-sm border border-slate-200">Events & Networking</span>
        </div>
    </div>

    <!-- Login Card -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 md:p-10 border border-slate-200">
        <h2 class="text-2xl font-semibold text-slate-800 mb-6 text-center">Welcome Back</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-600">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Password with Toggle -->
            <!-- Password with Toggle -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 pr-12">
                    <button type="button" id="togglePassword"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 z-10">
                        <!-- Eye open -->
                        <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>

                        <!-- Eye closed -->
                        <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956
                 9.956 0 012.107-3.592m2.72-2.72A9.955 9.955 0 0112 5c4.477
                 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.132 5.411M15
                 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>


            <!-- Remember -->
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="remember" id="remember" class="rounded border-slate-300">
                <label for="remember" class="text-sm text-slate-700">Remember me</label>
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 transition-all">
                Log In
            </button>

            <!-- Forgot password -->
            <div class="text-center mt-4">
                <a href="{{ route('password.request') }}" class="text-blue-700 text-sm hover:underline">Forgot
                    password?</a>
            </div>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <hr class="flex-grow border-slate-300">
                <span class="px-3 text-slate-500 text-sm">or</span>
                <hr class="flex-grow border-slate-300">
            </div>

            <!-- Register -->
            <div class="flex justify-center">
                <a href="{{ route('register') }}"
                    class="bg-blue-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-600 transition-all">
                    Create New Account
                </a>
            </div>
        </form>
    </div>

</body>

</html>
