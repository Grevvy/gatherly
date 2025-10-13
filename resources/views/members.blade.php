<x-layout>
    <div class="bg-gray-50 min-h-screen px-6 py-8">

        <!-- Header -->
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900">Community Members</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $community->memberships->count() }} members in {{ $community->name }}
            </p>

            <!-- Search -->
            <div class="relative mt-4 w-full md:w-1/2">
                <input id="memberSearch" type="text" placeholder="Search members..."
                    class="w-full border border-gray-300 pl-4 pr-10 py-2 text-sm rounded-lg focus:ring focus:ring-indigo-200 focus:outline-none">
                <svg class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                </svg>
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-3 mt-5 flex-wrap">
                <button data-filter="all"
                    class="tab-button active px-4 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700">
                    All Members ({{ $community->memberships->count() }})
                </button>
                <button data-filter="online"
                    class="tab-button px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                    Online ({{ $community->memberships->where('status', 'active')->count() }})
                </button>
                <button data-filter="staff"
                    class="tab-button px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                    Staff ({{ $community->memberships->whereIn('role', ['admin','moderator'])->count() }})
                </button>
            </div>
        </div>

        <!-- Members Grid -->
        <div id="membersGrid" class="max-w-7xl mx-auto grid gap-6 md:grid-cols-2 lg:grid-cols-3 mt-8">
            @forelse($community->memberships as $member)
                @php
                    $user = $member->user;
                    $joined = $member->created_at ? $member->created_at->format('F Y') : '—';
                    $isStaff = in_array($member->role, ['admin','moderator']);
                    $isOnline = $member->status === 'active';
                @endphp

                <div class="member-card bg-white shadow-sm rounded-xl p-5 flex flex-col justify-between border hover:shadow-md transition"
                    data-role="{{ $member->role }}" 
                    data-online="{{ $isOnline ? 'true' : 'false' }}"
                    data-name="{{ strtolower($user->name) }}">
                    
                    <!-- Top Row -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                <span class="text-gray-600 font-semibold">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $user->name }}</h3>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        @if ($member->role === 'owner')
                            <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">Owner</span>
                        @elseif ($member->role === 'admin')
                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Admin</span>
                        @elseif ($member->role === 'moderator')
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">Moderator</span>
                        @endif
                    </div>

                    <!-- Bio Placeholder -->
                    <p class="text-sm text-gray-700 mb-3 line-clamp-3">
                        {{ $user->bio ?? 'No bio available.' }}
                    </p>

                    <!-- Example Tags (replace with real tags later if your backend has them) -->
                    @if (!empty($user->tags))
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach ($user->tags as $tag)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <!-- Footer -->
                    <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
                        <span>Joined {{ $joined }}</span>
                        @if ($isOnline)
                            <span class="flex items-center gap-1 text-green-600">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span> Online
                            </span>
                        @else
                            <span class="flex items-center gap-1 text-gray-400">
                                <span class="w-2 h-2 rounded-full bg-gray-300"></span> Offline
                            </span>
                        @endif
                    </div>

                    <div class="flex gap-3">
                        <a href="/messages"
                            class="flex-1 px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50">
                            Message
                        </a>
                        <a href="mailto:{{ $user->email }}"
                            class="flex-1 px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50">
                            Email
                        </a>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-gray-500 text-center">No members yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Search + Filter Script -->
    <script>
        const buttons = document.querySelectorAll('.tab-button');
        const cards = document.querySelectorAll('.member-card');
        const searchInput = document.getElementById('memberSearch');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                buttons.forEach(b => b.classList.remove('bg-indigo-100', 'text-indigo-700', 'active'));
                buttons.forEach(b => b.classList.add('bg-gray-100', 'text-gray-600'));
                btn.classList.add('bg-indigo-100', 'text-indigo-700', 'active');

                const filter = btn.dataset.filter;
                cards.forEach(card => {
                    const isOnline = card.dataset.online === 'true';
                    const role = card.dataset.role;

                    if (filter === 'all') card.style.display = 'flex';
                    else if (filter === 'online' && isOnline) card.style.display = 'flex';
                    else if (filter === 'staff' && (role === 'admin' || role === 'moderator')) card.style.display = 'flex';
                    else card.style.display = 'none';
                });
            });
        });

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            cards.forEach(card => {
                const name = card.dataset.name;
                card.style.display = name.includes(query) ? 'flex' : 'none';
            });
        });
    </script>
</x-layout>
