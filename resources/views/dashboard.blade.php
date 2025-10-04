<x-layout title="Dashboard - Gatherly">
    <div class="bg-gray-50 min-h-screen">

        @php
            $community = null;
            if (request('community')) {
                $community = \App\Models\Community::with(['owner', 'memberships.user'])
                    ->where('slug', operator: request('community'))
                    ->first();
            }
        @endphp

        @if ($community)
            <!-- Community Banner -->
            <div class="relative w-full max-w-6xl h-60 overflow-hidden mx-auto">
                <!-- Background Image -->
                <img src="{{ $community->banner_url ?? 'https://via.placeholder.com/1200x300' }}" alt="Community Banner"
                    class="w-full h-full object-cover">

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black bg-opacity-40"></div>

                <!-- Content -->
                <div class="absolute bottom-4 left-4 flex items-start gap-4 text-white">
                    <div>
                        <h2 class="text-2xl font-bold">{{ $community->name }}</h2>
                        <p class="text-gray-200 mt-1 text-sm">{{ $community->description }}</p>

                        <!-- Stats -->
                        <div class="flex gap-6 text-sm mt-2 text-gray-200">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5V4H2v16h5m10-6a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ $community->memberships->count() }} members
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $community->memberships->where('status', 'active')->count() }} active
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.121 17.804A4 4 0 016 16h12a4 4 0 01.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Owner: {{ $community->owner->name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Grid -->
        <main class="max-w-6xl mx-auto mt-6 px-4 grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Posts Section -->
            <section class="lg:col-span-2 space-y-6">
                @if ($community)
                    <!-- Post Input -->
                    <div class="bg-white border border-gray-200 shadow p-6">
                        <div class="flex gap-5 items-start">
                            <!-- Rounded Avatar -->
                            <div
                                class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </div>

                            <div class="flex-1">
                                <textarea placeholder="What's happening in {{ $community->name }}?"
                                    class="w-full bg-blue-50 border border-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 p-4 text-gray-800 placeholder-gray-400 text-sm resize-none"
                                    rows="3"></textarea>

                                <div class="flex justify-between items-center mt-4">
                                    <div class="flex gap-4 text-sm text-gray-500">
                                        <span class="flex items-center gap-1 text-orange-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3" />
                                            </svg>
                                            Posts require approval
                                        </span>

                                        <label for="photo-upload"
                                            class="flex items-center gap-1 cursor-pointer hover:text-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828M16 5h6m-2-2v6" />
                                            </svg>
                                            Photo
                                        </label>
                                        <input type="file" id="photo-upload" class="hidden" accept="image/*">
                                    </div>

                                    <button type="button"
                                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 shadow transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                        </svg>
                                        Post
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Feed -->
                <div class="space-y-5">
                    @if ($community)
                        @forelse ($posts ?? [] as $post)
                            {{-- Render individual post components here --}}
                        @empty
                            <div class="text-center text-gray-500 py-8">No posts yet.</div>
                        @endforelse
                    @else
                        <div class="text-center text-gray-500 py-8">
                            Select a community to get started.
                        </div>
                    @endif
                </div>
            </section>

            <!-- Sidebar -->
            @if ($community)
                <aside id="sidebar" class="space-y-6">
                    <div class="bg-white border border-gray-200 p-6 rounded-lg shadow">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Community Info</h3>

                        <!-- Activity -->
                        <div class="border-b border-gray-300 pb-3 mb-3">
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Activity
                            </h4>
                            <p id="community-activity" class="mt-1 text-sm text-gray-600"></p>
                        </div>

                        <!-- Leaders -->
                        <div class="border-b border-gray-300 pb-3 mb-3">
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.121 17.804A4 4 0 016 16h12a4 4 0 01.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Leaders
                            </h4>
                            <ul id="community-leaders" class="mt-1 text-sm text-gray-600 space-y-1"></ul>
                        </div>

                        <!-- Quick Info -->
                        <div>
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Quick Info
                            </h4>
                            <p id="community-info" class="mt-1 text-sm text-gray-600"></p>
                        </div>
                    </div>
                </aside>
            @endif
        </main>
    </div>
</x-layout>
