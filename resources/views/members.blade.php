<x-layout :community="$community" :communities="$communities">
    <div class="min-h-screen px-6 py-10 bg-white">

        <!-- Header -->
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-extrabold text-gray-900 text-center mb-2">
                Community Members
            </h1>
            <div class="h-1 w-24 mx-auto mt-2 rounded-full bg-gradient-to-r from-blue-300 to-cyan-300"></div>

            <p class="text-center text-gray-500 mb-6">
                Meet and connect with everyone in <span class="font-semibold text-blue-600">{{ $community->name }}</span>
            </p>


            <!-- Search -->
            <div class="relative mt-6 w-full flex justify-center">
                <div class="relative w-full max-w-sm">
                    <input id="memberSearch" type="text" placeholder="Search members..."
                        class="w-full px-4 py-2.5 rounded-2xl bg-white/70 backdrop-blur-md border border-gray-200 shadow-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-200 focus:outline-none placeholder-gray-400 text-sm transition-all duration-300 hover:shadow-md hover:border-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-3 top-2.5 h-5 w-5 text-gray-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                </div>
            </div>


            <!-- Filter Tabs -->
            <div class="flex justify-center gap-3 mt-6 flex-wrap">
                <button data-filter="all"
                    class="tab-button px-5 py-2 rounded-full text-sm font-semibold bg-white/70 text-gray-600 hover:bg-purple-50 shadow-sm transition">
                    All Members ({{ $community->memberships->count() }})
                </button>
                <button data-filter="online"
                    class="tab-button px-5 py-2 rounded-full text-sm font-semibold bg-white/70 text-gray-600 hover:bg-purple-50 shadow-sm transition">
                    Online ({{ $community->memberships->where('status', 'active')->count() }})
                </button>
                <button data-filter="staff"
                    class="tab-button px-5 py-2 rounded-full text-sm font-semibold bg-white/70 text-gray-600 hover:bg-purple-50 shadow-sm transition">
                    Staff ({{ $community->memberships->whereIn('role', ['admin', 'moderator'])->count() }})
                </button>
                @if (auth()->user()->id === $community->owner_id ||
                        $community->memberships->where('user_id', auth()->user()->id)->whereIn('role', ['admin', 'moderator'])->count() > 0)
                    <button data-filter="pending"
                        class="tab-button px-5 py-2 rounded-full text-sm font-semibold bg-white/70 text-gray-600 hover:bg-purple-50 shadow-sm transition">
                        Pending ({{ $community->memberships->where('status', 'pending')->count() }})
                    </button>
                @endif
            </div>


            <!-- Members Grid -->
            <div id="membersGrid" class="max-w-7xl mx-auto grid gap-6 md:grid-cols-2 lg:grid-cols-3 mt-8">
                @forelse($community->memberships as $member)
                    @php
                        $user = $member->user;
                        $joined = $member->created_at ? $member->created_at->format('F Y') : '—';
                        $isStaff = in_array($member->role, ['admin', 'moderator']);
                        $isOnline = $member->status === 'active';
                    @endphp

                    <div class="member-card  bg-white/70 backdrop-blur-lg rounded-2xl p-5 shadow-md hover:shadow-xl border border-white/50  transform hover:-translate-y-1  transition-all duration-300 flex flex-col justify-between"
                        data-role="{{ $member->role }}" data-online="{{ $isOnline ? 'true' : 'false' }}"
                        data-status="{{ $member->status }}" data-name="{{ strtolower($user->name) }}">


                        <!-- Top Row -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-300 to-indigo-300 flex items-center justify-center overflow-hidden">
                                    @if ($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}"
                                            alt="{{ $user->name }}'s avatar" class="w-full h-full object-cover">
                                    @else
                                        <span
                                            class="text-white font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $user->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($member->status === 'pending')
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                                @endif
                                @if ($member->role === 'owner')
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">Owner
                                        👑</span>
                                @elseif ($member->role === 'admin')
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Admin</span>
                                @elseif ($member->role === 'moderator')
                                    <span
                                        class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">Moderator</span>
                                @endif
                            </div>
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
                            @if (
                                $member->status === 'pending' &&
                                    (auth()->user()->id === $community->owner_id ||
                                        $community->memberships->where('user_id', auth()->user()->id)->whereIn('role', ['admin', 'moderator'])->count() > 0))
                                <form action="/communities/{{ $community->slug }}/approve" method="POST"
                                    class="flex-1">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit"
                                        class="w-full px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-green-400 to-emerald-400 text-white hover:from-green-500 hover:to-emerald-500 transition">
                                        Approve
                                    </button>
                                </form>
                                <form action="/communities/{{ $community->slug }}/reject" method="POST"
                                    class="flex-1">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit"
                                        class="w-full px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-red-400 to-pink-400 text-white hover:from-red-500 hover:to-pink-500 transition">
                                        Reject
                                    </button>
                                </form>
                            @else
                                <form action="/threads/{{ $community->slug }}" method="POST" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="participant_ids[]" value="{{ $user->id }}">
                                    <button type="submit"
                                        class="w-full px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-blue-200 to-cyan-200 hover:from-blue-300 hover:to-cyan-300 transition">
                                        Message
                                    </button>
                                </form>
                                <a href="mailto:{{ $user->email }}"
                                    class="flex-1 px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Email
                                </a>
                            @endif
                        </div>

                        @php
                            $currentUser = auth()->user();
                            $isOwnerOrAdmin =
                                $currentUser->id === $community->owner_id ||
                                $community->memberships
                                    ->where('user_id', $currentUser->id)
                                    ->whereIn('role', ['admin'])
                                    ->count() > 0;
                            $canModerate =
                                $isOwnerOrAdmin ||
                                $community->memberships
                                    ->where('user_id', $currentUser->id)
                                    ->whereIn('role', ['moderator'])
                                    ->count() > 0;
                            $targetIsOwner = $member->role === 'owner';
                            $targetIsAdmin = $member->role === 'admin';
                            $targetIsModerator = $member->role === 'moderator';
                            $canPromote =
                                $isOwnerOrAdmin &&
                                !$targetIsOwner &&
                                !$targetIsAdmin &&
                                !$targetIsModerator &&
                                $member->status === 'active';
                            $canRemove =
                                $canModerate &&
                                $currentUser->id !== $user->id &&
                                !$targetIsOwner &&
                                $member->status === 'active';
                            $canDemote = $isOwnerOrAdmin && $targetIsModerator && $currentUser->id !== $user->id;
                        @endphp

                        @if ($canPromote || $canRemove || $canDemote)
                            <div class="flex gap-3 mt-3 border-t pt-3">
                                @if ($canPromote)
                                    <button
                                        onclick="promoteMember('{{ $community->slug }}', {{ $user->id }}, '{{ $user->name }}')"
                                        class="flex-1 px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-purple-200 to-indigo-200 
                                        hover:from-purple-300 hover:to-indigo-300 transition text-center">
                                        Make Moderator
                                    </button>
                                @endif

                                @if ($canDemote)
                                    <button
                                        onclick="demoteModerator('{{ $community->slug }}', {{ $user->id }}, '{{ $user->name }}')"
                                        class="flex-1 px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-amber-200 to-orange-200 
                                        hover:from-amber-300 hover:to-orange-300 transition text-center">
                                        Remove Moderator
                                    </button>
                                @endif

                                @if ($canRemove)
                                    <button
                                        onclick="removeMember('{{ $community->slug }}', {{ $user->id }}, '{{ $user->name }}')"
                                        class="flex-1 px-3 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-red-200 to-pink-200 
                                        hover:from-red-300 hover:to-pink-300 transition text-center">
                                        Remove Member
                                    </button>
                                @endif
                            </div>
                        @endif
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

                        const status = card.dataset.status;
                        if (filter === 'all') card.style.display = 'flex';
                        else if (filter === 'online' && isOnline) card.style.display = 'flex';
                        else if (filter === 'staff' && (role === 'admin' || role === 'moderator')) card
                            .style.display = 'flex';
                        else if (filter === 'pending' && status === 'pending') card.style.display =
                            'flex';
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
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.classList.add('scale-[1.02]');
                });
                card.addEventListener('mouseleave', () => {
                    card.classList.remove('scale-[1.02]');
                });
            });

            async function demoteModerator(slug, userId, userName) {
                try {
                    const response = await fetch(`/communities/${slug}/role`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            role: 'member'
                        })
                    });

                    if (response.ok) {
                        showToastify(`${userName} is no longer a moderator`, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        const data = await response.json();
                        console.error(data.message || 'Failed to update role');
                    }
                } catch (err) {
                    console.error('Something went wrong', err);
                }
            }



            async function promoteMember(slug, userId, userName) {
                try {
                    const response = await fetch(`/communities/${slug}/role`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            role: 'moderator'
                        })
                    });

                    if (response.ok) {
                        showToastify(`${userName} is now a moderator`, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        const data = await response.json();
                        console.error(data.message || 'Failed to update role');
                    }
                } catch (err) {
                    console.error('Something went wrong', err);
                }
            }


            async function removeMember(slug, userId, userName) {
                showConfirmToast(
                    `Remove ${userName} from this community?`,
                    async () => {
                            try {
                                const response = await fetch(`/communities/${slug}/members/${userId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                        'Accept': 'application/json'
                                    }
                                });

                                if (response.ok) {
                                    showToastify(`${userName} has been removed from the community`, 'success');
                                    setTimeout(() => window.location.reload(), 1000);
                                } else {
                                    const data = await response.json();
                                    showToastify(data.message || 'Failed to remove member', 'error');
                                }
                            } catch (err) {
                                console.error(err);
                                showToastify('Something went wrong', 'error');
                            }
                        },
                        'bg-red-400 hover:bg-red-500',
                        'Remove'
                );
            }
        </script>

</x-layout>
