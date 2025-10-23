@php
    use App\Models\Community;

    // Sidebar: user’s own communities
    $userCommunities = Community::whereHas('memberships', function ($q) {
        $q->where('user_id', auth()->id());
    })->get();
@endphp

<x-layout :title="'Explore Communities'" :community="null" :communities="$userCommunities">

<div class="min-h-screen bg-white py-10">
    <div class="max-w-6xl mx-auto px-4">

        <h1 class="text-4xl font-extrabold text-blue-700 mb-6 text-center">
            🌎 Discover New Communities
        </h1>

        <!-- 🔍 Search Bar -->
        <div class="max-w-2xl mx-auto mb-10">
            <div class="relative">
                <input 
                    id="communitySearch" 
                    type="text" 
                    placeholder="Search communities by name or description..."
                    class="w-full px-4 py-3 rounded-2xl bg-white/70 backdrop-blur-md border border-gray-200 
                        shadow-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-200 focus:outline-none 
                        placeholder-gray-400 text-sm transition-all duration-300 hover:shadow-md hover:border-blue-100"
                >
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="absolute right-3 top-3 h-5 w-5 text-gray-400 pointer-events-none"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
            </div>
        </div>

        <!-- 🌟 Communities Based on Your Interests -->
        @if(isset($recommended) && $recommended->count() > 0)
        <h2 class="text-2xl font-bold text-blue-600 mb-4">🌟 Communities Based on Your Interests</h2>

        <div id="recommendedGrid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            @foreach($recommended as $community)
                <div class="group relative bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-md 
                    hover:shadow-blue-200/70 transition-all duration-300 p-5 flex flex-col justify-between 
                    transform hover:-translate-y-2 hover:scale-[1.02]">

                    <div>
                        <div class="relative overflow-hidden rounded-2xl">
                            <img src="{{ asset($community->banner_image ?? 'images/default-banner.jpg') }}"
                                alt="Community Banner"
                                class="w-full h-36 object-cover rounded-2xl transform group-hover:scale-110 transition duration-500 ease-out">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-500 rounded-2xl"></div>
                        </div>

                        <h2 class="text-xl font-bold text-gray-800 mb-1 tracking-tight">{{ $community->name }}</h2>
                        <p class="text-sm text-gray-500 italic line-clamp-2 leading-relaxed">
                            {{ $community->description ?? 'No description yet.' }}
                        </p>

                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-2">
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ ucfirst($community->visibility ?? 'public') }}</span>
                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ ucfirst($community->join_policy ?? 'open') }}</span>
                            <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $community->memberships->count() ?? 0 }} members</span>
                        </div>

                        @if (!empty($community->tags))
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-gray-600 mb-1">Tags:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($community->tags as $tag)
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full shadow-sm">
                                            {{ ucfirst($tag) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <a href="/dashboard?community={{ $community->slug }}" class="text-blue-600 text-sm font-medium hover:underline">View</a>

                        @php
                            $membership = $community->memberships->where('user_id', auth()->id())->first();
                            $isMember = $membership && $membership->status === 'active';
                            $isPending = $membership && $membership->status === 'pending';
                            $joinPolicy = $community->join_policy ?? 'open';
                            $buttonText = match($joinPolicy) {
                                'request' => 'Request to Join',
                                'invite' => 'Invite Only',
                                default => 'Join'
                            };
                        @endphp

                        @if ($isMember)
                            <button disabled class="bg-gray-300 text-white text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed">Member ✓</button>
                        @elseif ($isPending)
                            <button disabled class="bg-yellow-100 text-yellow-700 text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed">Request Pending</button>
                        @else
                            <button
                    onclick="joinCommunity('{{ $community->slug }}', '{{ $community->name }}', '{{ $buttonText }}')"
                     class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm px-4 py-1.5 rounded-xl 
                  font-semibold shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 
                 transition-all duration-300 hover:-translate-y-0.5">
                 {{ $buttonText }}
                    </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <!-- 🌍 All Communities -->
        <h2 class="text-2xl font-bold text-gray-900 mb-4">🌍 All Communities</h2>
        <div id="communitiesGrid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($communities as $community)
                <div class="group relative bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl shadow-md 
                    hover:shadow-blue-200/70 transition-all duration-300 p-5 flex flex-col justify-between 
                    transform hover:-translate-y-2 hover:scale-[1.02]">

                    <div>
                        <div class="relative overflow-hidden rounded-2xl">
                            <img src="{{ asset($community->banner_image ?? 'images/default-banner.jpg') }}"
                                alt="Community Banner"
                                class="w-full h-36 object-cover rounded-2xl transform group-hover:scale-110 transition duration-500 ease-out">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-500 rounded-2xl"></div>
                        </div>

                        <h2 class="text-xl font-bold text-gray-800 mb-1 tracking-tight">{{ $community->name }}</h2>
                        <p class="text-sm text-gray-500 italic line-clamp-2 leading-relaxed">
                            {{ $community->description ?? 'No description yet.' }}
                        </p>

                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-2">
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ ucfirst($community->visibility ?? 'public') }}</span>
                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ ucfirst($community->join_policy ?? 'open') }}</span>
                            <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $community->memberships->count() ?? 0 }} members</span>
                        </div>

                        @if (!empty($community->tags))
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-gray-600 mb-1">Tags:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($community->tags as $tag)
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full shadow-sm">
                                            {{ ucfirst($tag) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <a href="/dashboard?community={{ $community->slug }}" class="text-blue-600 text-sm font-medium hover:underline">View</a>

                        @php
                            $membership = $community->memberships->where('user_id', auth()->id())->first();
                            $isMember = $membership && $membership->status === 'active';
                            $isPending = $membership && $membership->status === 'pending';
                            $joinPolicy = $community->join_policy ?? 'open';
                            $buttonText = match($joinPolicy) {
                                'request' => 'Request to Join',
                                'invite' => 'Invite Only',
                                default => 'Join'
                            };
                        @endphp

                        @if ($isMember)
                            <button disabled class="bg-gray-300 text-white text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed">Member ✓</button>
                        @elseif ($isPending)
                            <button disabled class="bg-yellow-100 text-yellow-700 text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed">Request Pending</button>
                        @else
                            <button
                                onclick="joinCommunity('{{ $community->slug }}', '{{ $community->name }}', '{{ $buttonText }}')"
                                class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm px-4 py-1.5 rounded-xl 
                                    font-semibold shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 
                                    transition-all duration-300 hover:-translate-y-0.5"
                                {{ $joinPolicy === 'invite' ? 'disabled' : '' }}
                                title="{{ $joinPolicy === 'invite' ? 'This community is invite-only' : '' }}">
                                {{ $buttonText }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-gray-500 text-sm">
                    No communities found. Check back later!
                </p>
            @endforelse
        </div>
    </div>
</div>
<script>
async function joinCommunity(slug, communityName, buttonText) {
    const btn = event.target;
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Joining...';

    try {
        const res = await fetch(`/communities/${slug}/join`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        if (res.ok) {
            // Instantly update button UI — no alerts
            if (buttonText === 'Request to Join') {
                btn.textContent = 'Request Pending';
                btn.className = 'bg-yellow-100 text-yellow-700 text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed';
            } else {
                btn.textContent = 'Joined ✓';
                btn.className = 'bg-gray-300 text-white text-sm px-4 py-1.5 rounded-xl font-semibold cursor-not-allowed';
            }
        } else {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    } catch (err) {
        console.error(err);
        btn.disabled = false;
        btn.textContent = originalText;
    }
}
</script>


</x-layout>
