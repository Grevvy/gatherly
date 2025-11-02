@php
    use App\Models\Event;
    use App\Models\Community;

    $event = request()->route('event');

    if (!$event) {
        abort(404, 'Event not found.');
    }

    $community = $event->community;
    $slug = $community->slug;

    if (!$community) {
        abort(404, 'Community not found for this event.');
    }

    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
@endphp


<x-layout :title="'Edit Event - Gatherly'" :community="$community" :communities="$communities">
    <div class="w-full bg-white shadow-lg p-6 mt-2 px-4 lg:px-8 rounded-2xl">
        <form id="edit-event-form" action="/events/{{ $event->id }}" method="POST">
            @csrf
            @method('PATCH')

            <!-- Title -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Event Title</span>
                <input type="text" name="title" value="{{ $event->title }}"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl" required />
            </div>

            <!-- Description -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Event
                    Description</span>
                <textarea name="description" rows="3" class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl">{{ $event->description }}</textarea>
            </div>

            <!-- Location -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Event Location</span>
                <input type="text" name="location" value="{{ $event->location }}"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl" required />
            </div>

            <!-- Start/End Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="relative">
                    <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Start time</span>
                    <input type="datetime-local" name="starts_at"
                        class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl"
                        value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}" />
                </div>
                <div class="relative">
                    <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">End time</span>
                    <input type="datetime-local" name="ends_at"
                        class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl"
                        value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}" />
                </div>
            </div>

            <!-- Capacity -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Capacity</span>
                <input type="number" name="capacity" value="{{ $event->capacity }}" min="1"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl" />
            </div>

            <!-- Visibility -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Visibility</span>
                <select name="visibility"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none rounded-xl" required>
                    <option value="public" {{ $event->visibility === 'public' ? 'selected' : '' }}>Public</option>
                    <option value="private" {{ $event->visibility === 'private' ? 'selected' : '' }}>Private</option>
                </select>
            </div>

            <!-- Status -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Status</span>
                <select name="status"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none rounded-xl" required>
                    <option value="draft" {{ $event->status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $event->status === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="flex justify-between mb-6 pt-4 border-t border-gray-200">
                <a href="{{ route('events', ['community' => $event->community?->slug]) }}"
                    class="text-gray-600 underline">Cancel</a>
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">Update
                    Event</button>

            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('edit-event-form');

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                formData.append('_method', 'PATCH');

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        showToastify(err.message || 'Failed to update event.', 'error');
                        return;
                    }

                    window.location.href = `/events?community={{ $event->community?->slug }}`;
                } catch (err) {
                    console.error(err);
                    showToastify('Something went wrong.', 'error');
                }
            });
        });
    </script>


</x-layout>
