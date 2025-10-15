@php
    use App\Models\Event;
    use App\Models\Community;

    // Retrieve the event from the route
    $eventId = request()->route('event');
    $event = Event::with(['community', 'owner', 'attendees.user'])->findOrFail($eventId);

    // Load the community for the event
    $community = $event->community;
    $slug = $community->slug;

    if (!$community) {
        abort(404, 'Community not found for this event.');
    }

    // Load communities the current user belongs to (for sidebar)
    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();

@endphp

<x-layout :title="'Edit Event - Gatherly'" :community="$community" :communities="$communities">
    <div class="w-full bg-white shadow-lg p-6 mt-2 px-4 lg:px-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ $event->title }}</h1>
                <p class="mt-2 text-base text-gray-600">
                    Event Organized by <span
                        class="font-medium text-gray-800">{{ $event->owner->name ?? 'Unknown' }}</span>
                    in <span class="font-medium text-gray-800">{{ $event->community->name ?? 'Community' }}</span>
                </p>
            </div>
            <a href="{{ route('events', ['community' => $event->community?->slug]) }}" class="text-gray-600 underline">←
                Back to Events</a>
        </div>

        <div class="border-t pt-10 space-y-8">
            <!-- Description Block -->
            <div class="gap-6 text-[14px] text-gray-800">

                <p class="text-gray-500 font-semibold text-base mb-1">Description</p>
                <p>{{ $event->description ?? 'No description provided.' }}</p>
            </div>

            <!-- Metadata Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-[14px] text-gray-800">

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Location</p>
                    <p>{{ $event->location ?? 'N/A' }}</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Starts</p>
                    <p>{{ $event->starts_at->format('D, M j, Y') }} at {{ $event->starts_at->format('g:i A') }}</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Ends</p>
                    <p>{{ $event->ends_at->format('D, M j, Y') }} at {{ $event->ends_at->format('g:i A') }}</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Capacity</p>
                    <p>{{ $event->capacity ?? 'Unlimited' }}</p>
                </div>

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Visibility</p>
                    <p>{{ $event->visibility ?? 'N/A' }}</p>
                </div>

                @php
                    $statusStyles = [
                        'draft' => 'bg-yellow-100 text-yellow-800',
                        'published' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 
                        text-red-700',
                    ];

                    $statusClass = $statusStyles[$event->status] ?? 'bg-gray-100 text-gray-600';
                @endphp

                <div>
                    <p class="text-gray-500 font-semibold text-base mb-1">Status</p>
                    <span class="inline-block px-3 py-1 rounded-md font-semibold capitalize {{ $statusClass }}">
                        {{ $event->status }}
                    </span>
                </div>

            </div>
        </div>


        <div class="border-t pt-8">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                Attendees ({{ $event->attendees->count() }}/{{ $event->capacity ?? '∞' }})
            </h3>
            @if ($event->attendees->count())
                <ul class="list-disc pl-6 space-y-2 text-[15px] text-gray-700">
                    @foreach ($event->attendees as $attendee)
                        <li>{{ $attendee->user->name ?? 'Unknown' }}</li>
                    @endforeach
                </ul>
            @else
                <p class="text-[15px] text-gray-500">No attendees yet.</p>
            @endif
        </div>
        <div class="text-xs text-gray-400 mt-9">
            Created on {{ $event->created_at->format('g:i A · M j, Y') }}
        </div>

    </div>
</x-layout>
