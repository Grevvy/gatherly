<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    @vite(['resources/css/app.css','resources/js/app.jsx'])
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white shadow-md rounded-lg p-8">
        <h1 class="text-2xl font-bold text-center mb-6">Login</h1>

        @if ($errors->any())
            <div class="mb-4 text-red-600">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full border p-2 rounded">
            </div>

            <div>
                <label>Password</label>
                <input type="password" name="password" required
                       class="mt-1 block w-full border p-2 rounded">
            </div>

            <div>
                <label><input type="checkbox" name="remember"> Remember me</label>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded">
                Log In
            </button>
        </form>
        <p class="mt-4 text-sm text-center">
    Don’t have an account?
    <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Register</a>
</p>

    </div>
</body>
</html>
