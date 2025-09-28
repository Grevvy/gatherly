<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Gatherly' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

    <!-- Layout Wrapper -->
    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="w-72 bg-white shadow-lg p-4 flex flex-col">
            <!-- App Logo -->
            <div class="text-2xl font-bold text-blue-600 mb-6">Gatherly</div>

            <!-- Search -->
            <div class="mb-6">
                <input type="text" placeholder="Search communities..."
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <!-- Nav -->
            <nav class="space-y-2 mb-6">
                <!-- Eventually Dashboard in sidebar for creator/moderator -->
                <a href="{{ route('dashboard') }}"
                    class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                    Feed
                </a>
                <a href="{{ route('events') }}" class="block px-3 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('events') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">Events</a>
                <a href="#" class="block px-3 py-2 rounded hover:bg-gray-100">
                    Messages
                    <span class="ml-1 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">0</span>
                </a>
                <a href="#" class="block px-3 py-2 rounded hover:bg-gray-100">Members</a>
            </nav>

            <!-- My Communities -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 mb-2">MY COMMUNITIES</h3>
                <div class="space-y-2">
                    {{-- Add communities dynamically here later --}}
                </div>
            </div>

            <!-- User Profile + Logout -->
            <div class="mt-auto border-t pt-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <!-- Logout button under name/email -->
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="w-full text-sm bg-red-100 hover:bg-red-200 text-red-600 px-3 py-2 rounded-lg shadow-sm transition">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 space-y-6">

            <!-- Tabs -->
            <div class="flex space-x-6 border-b">
                <a href="{{ route('dashboard') }}"
                    class="py-2 {{ request()->routeIs('dashboard') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                    Feed
                </a>
                <a href="{{ route('events') }}" class="py-2 {{ request()->routeIs('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Events</a>
                <a href="#" class="py-2 text-gray-600 hover:text-gray-800">Members</a>
                <a href="#" class="py-2 text-gray-600 hover:text-gray-800"> Photo Gallery</a>
                <!-- Eventually settings tab for creator/mod? -->
            </div>

            <!-- Page Content Slot -->
            <div>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>

</html>
