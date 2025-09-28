<x-layout>
    <div class="space-y-6">

        <!-- Community Banner -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="w-full h-48 bg-gray-200"></div>
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ $community->name ?? 'Community Name' }}
                </h2>
                <p class="text-gray-600">
                    {{ $community->description ?? 'This is a sample description for the community.' }}
                </p>
                <div class="flex flex-wrap gap-6 mt-3 text-sm text-gray-500">
                    <span>{{ $community->members_count ?? 0 }} members</span>
                    <span>{{ $community->active_count ?? 0 }} active this week</span>
                    <span>{{ $community->events_count ?? 0 }} events</span>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Community Events</h3>
                <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    + Create Event
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex gap-6 border-b mb-4">
                <button class="py-2 border-b-2 border-blue-600 text-blue-600 font-medium">
                    Upcoming (0)
                </button>
                <button class="py-2 text-gray-600 hover:text-gray-800">
                    Attending (0)
                </button>
            </div>

            <!-- Example Event Card -->
            <div class="bg-gray-50 rounded-lg p-6 text-center text-gray-500">
                No upcoming events found. Check back later!
            </div>
        </div>

    </div>
</x-layout>
