<x-layout>
    <div class="bg-gray-50 min-h-screen py-10">
        <div class="max-w-4xl mx-auto px-6">
            <!-- Back link -->
            <a href="/dashboard" class="flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Community
            </a>

            <!-- Profile Card -->
            <div class="bg-white shadow-md rounded-xl overflow-hidden relative">
                <!-- Cover -->
                <div class="h-40 bg-gradient-to-r from-blue-500 to-purple-500"></div>

                <!-- Edit button -->
                <button
                    class="absolute top-4 right-4 bg-white text-gray-700 text-xs px-3 py-1 rounded-md border hover:bg-gray-100 shadow-sm">
                    Edit Profile
                </button>

                <!-- Avatar + Info -->
                <div class="relative flex flex-col items-center px-6 pb-8 -mt-12">
                    <div
                        class="w-24 h-24 rounded-full bg-gray-200 border-4 border-white overflow-hidden shadow-md flex items-center justify-center text-2xl font-semibold text-gray-600">
                        {{ strtoupper(substr(auth()->user()->name ?? 'JD', 0, 2)) }}
                    </div>

                    <h2 class="mt-3 text-xl font-bold text-gray-900 flex items-center gap-1">
                        {{ auth()->user()->name ?? 'User' }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 111.414-1.414L9 10.586l3.293-3.293a1 1 0 111.414 1.414z" />
                        </svg>
                    </h2>

                    <p class="text-gray-500 text-sm">
                        {{ '@' . (auth()->user()->username ?? Str::slug(auth()->user()->name ?? 'user')) }}
                    </p>

                    <p class="text-gray-400 text-xs mt-1">
                        Joined {{ optional(auth()->user()->created_at)->format('F Y') ?? 'Recently' }}
                    </p>

                    <p class="text-center text-gray-700 mt-3 text-sm max-w-md">
                        {{ auth()->user()->bio ?? 'Passionate community member and avid reader. Love connecting with people and sharing meaningful experiences.' }}
                    </p>

                    <div class="flex flex-wrap justify-center gap-4 mt-4 text-sm text-gray-600">
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

            <!-- Stats Section -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                @foreach ([
                    ['count' => 0, 'label' => 'Posts'],
                    ['count' => 0, 'label' => 'Communities'],
                    ['count' => 0, 'label' => 'Likes Received'],
                    ['count' => 0, 'label' => 'Comments'],
                ] as $stat)
                    <div
                        class="bg-white rounded-xl shadow-sm py-4 text-center hover:shadow-md transition border border-gray-100">
                        <p class="text-xl font-bold text-gray-900">{{ $stat['count'] }}</p>
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</x-layout>
