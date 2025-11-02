@php
    use App\Models\Event;
    use App\Models\Community;

    // Retrieve the event from the route
    $eventId = request()->route('event');
    $event = Event::with([
        'community',
        'owner',
        'attendees' => function ($query) {
            $query->where('status', 'accepted')->with('user');
        },
    ])
        ->withCount([
            'attendees as accepted_count' => function ($query) {
                $query->where('status', 'accepted');
            },
        ])
        ->findOrFail($eventId);

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

<x-layout :title="'Event Details- Gatherly'" :community="$community" :communities="$communities">
    <div class="w-full bg-white shadow-lg p-6 mt-2 px-4 lg:px-8 rounded-2xl">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div>
                <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ $event->title }}</h1>
                <p class="mt-2 text-base text-gray-600">
                    Event Organized by <span
                        class="font-medium text-gray-800">{{ $event->owner->name ?? 'Unknown' }}</span>

                </p>
            </div>
            <a href="{{ route('events', ['community' => $event->community?->slug]) }}"
                class="text-gray-600 hover:text-gray-800 text-sm">←
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

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 100-6 3 3 0 000 6z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 22s8-4.5 8-11a8 8 0 10-16 0c0 6.5 8 11 8 11z" />
                        </svg>
                    </span>
                    <p class="text-gray-500 font-semibold text-base">Location</p>
                    <p class="col-start-2">{{ $event->location ?? 'N/A' }}</p>
                </div>

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <p class="text-gray-500 font-semibold text-base mb-1">Starts</p>
                    <p class="col-start-2">{{ $event->starts_at->format('D, M j, Y') }} at
                        {{ $event->starts_at->format('g:i A') }}</p>
                </div>

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                        </svg>
                    </span>
                    <p class="text-gray-500 font-semibold text-base">Ends</p>
                    <p class="col-start-2">{{ $event->ends_at->format('D, M j, Y') }} at
                        {{ $event->ends_at->format('g:i A') }}</p>
                </div>

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 13l4 4L19 7M16 6a4 4 0 11-8 0 4 4 0 018 0zM4 20h16v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2z" />
                        </svg>
                    </span>
                    <p class="text-gray-500 font-semibold text-base">Capacity</p>
                    <p class="col-start-2">
                        {{ $event->accepted_count }}/{{ $event->capacity ?? '∞' }}
                        spots filled
                    </p>
                </div>

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </span>
                    <p class="text-gray-500 font-semibold text-base">Visibility</p>
                    <p class="col-start-2">{{ $event->visibility ?? 'N/A' }}</p>
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

                <div class="grid grid-cols-[auto_1fr] gap-3 items-start">
                    <span
                        class="inline-flex items-center justify-center bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm shrink-0 row-span-2">
                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                    </span>
                    <p class="text-gray-500 font-semibold text-base">Status</p>
                    <span
                        class="col-start-2 inline-block px-4 py-1 rounded-md font-semibold capitalize w-fit justify-self-start {{ $statusClass }}">
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
                <ul class="space-y-3 text-[15px] text-gray-700">
                    @foreach ($event->attendees as $attendee)
                        @php
                            $avatarUser = $attendee->user ?? null;
                        @endphp

                        <li class="flex items-center gap-3">
                            <span class="text-gray-500 w-5 text-right">{{ $loop->iteration }}.</span>

                            <!-- Avatar -->
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-sky-300 to-indigo-300 flex items-center justify-center overflow-hidden">
                                @if ($avatarUser && $avatarUser->avatar)
                                    <img src="{{ asset('storage/' . $avatarUser->avatar) }}"
                                        alt="{{ $avatarUser->name }}'s avatar" class="w-full h-full object-cover">
                                @elseif ($avatarUser)
                                    <span class="text-white font-semibold">
                                        {{ strtoupper(substr($avatarUser->name ?? 'U', 0, 1)) }}
                                    </span>
                                @else
                                    <span class="text-white font-semibold">?</span>
                                @endif
                            </div>

                            <div>
                                <div class="font-medium text-gray-800">{{ $avatarUser->name ?? 'Unknown' }}</div>
                                @if ($avatarUser?->email)
                                    <div class="text-xs text-gray-500">{{ $avatarUser->email }}</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-[15px] text-gray-500">No attendees yet.</p>
            @endif
        </div>
        <div class="text-[11px] text-gray-400 text-right mt-4">
            Created: {{ $event->created_at->format('g:i A · M j, Y') }}
        </div>

    </div>
</x-layout>
