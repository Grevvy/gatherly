@php
    use App\Models\Community;

    $slug = request('community');
    $community = $slug
        ? Community::with(['owner', 'memberships.user'])
            ->where('slug', $slug)
            ->firstOrFail()
        : abort(404, 'Community not found');

    // load communities the current user belongs to (for sidebar)
    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();

    $canPublish =
        auth()->check() &&
        (auth()->user()->isSiteAdmin() ||
            \App\Models\CommunityMembership::where('community_id', $community->id)
                ->where('user_id', auth()->id())
                ->whereIn('role', ['owner', 'admin', 'moderator'])
                ->where('status', 'active')
                ->exists());
@endphp

<x-layout :title="'Create Event - Gatherly'" :community="$community" :communities="$communities">
    <div class="w-full bg-white shadow-lg p-6 mt-2 px-4 lg:px-8">
        <form id="create-event-form" method="POST" action="{{ url('/events') }}">
            @csrf

            <div class="flex justify-between mb-6 pt-4 border-t border-gray-200">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 font-medium">
                    Create Event
                </button>
                <a href="{{ route('events', ['community' => $community->slug]) }}"
                    class="text-gray-600 underline text-sm">
                    Cancel
                </a>
            </div>

            <input type="hidden" name="community_id" value="{{ $community->id }}">

            <!-- Title -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Event Title</span>
                <input name="title" class="w-full border p-2 pt-6 text-gray-800 bg-transparent" required />
            </div>

            <!-- Description -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Event
                    Description</span>
                <textarea name="description" rows="3" class="w-full border p-2 pt-6 text-gray-800 bg-transparent"></textarea>
            </div>

            <!-- Location -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Location</span>
                <input name="location" class="w-full border p-2 pt-6 text-gray-800 bg-transparent" />
            </div>

            <!-- Start & End Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="relative">
                    <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Start Time</span>
                    <input type="datetime-local" name="starts_at"
                        class="w-full border p-2 pt-6 text-gray-800 bg-transparent" required />
                </div>
                <div class="relative">
                    <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">End Time</span>
                    <input type="datetime-local" name="ends_at"
                        class="w-full border p-2 pt-6 text-gray-800 bg-transparent" />
                </div>
            </div>

            <!-- Capacity -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Capacity</span>
                <input type="number" name="capacity" min="1"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent" />
            </div>

            <!-- Visibility -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Select
                    Visibility</span>
                <select name="visibility" class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none"
                    required>
                    <option value="" disabled selected></option>
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </div>

            <!-- Status -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Select Status</span>
                <select name="status" class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none"
                    required>
                    <option value="" disabled selected></option>

                    <option value="draft">Draft</option>
                    @if ($canPublish)
                        <option value="published">Published</option>
                    @endif
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('create-event-form');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const data = new FormData(form);
                const token = data.get('_token');

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: data
                    });

                    const result = await res.json();

                    if (res.ok) {
                        window.location.href = `/events?community={{ $community->slug }}`;
                    } else {
                        showToast(err.message || 'Failed to create event.', 'alert');

                    }
                } catch (err) {
                    console.error(err);
                    showToast('Something went wrong.', 'alert');

                }
            });
        });
    </script>
</x-layout>
