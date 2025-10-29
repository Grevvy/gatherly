<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Gatherly - Reset Password</title>
    @vite(['resources/js/app.jsx'])
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            // Primary password field toggle
            const toggle = document.getElementById('togglePassword');
            const pwd = document.getElementById('password');
            const eye = document.getElementById('iconEye');
            const eyeOff = document.getElementById('iconEyeOff');
            if (toggle && pwd && eye && eyeOff) {
                toggle.addEventListener('click', function() {
                    const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
                    pwd.setAttribute('type', type);
                    eye.classList.toggle('hidden');
                    eyeOff.classList.toggle('hidden');
                });
            }

            // Confirmation password field toggle
            const toggle2 = document.getElementById('togglePasswordConfirm');
            const pwd2 = document.getElementById('password_confirmation');
            const eye2 = document.getElementById('iconEyeConfirm');
            const eyeOff2 = document.getElementById('iconEyeOffConfirm');
            if (toggle2 && pwd2 && eye2 && eyeOff2) {
                toggle2.addEventListener('click', function() {
                    const type = pwd2.getAttribute('type') === 'password' ? 'text' : 'password';
                    pwd2.setAttribute('type', type);
                    eye2.classList.toggle('hidden');
                    eyeOff2.classList.toggle('hidden');
                });
            }
        });
    </script>
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 md:p-10 border border-slate-200">
        <h2 class="text-2xl font-semibold text-slate-800 mb-6 text-center">Reset your password</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-700 bg-red-50 border border-red-200 px-4 py-3 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $email ?? '') }}" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 pr-12">
                    <button type="button" id="togglePassword"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 z-10">
                        <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.107-3.592m2.72-2.72A9.955 9.955 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.132 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm
                    Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 pr-12">
                    <button type="button" id="togglePasswordConfirm"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 z-10">
                        <svg id="iconEyeConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="iconEyeOffConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.107-3.592m2.72-2.72A9.955 9.955 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.132 5.411M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-700 text-white py-3 rounded-lg font-semibold hover:bg-blue-800 transition-all">
                Reset Password
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-blue-700 text-sm hover:underline">Back to login</a>
            </div>
        </form>
    </div>
</body>

</html>
