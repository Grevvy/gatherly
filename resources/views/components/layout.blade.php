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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <input type="hidden" name="_token" id="csrf-token" value="{{ csrf_token() }}">

    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside class="w-72 bg-white shadow-lg p-4 flex flex-col">
            <div class="text-2xl font-bold text-blue-600 mb-6">Gatherly</div>

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

                                <li class="community-item">
                                    <a href="/dashboard?community={{ $c->slug }}"
                                        class="block px-2 py-1 transition {{ $active ? 'bg-blue-100 text-blue-600 font-medium' : 'hover:bg-blue-50 text-gray-800' }}">
                                        <span class="font-medium text-sm">{{ $c->name }}</span>
                                        <span class="text-xs text-gray-400 block">{{ ucfirst($c->visibility) }}</span>
                                    </a>
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

                    <a href="/events{{ $slug ? '?community=' . $slug : '' }}"
                        class="py-3 {{ request()->is('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Events
                    </a>

                    <a href="/messages{{ $slug ? '?community=' . $slug : '' }}"
                        class="py-3 {{ request()->is('messages') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Messages
                    </a>

                    <a href="/members{{ $slug ? '?community=' . $slug : '' }}"
                        class="py-3 {{ request()->is('members') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Members
                    </a>

                    <a href="/gallery{{ $slug ? '?community=' . $slug : '' }}"
                        class="py-3 {{ request()->is('gallery') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Photo Gallery
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
                <div class="relative w-full max-w-6xl h-60 overflow-hidden mx-auto community-banner-container">
                    <!-- Banner Image -->
                    <img src="{{ asset($community->banner_image ?? 'images/default-banner.jpg') }}"
                        alt="Community Banner" class="w-full h-full object-cover">

                    <div class="absolute inset-0 bg-gradient-to-b from-black/10 via-black/40 to-black/60"></div>
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
                        <h1
                            class="max-w-5xl text-5xl sm:text-6xl md:text-6xl font-display font-bold tracking-tight leading-tight text-white drop-shadow-[0_2px_6px_rgba(255,255,255,0.4)] shadow-white/20 animate-fade-in">
                            {{ $community->name }}
                        </h1>

                        @if ($community->description)
                            <p
                                class="mt-4 max-w-xl text-sm sm:text-base text-gray-100 leading-snug italic px-2 py-1 bg-gray-900/10 rounded-sm backdrop-blur-[0.5px] animate-fade-in delay-200">
                                {{ $community->description }}
                            </p>
                        @endif
                    </div>

                    <div class="absolute bottom-0 left-0 right-0 bg-black/15 backdrop-blur-sm">
                        <div
                            class="flex flex-wrap items-center justify-center gap-4 py-1.5 text-xs xs:text-xs text-gray-300">
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

    <script>
        function showToast(message, type = 'alert', duration = 3000, buttons = []) {
            const container = document.getElementById('toast');
            const toast = document.createElement('div');

            // Tailwind background + label
            let bgClass = '';
            let label = '';
            let textColor = '';
            if (type === 'success') {
                bgClass = 'bg-green-100';
                label = 'Success';
                textColor = 'text-green-800';
            } else if (type === 'confirm') {
                bgClass = 'bg-white';
                label = 'Confirmation';
                textColor = 'text-black';
            } else {
                bgClass = 'bg-red-100';
                label = 'Error';
                textColor = 'text-red-800';
            }

            toast.className = `
        flex flex-col gap-2 p-4 rounded-lg shadow-lg font-sans
        min-w-[240px] max-w-[360px] ${bgClass} relative border border-gray-300
    `;

            // Header row
            const header = document.createElement('div');
            header.className = 'flex justify-between items-center';

            const title = document.createElement('span');
            title.textContent = label;
            title.className = 'font-semibold text-sm';

            const close = document.createElement('button');
            close.textContent = '✕';
            close.className = 'text-gray-500 hover:text-gray-700 text-sm';
            close.onclick = () => {
                if (toast.parentNode) container.removeChild(toast);
            };

            header.appendChild(title);
            header.appendChild(close);
            toast.appendChild(header);

            // Message
            const text = document.createElement('div');
            text.textContent = message;
            text.className = 'text-sm';
            toast.appendChild(text);

            // Buttons
            if (buttons.length) {
                const btnContainer = document.createElement('div');
                btnContainer.className = 'flex justify-center gap-4 mt-4';

                buttons.forEach(btn => {
                    const b = document.createElement('button');
                    b.textContent = btn.text;

                    if (btn.style) {
                        b.className = `text-sm rounded px-4 py-2 cursor-pointer transition ${btn.style}`;
                    } else if (btn.type === 'yes') {
                        b.className = `
                text-sm text-white bg-red-600 rounded px-4 py-2 cursor-pointer
                hover:bg-red-700 transition
            `;
                    } else {
                        b.className = `
                text-sm text-gray-700 bg-gray-200 rounded px-4 py-2 cursor-pointer
                hover:bg-gray-300 transition
            `;
                    }

                    b.onclick = () => {
                        if (typeof btn.onClick === 'function') btn.onClick();
                        if (toast.parentNode) container.removeChild(toast);
                    };

                    btnContainer.appendChild(b);
                });

                toast.appendChild(btnContainer);
            }

            container.appendChild(toast);

            // Auto-dismiss if no buttons
            if (!buttons.length) {
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                    setTimeout(() => {
                        if (toast.parentNode) container.removeChild(toast);
                    }, 500);
                }, duration || 6000);
            }
        }


        function confirmDelete(button) {
            const form = button.closest('.delete-community-form');
            const slug = form.dataset.slug;

            showToast(`Are you sure you want to delete this community?`, 'confirm', 0, [{
                    text: "Delete",
                    type: 'yes',
                    onClick: async () => {
                        try {
                            const res = await fetch(`/communities/${slug}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')
                                        .value,
                                    'Content-Type': 'application/json'
                                }
                            });

                            if (res.ok) {
                                // ✅ Redirect to dashboard after successful delete
                                window.location.href = '/dashboard';
                            } else {
                                const data = await res.json().catch(() => ({}));
                                showToast(data.message || 'Failed to delete community.', 'error');
                            }
                        } catch (err) {
                            console.error(err);
                            showToast('Something went wrong.', 'error');
                        }
                    }
                },
                {
                    text: 'Cancel',
                    type: 'no',
                    onClick: () => {
                        // Do nothing
                    }
                }
            ]);
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

                        // Optionally add to side list
                        const list = document.getElementById('community-list');
                        if (list) {
                            const li = document.createElement('li');
                            li.className = 'community-item';
                            li.innerHTML =
                                `<a href="/dashboard?community=${slug}" class="block px-2 py-1 transition hover:bg-blue-50 text-gray-800"><span class="font-medium text-sm">${escapeHtml(btn.closest('div').querySelector('.font-medium')?.textContent || '')}</span></a>`;
                            list.appendChild(li);
                        }

                        // Hide dropdown after join
                        setTimeout(() => {
                            dropdown.classList.add('hidden');
                        }, 500);
                    } else {
                        const data = await res.json().catch(() => ({}));
                        const msg = data.message || 'Failed to join community';
                        showToast(msg, 'error');

                        btn.disabled = false;
                        btn.textContent = 'Join';
                    }
                } catch (err) {
                    console.error(err);
                    showToast('Something went wrong', 'error');

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
                    // Remove confirm entirely
                    // Proceed with deletion
                    showToast('Community deleted', 'success');


                    const slug = form.dataset.slug;

                    try {
                        const res = await fetch(`/communities/${slug}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });

                        if (res.ok) {
                            // Redirect to dashboard after successful delete
                            window.location.href = '/dashboard';
                        } else {
                            const data = await res.json().catch(() => ({}));
                            const msg = data.message || 'Failed to delete community.';
                            showToast(msg, 'error');

                        }
                    } catch (err) {
                        console.error(err);
                        showToast('Something went wrong deleting this community.', 'error');

                    }
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
