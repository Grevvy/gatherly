<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Gatherly - Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
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

    <!-- Register Card -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 md:p-10 border border-slate-200">
        <h2 class="text-2xl font-semibold text-slate-800 mb-6 text-center">Create Your Account</h2>

        {{-- Show errors --}}
        @if ($errors->any())
            <div class="mb-4 text-red-600">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm
                    Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Register Button -->
            <button type="submit"
                class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 transition-all">
                Register
            </button>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <hr class="flex-grow border-slate-300">
                <span class="px-3 text-slate-500 text-sm">or</span>
                <hr class="flex-grow border-slate-300">
            </div>

            <!-- Already have account -->
            <div class="text-center">
                <p class="text-sm text-slate-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-blue-700 hover:underline font-medium">Log in</a>
                </p>
            </div>
        </form>
    </div>

</body>

</html>
