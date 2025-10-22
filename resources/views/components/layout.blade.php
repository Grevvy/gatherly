@props([
    'title' => null,
    'community' => null,
    'communities' => [],
])
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Gatherly' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <input type="hidden" name="_token" id="csrf-token" value="{{ csrf_token() }}">

    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside
            class="w-72 bg-white/70 backdrop-blur-xl border-r border-gray-100 shadow-[0_8px_24px_rgba(0,0,0,0.05)] p-5 flex flex-col">

            <div class="flex items-center gap-2 mb-6">
                <img src="{{ asset('images/gatherly-logo.png') }}" alt="Gatherly Logo"
                    class="w-8 h-8 rounded-lg shadow-sm object-contain">
                <h1
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-purple-500 tracking-tight">
                    Gatherly
                </h1>
            </div>

            <!-- Search -->
            <div class="mb-6 relative">
                <input id="search-box" type="text" placeholder="Search communities..."
                    class="w-full px-3 py-2 border text-sm focus:outline-none focus:ring focus:ring-blue-300"
                    value="{{ request('search') }}" autocomplete="off" />

                <!-- Live search dropdown -->
                <div id="search-dropdown"
                    class="hidden absolute left-0 mt-1 w-full bg-white border shadow-lg z-50 rounded-md overflow-hidden">
                    <div id="search-results" class="divide-y divide-gray-100 max-h-64 overflow-y-auto"></div>
                    <div id="search-empty" class="p-2 text-sm text-gray-500 hidden">No communities found.</div>
                </div>
            </div>

            <!-- My Communities -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-500">MY COMMUNITIES</h3>
                    <a href="{{ route('create-community') }}"
                        class="flex items-center justify-center w-6 h-6 text-blue-600 text-sm font-medium hover:text-blue-800 rounded-full border border-blue-600 transition">
                        +
                    </a>

                </div>

                <div id="community-list" class="space-y-1">
                    @if (!empty($communities) && count($communities) > 0)
                        <ul class="space-y-1 list-none">
                            @foreach ($communities as $c)
                                @php $active = isset($community) && $community->id === $c->id; @endphp

                                <li class="community-item group">
                                    <div class="flex items-center justify-between px-2 py-1 transition {{ $active ? 'bg-blue-100' : 'hover:bg-blue-50' }}">
                                        <a href="/dashboard?community={{ $c->slug }}"
                                            class="{{ $active ? 'text-blue-600 font-medium' : 'text-gray-800' }} flex-grow">
                                            <span class="font-medium text-sm">{{ $c->name }}</span>
                                            <span class="text-xs text-gray-400 block">{{ ucfirst($c->visibility) }}</span>
                                        </a>
                                        @if($c->owner_id !== auth()->id())
                                            <form action="/communities/{{ $c->slug }}/leave" method="POST" class="hidden group-hover:block ml-2 leave-community-form">
                                                @csrf
                                                <button type="button" 
                                                    class="p-1 rounded-lg hover:bg-red-100 text-gray-400 hover:text-red-500 transition-colors"
                                                    onclick="confirmLeaveCommunity(this, '{{ $c->name }}')"
                                                    title="Leave Community">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p id="no-communities" class="text-gray-500 text-sm">No communities found.</p>
                    @endif
                </div>
            </div>
        </aside>

        <!-- Main Dashboard -->
        <main class="flex-1 flex flex-col bg-gray-50">
            <!-- Top Tabs -->
            <div class="flex items-center justify-between bg-white border-b px-6 relative">
                <div class="flex space-x-6">
                    @php $slug = request('community'); @endphp

                    <a href="/dashboard{{ $slug ? '?community=' . $slug : '' }}"
                        class="py-3 {{ request()->is('dashboard') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Feed
                    </a>

                    <a href="{{ $slug ? '/events?community=' . $slug : '/dashboard' }}"
                        class="py-3 {{ request()->is('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Events
                    </a>

                    <a href="{{ $slug ? '/messages?community=' . $slug : '/dashboard' }}"
                        class="py-3 {{ request()->is('messages') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Messages
                    </a>

                    <a href="{{ $slug ? '/members?community=' . $slug : '/dashboard' }}"
                        class="py-3 {{ request()->is('members') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Members
                    </a>

                    <a href="{{ $slug ? '/gallery?community=' . $slug : '/dashboard' }}"
                        class="py-3 {{ request()->is('gallery') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Photo Gallery
                    </a>
               <a href="/explore"
               class="py-3 {{ request()->is('explore') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                Explore
                 </a>

                </div>

                <div class="flex items-center gap-4">

                    <!-- Calendar Icon -->
                    <div class="relative mt-2">
                        @php $slug = $community?->slug ?? request('community'); @endphp


                        @if ($slug)
                            <a href="{{ route('events', ['community' => $slug, 'tab' => 'calendar']) }}"
                                class="p-2 hover:bg-gray-100 block rounded-lg cursor-pointer">
                                <i data-lucide="calendar-days" class="w-5 h-5 text-gray-600"></i>
                            </a>
                        @else
                            <span class="p-2 block text-gray-400 opacity-50" title="Select a community first">
                                <i data-lucide="calendar" class="w-5 h-5"></i>
                            </span>
                        @endif
                    </div>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button id="notif-btn" class="p-2 hover:bg-gray-100 relative mt-2 rounded-lg">
                            <i data-lucide="bell" class="w-5 h-5 text-gray-600"></i>
                        </button>

                        <div id="notif-dropdown"
                            class="hidden absolute right-0 mt-0.99 w-56 bg-white border shadow-lg z-50 overflow-hidden divide-y divide-gray-100">
                            <div class="p-3 text-sm text-gray-700 border-b font-semibold">Notifications</div>
                            <div class="max-h-60 overflow-y-auto divide-y divide-gray-100">
                                <div class="p-3 text-sm text-gray-500 text-center">No notifications</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg">
                            <div
                                class="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, length: 1)) }}
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                        </button>

                        <div id="user-menu-dropdown"
                            class="hidden absolute right-0 mt-0 w-56 bg-white border shadow-lg z-50 overflow-hidden divide-y divide-gray-100">
                            <div class="p-3">
                                <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <!--  View Profile link -->
                            <a href="{{ route('profile.show') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i data-lucide="user" class="w-4 h-4 inline mr-2 text-gray-500"></i>
                                View Profile
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="p-2">
                                @csrf
                                <button type="submit"
                                    class="w-full text-sm text-left px-3 py-2 text-red-600 hover:bg-red-50 rounded-md">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Community Banner -->
            @if ($community)
                <div class="relative w-full max-w-6xl h-64 mx-auto rounded-3xl overflow-hidden group">
                    <!-- Background Image with Parallax Effect -->
                    <div class="absolute inset-0">
                        <img src="{{ asset($community->banner_image ?? 'images/default-banner.jpg') }}"
                            alt="Community Banner"
                            class="w-full h-full object-cover transform group-hover:scale-105 transition-all duration-700 ease-out" />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-white-900/70 via-white-700/20 to-transparent">
                        </div>
                    </div>

                    <!-- Glassy Title Card -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center z-20">
                        <div
                            class="bg-white/20 backdrop-blur-xl border border-white/30 shadow-[0_8px_32px_0_rgba(31,38,135,0.37)] px-8 py-4 rounded-3xl transition-all duration-500 hover:shadow-[0_8px_50px_0_rgba(31,38,135,0.5)]">
                            <h1
                                class="text-5xl font-extrabold text-white tracking-wide drop-shadow-[0_3px_10px_rgba(0,0,0,0.4)]">
                                {{ $community->name }}
                            </h1>
                            @if ($community->description)
                                <p class="text-white/90 text-sm italic mt-2">
                                    {{ $community->description }}
                                </p>
                            @endif
                        </div>
                    </div>


                    <!-- Banner Content -->
                    @php
                        $userId = auth()->id();
                        $isOwner = $community->owner_id === $userId;
                        $isAdmin = $community->memberships->contains(
                            fn($m) => $m->user_id === $userId && $m->role === 'admin' && $m->status === 'active',
                        );
                    @endphp @if ($isOwner || $isAdmin)
                        <div class="absolute top-3 right-4 flex gap-3 z-30">
                            <!-- Edit Icon (Pencil) -->
                            <a href="/community-edit?community={{ $community->slug }}"
                                class="p-2 text-white bg-black/40 rounded-full backdrop-blur-sm hover:bg-black/60 transition flex items-center justify-center"
                                aria-label="Edit Community">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 20h9M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z" />
                                </svg>
                            </a>

                            <form class="delete-community-form" method="POST"
                                action="/communities/{{ $community->slug }}" data-slug="{{ $community->slug }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmDelete(this)"
                                    class="p-2 text-white bg-red-800/40 rounded-full backdrop-blur-sm hover:bg-red-900/60 transition flex items-center justify-center"
                                    aria-label="Delete Community">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
                                    </svg>
                                </button>
                            </form>

                        </div>
                    @endif

                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center text-center px-6 z-20 pointer-events-none">
                        <div class="bg-white/20 backdrop-blur-md px-6 py-3 rounded-2xl shadow-md inline-block">
                            <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-white drop-shadow-lg">
                                {{ $community->name }}
                            </h1>

                            @if ($community->description)
                                <p class="text-white/90 text-sm italic mt-1">
                                    {{ $community->description }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div
                        class="absolute bottom-0 left-0 right-0 bg-gradient-to-r from-clear-950/50 via-blue-800/40 to-clear-950/50 backdrop-blur-md border-t border-white/10 py-2">
                        <div
                            class="flex flex-wrap items-center justify-center gap-10 text-sm text-white/90 font-medium">

                            <span class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                @php
                                    $memberCount = $community?->memberships->count() ?? 0;
                                @endphp
                                <strong>{{ $memberCount }}</strong> {{ $memberCount === 1 ? 'member' : 'members' }}
                            </span>


                            <span class="text-gray-400">|</span>
                            <span class="flex items-center gap-2">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                                <strong>{{ $community?->memberships->where('status', 'active')->count() ?? 0 }}</strong>
                                active
                            </span>
                            <span class="text-gray-400">|</span>
                            <span class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                @php
                                    $eventCount = $community?->events->count() ?? 0;
                                @endphp
                                <strong>{{ $eventCount }}</strong> {{ $eventCount === 1 ? 'event' : 'events' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif


            <!-- Page Content -->
            <div class="flex-1 p-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Toast container -->
    <div id="toast" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>

    <!-- Confirmation modal -->
    <div id="confirm-toast"
        class="hidden fixed top-4 right-4 z-50 max-w-sm w-full sm:w-auto border border-gray-300 bg-white text-gray-800 px-4 py-3 text-sm transform transition-transform duration-300 ease-out -translate-y-10 opacity-0 flex flex-col gap-3">
        <!-- Message -->
        <p id="confirm-message" class="text-sm text-gray-800">Are you sure?</p>

        <!-- Action buttons -->
        <div class="flex justify-center gap-2">
            <button id="confirm-yes"
                class="px-3 py-1 bg-blue-600 text-white text-sm hover:bg-blue-700 rounded">Publish</button>
            <button id="confirm-no"
                class="px-3 py-1 bg-gray-200 text-gray-700 text-sm hover:bg-gray-300 rounded">Cancel</button>
        </div>
    </div>


    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToastify("{{ session('success') }}", 'success');
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToastify("{{ session('error') }}", 'error');
            });
        </script>
    @endif

    <script>
        function confirmLeaveCommunity(button, communityName) {
            showConfirmToast(
                `Are you sure you want to leave ${communityName}?`,
                async () => {
                    const form = button.closest('form');
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            showToastify(`Successfully left ${communityName}`, 'success');
                            // Short delay to show the success message before redirect
                            setTimeout(() => {
                                window.location.href = '/dashboard';
                            }, 1000);
                        } else {
                            const data = await response.json();
                            showToastify(data.message || 'Failed to leave community', 'error');
                        }
                    } catch (err) {
                        console.error(err);
                        showToastify('Something went wrong', 'error');
                    }
                },
                'bg-red-400 hover:bg-red-500',
                'Leave'
            );
        }

        function showToastify(message, type = 'info', duration = 4000) {
            Toastify({
                text: message,
                duration: duration,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? "#4ade80" : // Tailwind green-400
                    type === 'error' ? "#f87171" : // Tailwind red-400
                    type === 'confirm' ? "#ffffff" : // White for confirm
                    "#e5e7eb", // Tailwind gray-200
                stopOnFocus: true
            }).showToast();
        }

        function showConfirmToast(message, onConfirm, yesStyle = 'bg-blue-600 hover:bg-blue-700', yesLabel = 'Yes') {
            const toast = document.getElementById('confirm-toast');
            const msg = document.getElementById('confirm-message');
            const yes = document.getElementById('confirm-yes');
            const no = document.getElementById('confirm-no');

            msg.textContent = message;
            yes.textContent = yesLabel;

            // Apply styles
            yes.className = `px-3 py-1 text-white text-sm rounded ${yesStyle}`;
            no.className = 'px-3 py-1 bg-gray-200 text-gray-700 text-sm hover:bg-gray-300 rounded';

            // Reset to hidden state before triggering animation
            toast.classList.remove('hidden');
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('-translate-y-10', 'opacity-0');

            // Trigger reflow and then animate in
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.classList.remove('-translate-y-10', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                });
            });

            const cleanup = () => {
                toast.classList.add('hidden');
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('-translate-y-10', 'opacity-0');
            };

            yes.onclick = () => {
                cleanup();
                if (typeof onConfirm === 'function') onConfirm();
            };
            no.onclick = cleanup;
        }


        function confirmDelete(button) {
            const form = button.closest('.delete-community-form');
            const slug = form.dataset.slug;

            showConfirmToast(
                'Are you sure you want to delete this community?',
                async () => {
                        try {
                            const res = await fetch(`/communities/${slug}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                    'Content-Type': 'application/json'
                                }
                            });

                            if (res.ok) {
                                window.location.href = '/dashboard';
                            } else {
                                const data = await res.json().catch(() => ({}));
                                showToastify(data.message || 'Failed to delete community.', 'error');
                            }
                        } catch (err) {
                            console.error(err);
                            showToastify('Something went wrong.', 'error');
                        }
                    },
                    'bg-red-400 hover:bg-red-500',
                    'Delete'
            );
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const setupDropdown = (btnId, menuId) => {
                const btn = document.getElementById(btnId);
                const menu = document.getElementById(menuId);
                let isLockedOpen = false; // track click-to-stay state

                if (!btn || !menu) return;

                // Toggle on click (lock open)
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    isLockedOpen = !isLockedOpen;
                    menu.classList.toggle("hidden", !isLockedOpen);
                });

                // Show on hover (only if not locked open)
                btn.addEventListener("mouseenter", () => {
                    if (!isLockedOpen) menu.classList.remove("hidden");
                });
                btn.addEventListener("mouseleave", () => {
                    if (!isLockedOpen) menu.classList.add("hidden");
                });

                // Also handle hovering over the menu itself
                menu.addEventListener("mouseenter", () => {
                    if (!isLockedOpen) menu.classList.remove("hidden");
                });
                menu.addEventListener("mouseleave", () => {
                    if (!isLockedOpen) menu.classList.add("hidden");
                });

                // Close if clicked outside
                document.addEventListener("click", (e) => {
                    if (!menu.contains(e.target) && !btn.contains(e.target)) {
                        menu.classList.add("hidden");
                        isLockedOpen = false;
                    }
                });
            };

            setupDropdown("notif-btn", "notif-dropdown");
            setupDropdown("user-menu-btn", "user-menu-dropdown");

            // Sidebar search (filter existing list)
            const searchBox = document.getElementById("search-box");
            const communityList = document.getElementById("community-list");
            const communityItems = communityList?.querySelectorAll(".community-item");
            searchBox?.addEventListener("input", () => {
                let visibleCount = 0;
                communityItems?.forEach(item => {
                    if (item.textContent.toLowerCase().includes(searchBox.value.toLowerCase()
                            .trim())) {
                        item.style.display = "";
                        visibleCount++;
                    } else item.style.display = "none";
                });

                let noCom = document.getElementById("no-communities");
                if (!noCom && visibleCount === 0) {
                    noCom = document.createElement("p");
                    noCom.id = "no-communities";
                    noCom.className = "text-gray-500 text-sm";
                    noCom.textContent = "No communities found.";
                    communityList?.after(noCom);
                } else if (noCom && visibleCount > 0) noCom.remove();
            });

            // Live global search dropdown for public communities (joinable)
            const dropdown = document.getElementById('search-dropdown');
            const results = document.getElementById('search-results');
            const empty = document.getElementById('search-empty');
            let searchTimer = null;

            async function fetchSearch(q) {
                if (!q || q.trim().length === 0) {
                    dropdown.classList.add('hidden');
                    results.innerHTML = '';
                    empty.classList.add('hidden');
                    return;
                }

                try {
                    const resp = await fetch(`/communities/search?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    if (!resp.ok) throw new Error('Search failed');
                    const data = await resp.json();

                    results.innerHTML = '';
                    if (!data || data.length === 0) {
                        empty.classList.remove('hidden');
                        dropdown.classList.remove('hidden');
                        return;
                    }

                    empty.classList.add('hidden');
                    data.forEach(c => {
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between p-2 hover:bg-gray-50';

                        // Decide button state based on membership
                        let btnHtml = '';
                        if (c.membership) {
                            const status = c.membership.status;
                            if (status === 'active') {
                                btnHtml =
                                    `<button class="btn-state text-xs px-2 py-1 rounded bg-gray-200 text-gray-500" disabled>Joined</button>`;
                            } else if (status === 'pending') {
                                btnHtml =
                                    `<button class="btn-state text-xs px-2 py-1 rounded bg-gray-100 text-gray-500" disabled>Request sent</button>`;
                            } else if (status === 'banned') {
                                btnHtml =
                                    `<button class="btn-state text-xs px-2 py-1 rounded bg-red-200 text-red-700" disabled>Banned</button>`;
                            } else {
                                btnHtml =
                                    `<button class="join-btn bg-blue-500 text-white text-xs px-2 py-1 rounded" data-slug="${c.slug}">Join</button>`;
                            }
                        } else {
                            btnHtml =
                                `<button class="join-btn bg-blue-500 text-white text-xs px-2 py-1 rounded" data-slug="${c.slug}">Join</button>`;
                        }

                        item.innerHTML = `
                            <div class="flex-1 pr-2">
                                <div class="font-medium text-sm">${escapeHtml(c.name)}</div>
                                <div class="text-xs text-gray-500 truncate">${escapeHtml(c.description || '')}</div>
                            </div>
                            <div class="flex-shrink-0">
                                ${btnHtml}
                            </div>
                        `;

                        // Slightly fade the whole item if membership exists
                        if (c.membership) item.classList.add('opacity-70');

                        results.appendChild(item);
                    });

                    dropdown.classList.remove('hidden');
                } catch (err) {
                    console.error(err);
                }
            }

            function escapeHtml(s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            searchBox?.addEventListener('input', (e) => {
                clearTimeout(searchTimer);
                const q = e.target.value;
                searchTimer = setTimeout(() => fetchSearch(q), 200);
            });

            // Click to join from results
            document.addEventListener('click', async (e) => {
                const btn = e.target.closest && e.target.closest('.join-btn');
                if (!btn) return;

                const slug = btn.dataset.slug;
                btn.disabled = true;
                btn.textContent = 'Joining...';

                try {
                    const token = '{{ csrf_token() }}';
                    const res = await fetch(`/communities/${slug}/join`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({})
                    });

                    if (res.ok) {
                        btn.textContent = 'Joined';
                        btn.classList.remove('bg-blue-500');
                        btn.classList.add('bg-green-500');

                        const list = document.getElementById('community-list');
                        if (list) {
                            const li = document.createElement('li');
                            li.className = 'community-item';
                            li.innerHTML =
                                `<a href="/dashboard?community=${slug}" class="block px-2 py-1 transition hover:bg-blue-50 text-gray-800"><span class="font-medium text-sm">${escapeHtml(btn.closest('div').querySelector('.font-medium')?.textContent || '')}</span></a>`;
                            list.appendChild(li);
                        }

                        setTimeout(() => {
                            dropdown.classList.add('hidden');
                        }, 500);
                    } else {
                        const data = await res.json().catch(() => ({}));
                        showToastify(data.message || 'Failed to join community', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Join';
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Something went wrong', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Join';
                }
            });


            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!document.getElementById('search-dropdown')?.contains(e.target) && e.target !==
                    searchBox) {
                    dropdown.classList.add('hidden');
                }
            });

            // Handle community delete and redirect manually
            document.querySelectorAll('.delete-community-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const slug = form.dataset.slug;

                    showConfirmToast('Are you sure you want to delete this community?',
                        async () => {
                            try {
                                const res = await fetch(`/communities/${slug}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });

                                if (res.ok) {
                                    window.location.href = '/dashboard';
                                } else {
                                    const data = await res.json().catch(() => ({}));
                                    showToastify(data.message ||
                                        'Failed to delete community.', 'error');
                                }
                            } catch (err) {
                                console.error(err);
                                showToastify(
                                    'Something went wrong deleting this community.',
                                    'error');
                            }
                        });
                });
            });



        });

        // Modal toggle
        function toggleModal() {
            document.getElementById('community-modal')?.classList.toggle('hidden');
        }

        // Tab switch
        function switchTab(tab) {
            document.getElementById('tab-create')?.classList.add('hidden');
            document.getElementById('tab-join')?.classList.add('hidden');
            document.getElementById(tab)?.classList.remove('hidden');
        }

        // === Image Preview ===
        const bannerInput = document.getElementById('banner-upload');
        const bannerPreviewContainer = document.getElementById('banner-preview-container');
        const bannerPreview = document.getElementById('banner-preview');

        if (bannerInput) {
            bannerInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        bannerPreview.src = event.target.result;
                        bannerPreviewContainer.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    bannerPreviewContainer.classList.add('hidden');
                    bannerPreview.src = '';
                }
            });
        }
    </script>
</body>

</html>
