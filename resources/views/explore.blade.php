@php
    use App\Models\Community;

    // Current user’s communities (for sidebar)
    $userCommunities = collect();
    if (auth()->check()) {
        $userCommunities = Community::whereHas('memberships', function ($q) {
            $q->where('user_id', auth()->id());
        })->get();
    }
@endphp

<x-layout :title="'Explore Communities'" :community="null" :communities="$userCommunities">
<div class="min-h-screen bg-white py-10">

    <div class="max-w-6xl mx-auto px-4">

        <h1 class="text-4xl font-extrabold text-blue-700 mb-10 text-center">
    🌎 Discover New Communities
              </h1>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
<p class="text-sm text-gray-500 italic line-clamp-2 leading-relaxed">{{ $community->description ?? 'No description yet.' }}</p>


<!-- Badges -->
<div class="flex items-center gap-2 text-xs text-gray-500 mt-2">
    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
        {{ ucfirst($community->visibility ?? 'public') }}
    </span>
    <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
        {{ ucfirst($community->join_policy ?? 'open') }}
    </span>
    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
        {{ $community->memberships->count() ?? 0 }} members
    </span>
</div>
</div>


                    <div class="mt-4 flex justify-between items-center">
                        <a href="/dashboard?community={{ $community->slug }}"
                           class="text-blue-600 text-sm font-medium hover:underline">View</a>

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
                           <button disabled
                               class="bg-gray-300 text-white text-sm px-4 py-1.5 rounded-xl 
                                   font-semibold cursor-not-allowed">
                               Member ✓
                           </button>
                       @elseif ($isPending)
                           <button disabled
                               class="bg-yellow-100 text-yellow-700 text-sm px-4 py-1.5 rounded-xl 
                                   font-semibold cursor-not-allowed">
                               Request Pending
                           </button>
                       @else
                           <form action="/communities/{{ $community->slug }}/join" method="POST" target="hidden_iframe_{{ $community->id }}">
                               @csrf
                               <button type="submit"
                                   class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm px-4 py-1.5 rounded-xl 
                                       font-semibold shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 
                                       transition-all duration-300 hover:-translate-y-0.5"
                                   {{ $joinPolicy === 'invite' ? 'disabled' : '' }}
                                   title="{{ $joinPolicy === 'invite' ? 'This community is invite-only' : '' }}">
                                   {{ $buttonText }}
                               </button>
                           </form>
                       @endif
                <iframe name="hidden_iframe_{{ $community->id }}" style="display:none;"></iframe>

                        </form>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-gray-500 text-sm">
                    No communities found. Check back later!
                </p>
            @endforelse
        </div>
    </div>

    <script>
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.join-btn');
            if (!btn) return;

            const slug = btn.dataset.slug;
            btn.disabled = true;
            btn.textContent = 'Joining...';

            try {
                const res = await fetch(`/communities/${slug}/join`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    btn.textContent = 'Joined ✓';
                    btn.classList.remove('bg-blue-500');
                    btn.classList.add('bg-green-500');
                    btn.disabled = true;

                    // Wait a bit longer for DB update before reload
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    btn.textContent = 'Join';
                    btn.disabled = false;
                    alert('Something went wrong joining this community.');
                }
            } catch (err) {
                console.error(err);
                alert('Error joining community.');
                btn.textContent = 'Join';
                btn.disabled = false;
            }
        });
    </script>
    <script>
      document.querySelectorAll('form[action*="/join"]').forEach(form => {
    form.addEventListener('submit', () => {
        // reload page after short delay so user sees update
        setTimeout(() => {
            window.location.reload();
        }, 800);
    });
});
</script>

</x-layout>
