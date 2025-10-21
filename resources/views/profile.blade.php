<x-layout :communities="$communities">
    <div class="min-h-screen bg-gradient-to-br from-indigbg-gradient-to-b from-white to-gray-50/40 min-h-screen">
        <div class="max-w-3xl mx-auto px-6 text-center">

            <!-- Back -->
            <div class="w-full flex justify-start mb-6">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800 underline transition">
                    ← Back to Community
                </a>
            </div>

            <!-- Card -->
            <div class="bg-white/70 backdrop-blur-lg rounded-3xl shadow-xl border border-indigo-100 overflow-hidden">
                <!-- Banner -->
                <div class="relative h-44 bg-gradient-to-r from-indigo-400 to-sky-400">
                    @if (Auth::user()->banner)
                        <img src="{{ asset('storage/' . Auth::user()->banner) }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-90" alt="Banner">
                    @endif
                    <a href="{{ route('profile.edit') }}"
                        class="absolute top-4 right-4 bg-white/80 hover:bg-white text-gray-700 text-xs px-3 py-1.5 rounded-md border border-gray-200 shadow-sm transition">
                        Edit Profile
                    </a>
                </div>

                <!-- Avatar -->
                <div class="relative flex flex-col items-center -mt-16 pb-8">
                    <div class="relative w-28 h-28">
                        @if (Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}"
                                class="w-28 h-28 rounded-full border-4 border-white shadow-lg object-cover"
                                alt="Avatar">
                        @else
                            <div
                                class="w-28 h-28 rounded-full border-4 border-white shadow-lg flex items-center justify-center bg-gradient-to-br from-sky-300 to-indigo-300 text-white text-3xl font-bold">
                                {{ strtoupper(substr(auth()->user()->name ?? 'JD', 0, 1)) }}
                            </div>
                        @endif
                        <span
                            class="absolute -bottom-1 -right-1 bg-gradient-to-r from-sky-400 to-indigo-400 p-1.5 rounded-full shadow">
                            <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                        </span>
                    </div>

                    <!-- Name / username -->
                    <h2 class="mt-4 text-2xl font-bold text-gray-800 flex items-center gap-1">
                        {{ auth()->user()->name ?? 'User' }}
                        <i data-lucide="star" class="w-4 h-4 text-indigo-400"></i>
                    </h2>

                    <p class="text-gray-500 text-sm">
                        {{ '@' . (auth()->user()->username ?? \Illuminate\Support\Str::slug(auth()->user()->name ?? 'user')) }}
                    </p>


                    <p class="text-gray-400 text-xs mt-1">
                        Joined {{ optional(auth()->user()->created_at)->format('F Y') ?? 'Recently' }}
                    </p>

                    <p class="text-gray-700 mt-7 text-sm max-w-md leading-relaxed">
                        "{{ auth()->user()->bio ?? 'Write a short bio.' }}"
                    </p>

                    <!-- Contact -->
                    <div class="flex flex-wrap justify-center gap-4 mt-5 text-sm text-gray-600">
                        @if (auth()->user()->email)
                            <span class="flex items-center gap-1"><i data-lucide="mail"></i>
                                {{ auth()->user()->email }}</span>
                        @endif
                        @if (auth()->user()->phone)
                            <span class="flex items-center gap-1"><i data-lucide="phone"></i>
                                {{ auth()->user()->phone }}</span>
                        @endif
                        @if (auth()->user()->location)
                            <span class="flex items-center gap-1"><i data-lucide="map-pin"></i>
                                {{ auth()->user()->location }}</span>
                        @endif
                        @if (auth()->user()->website)
                            <span class="flex items-center gap-1"><i data-lucide="globe"></i>
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

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
                @foreach ($stats as $stat)
                    <div
                        class="bg-gradient-to-r {{ $stat['color'] }} text-white py-3 rounded-xl shadow text-center hover:scale-105 transform duration-200">
                        <p class="text-2xl font-bold">{{ $stat['count'] }}</p>
                        <p class="text-sm opacity-90">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- About -->
            <div class="mt-10 bg-white/70 backdrop-blur-md rounded-2xl shadow-inner p-6 border border-indigo-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">About Me ☁️</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    {{ Auth::user()->bio ?? 'Write a short bio.' }}
                </p>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</x-layout>
