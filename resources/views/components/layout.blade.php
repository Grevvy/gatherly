<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Gatherly' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
                    class="w-full px-3 py-2 border text-sm focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <!-- My Communities -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-500">MY COMMUNITIES</h3>
                    <!-- Add Community Button -->
                    <button id="add-community-btn"
                        class="flex items-center justify-center w-6 h-6 text-blue-600 text-sm font-medium hover:text-blue-800 rounded-full border border-blue-600">
                        +
                    </button>
                </div>

                <div id="community-list" class="space-y-2">
                    {{-- Existing communities injected here --}}
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col">

            <!-- Top Nav Tabs -->
            <div class="flex items-center justify-between bg-white border-b px-6">
                <!-- Left Nav -->
                <div class="flex space-x-6">
                    <a href="/dashboard"
                        class="py-3 {{ request()->routeIs('dashboard') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Feed
                    </a>
                    <a href="/events"
                        class="py-3 {{ request()->is('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Events
                    </a>
                    <a href="#"
                        class="py-3 {{ request()->is('messages') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Messages
                        <span class="ml-1 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="#"
                        class="py-3 {{ request()->is('members') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Members
                    </a>
                    <a href="#"
                        class="py-3 {{ request()->is('gallery') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Photo Gallery
                    </a>

                    {{-- Extra Dashboard tab only for Creator & Moderator --}}
                    @if (in_array(auth()->user()->role, ['creator', 'moderator']))
                        <a href="#"
                            class="py-3 {{ request()->is('dashboard-extra') ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                            Dashboard
                        </a>
                    @endif
                </div>

                <!-- Right Side (Bell + Avatar Menu) -->
                <div class="flex items-center space-x-4">

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button id="notif-btn"
                            class="flex items-center justify-center w-11 h-11 hover:bg-gray-100 rounded-full">
                            <i data-lucide="bell" class="w-5 h-5 text-gray-600"></i>
                            <!-- Badge -->
                            <span id="notif-badge"
                                class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 flex items-center justify-center rounded-full"></span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div id="notif-dropdown"
                            class="hidden absolute right-0 top-full mt-1.5 w-64 bg-white border-gray-200 shadow-xl z-50 overflow-hidden border">
                            <div class="p-4 text-sm text-gray-500 text-center">
                                No notifications yet
                            </div>
                            <div class="px-3 py-2 text-xs text-center text-blue-600 hover:bg-blue-50 cursor-pointer">
                                Mark all as read
                            </div>
                        </div>
                    </div>

                    <!-- User Menu (Avatar Dropdown) -->
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center hover:bg-gray-100 rounded-full px-2 py-1">
                            <div
                                class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>

                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 ml-1"></i>
                        </button>

                        <!-- User Dropdown -->
                        <div id="user-menu-dropdown"
                            class="hidden absolute right-0 top-full mt-1 w-56 bg-white border-gray-200 shadow-xl z-50 overflow-hidden divide-y divide-gray-100 border">
                            <div class="p-3">
                                <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-sm text-left px-3 py-2 text-red-600 hover:bg-red-50 rounded-md transition">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="flex-1 p-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        const toggle = (btnId, menuId) => {
            const btn = document.getElementById(btnId);
            const menu = document.getElementById(menuId);

            if (!btn || !menu) return;

            btn.addEventListener("click", (e) => {
                e.stopPropagation();
                menu.classList.toggle("hidden");
            });

            // Close menu if click outside
            document.addEventListener("click", () => {
                menu.classList.add("hidden");
            });
        };

        toggle("notif-btn", "notif-dropdown");
        toggle("user-menu-btn", "user-menu-dropdown");
    </script>
</body>

</html>
