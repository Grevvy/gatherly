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
                <div id="search-dropdown" class="hidden absolute left-0 mt-1 w-full bg-white border shadow-lg z-50 rounded-md overflow-hidden">
                    <div id="search-results" class="divide-y divide-gray-100 max-h-64 overflow-y-auto"></div>
                    <div id="search-empty" class="p-2 text-sm text-gray-500 hidden">No communities found.</div>
                </div>
            </div>

            <!-- My Communities -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-500">MY COMMUNITIES</h3>
                    <button id="add-community-btn" onclick="toggleModal()"
                        class="flex items-center justify-center w-6 h-6 text-blue-600 text-sm font-medium hover:text-blue-800 rounded-full border border-blue-600">
                        +
                    </button>
                </div>

                <div id="community-list" class="space-y-1">
                    @if (!empty($communities) && count($communities) > 0)
                        <ul class="space-y-1">
                            @foreach ($communities as $c)
                                @php $active = request('community') === $c->slug; @endphp
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
                    <a href="/dashboard"
                        class="py-3 {{ request()->routeIs('dashboard') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Feed</a>
                    <a href="/events"
                        class="py-3 {{ request()->is('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Events</a>
                    <a href="/messages"
                        class="py-3 {{ request()->is('messages') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Messages</a>
                    <a href="/members"
                        class="py-3 {{ request()->is('members') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Members</a>
                    <a href="/gallery"
                        class="py-3 {{ request()->is('gallery') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">Photo
                        Gallery</a>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button id="notif-btn" class="p-2 hover:bg-gray-100 relative mt-2">
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

                    <div class="absolute inset-0 bg-black bg-opacity-40"></div>

                    <!-- Banner Content -->
                    <div class="absolute bottom-4 left-4 flex items-start justify-between w-[95%] text-white">
                        <div>
                            <h2 class="text-2xl font-bold">{{ $community->name }}</h2>
                            <p class="text-gray-200 text-sm">{{ $community->description ?? '' }}</p>

                            <div class="flex gap-6 text-sm mt-2 text-gray-200 ml-auto">
                                <span>{{ $community->memberships->count() }} members</span>
                                <span>{{ $community->memberships->where('status', 'active')->count() }} active</span>
                                <span>Owner: {{ $community->owner->name ?? 'N/A' }}</span>
                            </div>
                        </div>

                        @php
                            $userId = auth()->id();
                            $isOwner = $community->owner_id === $userId;
                            $isAdmin = $community->memberships->contains(
                                fn($m) => $m->user_id === $userId && $m->role === 'admin' && $m->status === 'active',
                            );
                        @endphp

                        @if ($isOwner || $isAdmin)
                            <div class="absolute bottom-0 right-1 flex gap-2">
                                <a href="/community-edit?community={{ $community->slug }}"
                                    class="inline-flex items-center justify-center bg-gray-800/50 text-white px-2 py-1.5 rounded-full text-[10px] font-medium shadow-sm hover:bg-gray-900/40 transition-all duration-150">
                                    Edit Community
                                </a>

                                <form class="delete-community-form" data-slug="{{ $community->slug }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center bg-red-800/50 text-white px-2 py-1.5 rounded-full text-[10px] font-medium shadow-sm hover:bg-red-900/40 transition-all duration-150">
                                        Delete Community
                                    </button>
                                </form>
                            </div>
                        @endif


                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <div class="flex-1 p-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Community Modal -->
    <div id="community-modal"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 w-96 shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Community</h2>
                <button onclick="toggleModal()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>

            <div class="flex mb-4 border-b">
                <button class="flex-1 py-2 text-sm font-semibold border-b-2 border-blue-500"
                    onclick="switchTab('tab-create')">Create</button>
            </div>

            <div id="tab-create">
                <form id="create-community-form">
                    @csrf
                    <label class="block text-sm font-semibold mb-1">Community Name</label>
                    <input type="text" name="name" required class="w-full p-2 border mb-3" />

                    <label class="block text-sm font-semibold mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full p-2 border mb-3"></textarea>

                    <label class="block text-sm font-semibold mb-1">Banner Image</label>
                    <div class="mb-3">
                        <input id="banner-upload" type="file" name="banner_image" accept="image/*"
                            class="w-full p-2 border mb-2">
                        <div id="banner-preview-container" class="hidden">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img id="banner-preview" src="" alt="Banner Preview"
                                class="w-full h-32 object-cover border border-gray-200 shadow-sm">
                        </div>
                    </div>


                    <label class="block text-sm font-semibold mb-1">Visibility</label>
                    <select name="visibility" class="w-full p-2 border mb-3">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                        <option value="hidden">Hidden</option>
                    </select>

                    <label class="block text-sm font-semibold mb-1">Join Policy</label>
                    <select name="join_policy" class="w-full p-2 border mb-3">
                        <option value="open">Open</option>
                        <option value="request">Request</option>
                        <option value="invite">Invite Only</option>
                    </select>

                    <button type="submit" class="bg-blue-500 text-white w-full py-2 hover:bg-blue-600">
                        Create Community
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
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

            // Create Community
            const form = document.getElementById('create-community-form');
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const data = new FormData(form);
                const token = data.get('_token');

                try {
                    const res = await fetch('/communities', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: data
                    });
                    const result = await res.json();
                    if (res.ok) {
                        form.reset();
                        toggleModal();

                        const list = document.getElementById('community-list');
                        if (list) {
                            // Remove "No communities" if exists
                            const noCom = document.getElementById('no-communities');
                            if (noCom) noCom.remove();

                            // Create new community item
                            const li = document.createElement('li');
                            li.className = 'community-item';
                            li.innerHTML = `
                    <a href="/dashboard?community=${result.slug}" class="block px-2 py-1 rounded transition hover:bg-blue-50 text-gray-800">
                        <span class="font-medium text-sm">${result.name}</span>
                        <span class="text-xs text-gray-400 block">${result.visibility.charAt(0).toUpperCase() + result.visibility.slice(1)}</span>
                    </a>
                `;
                            list.appendChild(li);

                        }
                    } else {
                        alert(result.message || 'Failed to create community');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Something went wrong');
                }
            });


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
                    const resp = await fetch(`/communities/search?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
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
                                btnHtml = `<button class="btn-state text-xs px-2 py-1 rounded bg-gray-200 text-gray-500" disabled>Joined</button>`;
                            } else if (status === 'pending') {
                                btnHtml = `<button class="btn-state text-xs px-2 py-1 rounded bg-gray-100 text-gray-500" disabled>Request sent</button>`;
                            } else if (status === 'banned') {
                                btnHtml = `<button class="btn-state text-xs px-2 py-1 rounded bg-red-200 text-red-700" disabled>Banned</button>`;
                            } else {
                                btnHtml = `<button class="join-btn bg-blue-500 text-white text-xs px-2 py-1 rounded" data-slug="${c.slug}">Join</button>`;
                            }
                        } else {
                            btnHtml = `<button class="join-btn bg-blue-500 text-white text-xs px-2 py-1 rounded" data-slug="${c.slug}">Join</button>`;
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
                            li.innerHTML = `<a href="/dashboard?community=${slug}" class="block px-2 py-1 transition hover:bg-blue-50 text-gray-800"><span class="font-medium text-sm">${escapeHtml(btn.closest('div').querySelector('.font-medium')?.textContent || '')}</span></a>`;
                            list.appendChild(li);
                        }

                        // Hide dropdown after join
                        setTimeout(() => {
                            dropdown.classList.add('hidden');
                        }, 500);
                    } else {
                        const data = await res.json().catch(() => ({}));
                        alert(data.message || 'Failed to join community');
                        btn.disabled = false;
                        btn.textContent = 'Join';
                    }
                } catch (err) {
                    console.error(err);
                    alert('Something went wrong');
                    btn.disabled = false;
                    btn.textContent = 'Join';
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!document.getElementById('search-dropdown')?.contains(e.target) && e.target !== searchBox) {
                    dropdown.classList.add('hidden');
                }
            });

            // Handle community delete and redirect manually
            document.querySelectorAll('.delete-community-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to delete this community?')) return;

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
                            alert(data.message || 'Failed to delete community.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Something went wrong deleting this community.');
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
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to delete this community?')) return;

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
                            alert(data.message || 'Failed to delete community.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Something went wrong deleting this community.');
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

=======
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
>>>>>>> fe4d4f00063207077feb22f88d97cbc1abe6f66e
</body>

</html>
