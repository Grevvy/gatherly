<x-layout :communities="$communities">
    <div class="min-h-screen bg-gradient-to-b from-white to-gray-50/40">
        <div class="max-w-4xl mx-auto px-6 py-8 text-center">

            <!-- Back -->
            <div class="w-full flex justify-start mb-8">
                <a href="{{ request('community') ? '/dashboard?community=' . request('community') : '/dashboard' }}"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Go Back
                </a>
            </div>

            <!-- Card -->
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-indigo-100 overflow-hidden">
                <!-- Banner -->
                <div class="relative h-56 bg-gradient-to-r from-indigo-400 to-sky-400">
                    @if (Auth::user()->banner)
                        <img src="{{ Auth::user()->banner_image_url }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-90" alt="Banner">
                    @endif
                    <a href="{{ route('profile.edit') }}"
                        class="absolute top-4 right-4 bg-white/80 hover:bg-white text-gray-700 text-xs px-3 py-1.5 rounded-md border border-gray-200 shadow-sm transition">
                        Edit Profile
                    </a>
                </div>

                <!-- Avatar -->
                <div class="relative flex flex-col items-center -mt-20 pb-10">
                    <div class="relative w-36 h-36">
                        @if (Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar_url }}"
                                class="w-36 h-36 rounded-full border-4 border-white shadow-xl object-cover"
                                alt="Avatar">
                        @else
                            <div
                                class="w-36 h-36 rounded-full border-4 border-white shadow-xl flex items-center justify-center bg-gradient-to-br from-sky-300 to-indigo-300 text-white text-4xl font-bold">
                                {{ strtoupper(substr(auth()->user()->name ?? 'JD', 0, 1)) }}
                            </div>
                        @endif
                        <span
                            class="absolute -bottom-2 -right-2 bg-gradient-to-r from-sky-400 to-indigo-400 p-2 rounded-full shadow-lg">
                            <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                        </span>
                    </div>

                    <!-- Name / username -->
                    <h2 class="mt-6 text-3xl font-bold text-gray-800 flex items-center justify-center gap-2">
                        {{ auth()->user()->name ?? 'User' }}
                        <i data-lucide="star" class="w-5 h-5 text-indigo-400"></i>
                    </h2>

                    <p class="text-gray-500 text-base mt-2">
                        {{ '@' . (auth()->user()->username ?? \Illuminate\Support\Str::slug(auth()->user()->name ?? 'user')) }}
                    </p>

                    <p class="text-gray-400 text-sm mt-2">
                        Joined {{ optional(auth()->user()->created_at)->format('F Y') ?? 'Recently' }}
                    </p>

                    <p class="text-gray-700 mt-8 text-base max-w-lg mx-auto leading-relaxed">
                        "{{ auth()->user()->bio ?? 'Write a short bio.' }}"
                    </p>

                    <!-- Contact -->
                    <div class="flex flex-wrap justify-center gap-4 mt-8 text-base text-gray-600">
                        @if (auth()->user()->email)
                            <span class="flex items-center gap-2"><i data-lucide="mail" class="w-5 h-5"></i>
                                {{ auth()->user()->email }}</span>
                        @endif
                        @if (auth()->user()->phone)
                            <span class="flex items-center gap-2"><i data-lucide="phone" class="w-5 h-5"></i>
                                {{ auth()->user()->phone }}</span>
                        @endif
                        @if (auth()->user()->location)
                            <span class="flex items-center gap-2"><i data-lucide="map-pin" class="w-5 h-5"></i>
                                {{ auth()->user()->location }}</span>
                        @endif
                        @if (auth()->user()->website)
                            <span class="flex items-center gap-2"><i data-lucide="globe" class="w-5 h-5"></i>
                                {{ auth()->user()->website }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stats -->
            @php
                $user = Auth::user();
                $uid = $user?->id;

                // safely get relation counts
                $safeCount = fn($relation) => $user && method_exists($user, $relation)
                    ? $user->{$relation}()->count()
                    : 0;

                // count communities manually
                $communityCount = 0;
                if ($uid) {
                    $communityCount = \App\Models\Community::whereHas('memberships', function ($q) use ($uid) {
                        $q->where('user_id', $uid);
                    })->count();
                }

                $stats = [
                    ['count' => $safeCount('posts'), 'label' => 'Posts', 'color' => 'from-sky-400 to-indigo-500'],
                    ['count' => $communityCount, 'label' => 'Communities', 'color' => 'from-indigo-400 to-blue-500'],
                    ['count' => $safeCount('likes'), 'label' => 'Likes', 'color' => 'from-purple-400 to-indigo-400'],
                    ['count' => $safeCount('comments'), 'label' => 'Comments', 'color' => 'from-cyan-400 to-sky-500'],
                ];
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-12">
                @foreach ($stats as $stat)
                    <div
                        class="bg-gradient-to-r {{ $stat['color'] }} text-white py-6 px-4 rounded-xl shadow-lg text-center hover:scale-105 transform duration-200">
                        <p class="text-3xl font-bold">{{ $stat['count'] }}</p>
                        <p class="text-base opacity-90 mt-1">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>


        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</x-layout>
