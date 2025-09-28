<x-layout title="Dashboard - Gatherly">
    <div class="space-y-6">

        <!-- Community Header -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="w-full h-48 bg-gray-200"></div>
            <div class="p-6">
                <h2 class="text-2xl font-bold">{{ $community->name ?? 'Community Name' }}</h2>
                <p class="text-gray-600">{{ $community->description ?? '' }}</p>
                <div class="flex gap-6 mt-3 text-sm text-gray-500">
                    <span>{{ $community->members_count ?? 0 }} members</span>
                    <span>{{ $community->active_count ?? 0 }} active this week</span>
                    <span>{{ $community->events_count ?? 0 }} events</span>
                </div>
            </div>
        </div>

        <!-- Info Boxes -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-semibold mb-2">Community Activity</h3>
                <p class="text-2xl font-bold">{{ $stats['posts'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Total Posts</p>
                <p class="mt-2 text-green-600">{{ $stats['online_now'] ?? 0 }} Online Now</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-semibold mb-2">Community Leaders</h3>
                <ul class="text-sm text-gray-700 space-y-1">
                    {{-- Dynamically list leaders --}}
                </ul>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-semibold mb-2">Quick Info</h3>
                <ul class="text-sm text-gray-700 space-y-1">
                    {{-- Fill in dynamic quick info --}}
                </ul>
            </div>
        </div>

        <!-- Post Box -->
        <div class="bg-white p-4 rounded-lg shadow flex flex-col">
            <p class="text-gray-600 mb-3">What’s happening in {{ $community->name ?? 'this community' }}?</p>

            <div class="flex gap-2 items-center">
                <input type="text" placeholder="Write a post for review..."
                    class="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring focus:ring-blue-300">

                <label for="photo-upload"
                    class="cursor-pointer flex items-center gap-2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-200 hover:text-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7h4l2-3h6l2 3h4v13H3V7z" />
                        <circle cx="12" cy="13" r="4" />
                    </svg>
                    <span class="text-sm font-medium">Upload</span>
                </label>
                <input type="file" id="photo-upload" class="hidden">

                <input type="file" id="photo-upload" class="hidden">

                <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Post</button>
            </div>
        </div>

    </div>
</x-layout>
