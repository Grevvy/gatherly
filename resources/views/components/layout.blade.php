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
            <div class="mb-6">
                <input id="search-box" type="text" placeholder="Search communities..."
                    class="w-full px-3 py-2 border text-sm focus:outline-none focus:ring focus:ring-blue-300"
                    value="{{ request('search') }}" />
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

                @php
                    use App\Models\Community;
                    use Illuminate\Support\Facades\Auth;

                    $communities = [];
                    if (Auth::check()) {
                        $communities = Community::whereHas('members', function ($query) {
                            $query->where('user_id', Auth::id());
                        })->get();
                    }
                @endphp

                @if (!empty($communities) && count($communities) > 0)
                    <ul id="community-list" class="space-y-1">
                        @foreach ($communities as $community)
                            @php $active = request('community') === $community->slug; @endphp
                            <li class="community-item">
                                <a href="/dashboard?community={{ $community->slug }}"
                                    class="block px-2 py-1 rounded transition {{ $active ? 'bg-blue-100 text-blue-600 font-medium' : 'hover:bg-blue-50 text-gray-800' }}">
                                    <span class="font-medium text-sm">{{ $community->name }}</span>
                                    <span class="text-xs text-gray-400 block">
                                        {{ ucfirst($community->visibility) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p id="no-communities" class="text-gray-500 text-sm">No communities found.</p>
                @endif
            </div>
        </aside>

        <!-- Main Dashboard -->
        <main class="flex-1 flex flex-col bg-gray-50">
            <!-- Top Tabs + Right Controls -->
            <div class="flex items-center justify-between bg-white border-b px-6">
                <div class="flex space-x-6">
                    <a href="/dashboard"
                        class="py-3 {{ request()->routeIs('dashboard') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Feed
                    </a>
                    <a href="/events"
                        class="py-3 {{ request()->is('events') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Events
                    </a>
                    <a href="/messages"
                        class="py-3 {{ request()->is('messages') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Messages
                        <span class="ml-1 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">0</span>
                    </a>
                    <a href="/members"
                        class="py-3 {{ request()->is('members') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Members
                    </a>
                    <a href="/gallery"
                        class="py-3 {{ request()->is('gallery') ? 'border-b-2 border-blue-600 text-blue-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                        Photo Gallery
                    </a>

                    @if (in_array(auth()->user()->role, ['owner', 'admin', 'moderator']))
                        <a href="#"
                            class="py-3 {{ request()->is('dashboard-extra') ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-800' }}">
                            Dashboard
                        </a>
                    @endif

                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="notif-btn" class="relative p-2 text-gray-600 hover:text-gray-800">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span id="notif-badge"
                                class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 flex items-center justify-center rounded-full"></span>
                        </button>
                        <div id="notif-dropdown"
                            class="hidden absolute right-0 mt-2 w-64 bg-white border shadow-lg z-50 rounded-md overflow-hidden">
                            <div class="p-4 text-sm text-gray-500 text-center">No notifications</div>
                        </div>
                    </div>

                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg">
                            <div
                                class="w-11 h-11 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
                        </button>

                        <div id="user-menu-dropdown"
                            class="hidden absolute right-0 mt-2 w-56 bg-white border shadow-lg z-50 rounded-md overflow-hidden divide-y divide-gray-100">
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

            <!-- Page Content -->
            <div class="flex-1 p-6">
                {{ $slot ?? '' }}
            </div>
        </main>
    </div>

    <!-- Community Modal -->
    <div id="community-modal"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96 shadow-lg">
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
                    <input type="text" name="name" required class="w-full p-2 border rounded mb-3" />

                    <label class="block text-sm font-semibold mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full p-2 border rounded mb-3"></textarea>

                    <label class="block text-sm font-semibold mb-1">Visibility</label>
                    <select name="visibility" class="w-full p-2 border rounded mb-3">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                        <option value="hidden">Hidden</option>
                    </select>

                    <label class="block text-sm font-semibold mb-1">Join Policy</label>
                    <select name="join_policy" class="w-full p-2 border rounded mb-3">
                        <option value="open">Open</option>
                        <option value="request">Request</option>
                        <option value="invite">Invite Only</option>
                    </select>

                    <button type="submit" class="bg-blue-600 text-white w-full py-2 rounded hover:bg-blue-700">
                        Create Community
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const toggleDropdown = (btnId, menuId) => {
                document.getElementById(btnId)?.addEventListener("click", () => {
                    document.getElementById(menuId)?.classList.toggle("hidden");
                });
            };
            toggleDropdown("notif-btn", "notif-dropdown");
            toggleDropdown("user-menu-btn", "user-menu-dropdown");

            // Community Create Form
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
                            'Accept': 'application/json',
                        },
                        body: data,
                    });
                    const result = await res.json();
                    if (res.ok) {
                        // Remove alert
                        form.reset();
                        toggleModal();

                        const list = document.getElementById('community-list');
                        if (list) {
                            const li = document.createElement('li');
                            li.className = 'community-item';

                            const a = document.createElement('a');
                            a.href = `/dashboard?community=${result.slug}`;
                            a.className = 'block px-2 py-1 rounded hover:bg-blue-50 text-gray-800';

                            const spanName = document.createElement('span');
                            spanName.className = 'font-medium text-sm';
                            spanName.textContent = result.name;

                            const spanVis = document.createElement('span');
                            spanVis.className = 'text-xs text-gray-400 block';
                            spanVis.textContent = result.visibility.charAt(0).toUpperCase() + result
                                .visibility.slice(1);

                            a.appendChild(spanName);
                            a.appendChild(spanVis);
                            li.appendChild(a);
                            list.appendChild(li);

                            // Remove "No communities" message if present
                            const noCom = document.getElementById('no-communities');
                            if (noCom) noCom.remove();
                        }
                    } else {
                        alert(result.message || 'Failed to create community');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Something went wrong');
                }
            });

            // search in sidebar
            const searchBox = document.getElementById("search-box");
            const communityList = document.getElementById("community-list");
            const communityItems = communityList?.querySelectorAll(".community-item");

            searchBox?.addEventListener("input", () => {
                const term = searchBox.value.toLowerCase().trim();

                let visibleCount = 0;
                communityItems?.forEach(item => {
                    const name = item.textContent.toLowerCase();
                    if (name.includes(term)) {
                        item.style.display = "";
                        visibleCount++;
                    } else {
                        item.style.display = "none";
                    }
                });

                // Show/hide "No communities" message
                let noCom = document.getElementById("no-communities");
                if (!noCom && visibleCount === 0) {
                    noCom = document.createElement("p");
                    noCom.id = "no-communities";
                    noCom.className = "text-gray-500 text-sm";
                    noCom.textContent = "No communities found.";
                    communityList?.after(noCom);
                } else if (noCom && visibleCount > 0) {
                    noCom.remove();
                }
            });

        });

        function toggleModal() {
            document.getElementById('community-modal').classList.toggle('hidden');
        }

        function switchTab(tab) {
            document.getElementById('tab-create').classList.add('hidden');
            document.getElementById('tab-join')?.classList.add('hidden');
            document.getElementById(tab).classList.remove('hidden');
        }
    </script>

</body>

</html>
