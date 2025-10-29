<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Gatherly - Forgot Password</title>
    @vite(['resources/js/app.jsx'])
</head>

<body
    class="bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 md:p-10 border border-slate-200">
        <h2 class="text-2xl font-semibold text-slate-800 mb-6 text-center">Forgot your password?</h2>

        @if (session('status'))
            <div class="mb-4 text-green-700 bg-green-50 border border-green-200 px-4 py-3 rounded">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-red-700 bg-red-50 border border-red-200 px-4 py-3 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-slate-600 mb-5 text-sm">
            Enter your email address and we'll email you a link to reset your password.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 transition-all">
                Email Password Reset Link
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-blue-700 text-sm hover:underline">Back to login</a>
            </div>
        </form>
    </div>
</body>

</html>
