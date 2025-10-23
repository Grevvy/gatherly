@php
    use App\Models\Community;
    $slug = request('community');
    $community = $slug
        ? Community::with(['owner', 'memberships.user', 'events.attendees'])
            ->where('slug', $slug)
            ->first()
        : null;
    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
    $nestedTabs = ['Upcoming', 'Attending'];
    $activeNestedTab = request('tab', 'Upcoming');
    $allUpcomingEvents = $community
        ? $community
            ->events()
            ->withCount(['attendees as accepted_count' => fn($q) => $q->where('status', 'accepted')])
            ->orderBy('starts_at')
            ->get()
        : collect();

    // Filter visibility: drafts should only be visible to the event owner, event host, or community owner/admin/moderator
    $visibleUpcomingEvents = $allUpcomingEvents->filter(function ($event) {
        if ($event->status !== 'draft') {
            return true;
        }

        $uid = auth()->id();
        if (!$uid) {
            return false;
        }

        // Owner
        if ($event->owner_id === $uid) {
            return true;
        }

        // Host
        $isHost = \App\Models\EventAttendee::where('event_id', $event->id)
            ->where('user_id', $uid)
            ->where('role', 'host')
            ->exists();
        if ($isHost) {
            return true;
        }

        // Community admin/owner/moderator
        if ($event->community_id) {
            $isAdmin = \App\Models\CommunityMembership::where('community_id', $event->community_id)
                ->where('user_id', $uid)
                ->whereIn('role', ['owner', 'admin', 'moderator'])
                ->where('status', 'active')
                ->exists();
            if ($isAdmin) {
                return true;
            }
        }

        return false;
    });
    $attendingEvents = $visibleUpcomingEvents->filter(
        fn($event) => $event->attendees->contains(
            fn($att) => $att->user_id === auth()->id() && in_array($att->status, ['accepted', 'waitlist']),
        ),
    );
    $eventsToShow = $activeNestedTab === 'Attending' ? $attendingEvents : $visibleUpcomingEvents;
@endphp
@php
    // Can the current user publish events in this community? Site admins or community owner/admin/moderator
    $canPublish = false;
    if (auth()->check() && auth()->user()->isSiteAdmin()) {
        $canPublish = true;
    } elseif ($community && auth()->check()) {
        $canPublish = \App\Models\CommunityMembership::where('community_id', $community->id)
            ->where('user_id', auth()->id())
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->where('status', 'active')
            ->exists();
    }
@endphp

<x-layout :community="$community" :communities="$communities">
    <div class="bg-gradient-to-b from-white to-gray-50/40 min-h-screen">
        @if ($community)
            <div class="p-8 mt-4 max-w-5xl mx-auto space-y-8">

                <div class="flex items-center justify-end mb-4">

                    @if ($community)
                        <a href="{{ route('create-event', ['community' => $community->slug]) }}"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Create Event
                        </a>
                    @endif

                </div>
                <div class="flex items-center justify-center mb-4">
                    <div
                        class="flex w-full bg-white/50 backdrop-blur-md rounded-full p-1 shadow-inner border border-white/40">
                        <button id="list-tab"
                            class="flex-1 px-6 py-2 text-center text-sm font-semibold rounded-full bg-white text-gray-900 border border-blue-400 transition">
                            Events
                        </button>
                        <button id="calendar-tab"
                            class="flex-1 px-6 py-2 text-center text-sm font-semibold text-gray-700 rounded-full hover:text-gray-900 transition">
                            Calendar
                        </button>
                    </div>
                </div>

                <!-- Calendar View -->
                <div id="calendar-view"
                    class="mt-7 space-y-6 bg-white/70 backdrop-blur-md p-6 rounded-2xl shadow-md border border-gray-100">
                    <div class="flex justify-between items-center text-black px-4 py-2 rounded-lg bg-gray-50">
                        <h2 id="calendar-month" class="font-bold text-lg"></h2>
                        <div class="flex gap-2">
                            <button id="prev-month"
                                class="px-3 py-1 rounded-full hover:bg-gray-200 transition">&lt;</button>
                            <button id="next-month"
                                class="px-3 py-1 rounded-full hover:bg-gray-200 transition">&gt;</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 text-center text-sm font-medium text-gray-500">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div id="calendar-days" class="grid grid-cols-7 text-center gap-y-4 text-gray-800"></div>
                    <div id="day-events" class="mt-6 space-y-4 hidden"></div>
                </div>

                <!-- Event List View -->
                <div id="list-view" class="hidden space-y-6">
                    <div class="flex border-b border-gray-200 mb-4 space-x-8">
                        <button id="upcoming-tab"
                            class="px-4 py-2 text-sm font-semibold text-blue-600 border-b-2 border-blue-600 transition-colors">
                            Upcoming ({{ $visibleUpcomingEvents->count() }})
                        </button>
                        <button id="attending-tab"
                            class="px-4 py-2 text-sm font-semibold text-gray-500 border-b-2 border-transparent transition-colors">
                            Attending ({{ $attendingEvents->count() }})
                        </button>
                    </div>

                    <div id="upcoming-view" class="space-y-6">
                        @if ($visibleUpcomingEvents->isEmpty())
                            <div
                                class=" p-8 w-full max-w-5xl h-64 mx-auto flex flex-col items-center justify-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 8h14a2 2 0 002-2V9a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <p class="text-gray-500 text-md text-center">No upcoming events — maybe create one?</p>
                            </div>
                        @else
                            @foreach ($visibleUpcomingEvents as $event)
                                @php
                                    $canManage = false;
                                    $uid = auth()->id();
                                    if ($uid) {
                                        if ($event->owner_id === $uid) {
                                            $canManage = true;
                                        }
                                        if (!$canManage) {
                                            $isHost = \App\Models\EventAttendee::where('event_id', $event->id)
                                                ->where('user_id', $uid)
                                                ->where('role', 'host')
                                                ->exists();
                                            if ($isHost) {
                                                $canManage = true;
                                            }
                                        }
                                        if (!$canManage && $event->community_id) {
                                            $isAdmin = \App\Models\CommunityMembership::where(
                                                'community_id',
                                                $event->community_id,
                                            )
                                                ->where('user_id', $uid)
                                                ->whereIn('role', ['owner', 'admin', 'moderator'])
                                                ->where('status', 'active')
                                                ->exists();
                                            if ($isAdmin) {
                                                $canManage = true;
                                            }
                                        }
                                    }

                                    $statusColor = match ($event->status) {
                                        'published' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'draft' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp

                                <!-- Event Card -->
                                <div
                                    class="bg-white/90 backdrop-blur-sm border border-blue-100 rounded-2xl shadow-md shadow-blue-100/50 p-5 relative transition-all duration-300 hover:shadow-lg hover:shadow-blue-200/70 hover:translate-y-[-2px]">

                                    <div class="p-6">
                                        <!-- Header -->
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <!-- Host Info -->
                                                <div class="flex items-center gap-2 -mt-2">
                                                    <div
                                                        class="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            {{ $event->owner->name ?? 'Community' }}
                                                        </p>
                                                        <p class="text-xs text-gray-500">Event Organizer</p>
                                                    </div>
                                                </div>

                                                <h3 class="text-xl font-semibold text-gray-900 mt-4">{{ $event->title }}
                                                </h3>
                                                <p class="text-sm text-gray-600 mt-1">{{ $event->description ?? '' }}
                                                </p>
                                            </div>

                                            <!-- Status / Draft Dropdown -->
                                            <div class="flex items-center gap-4">
                                                @if ($event->status === 'draft' && $canPublish)
                                                    <div class="relative inline-block text-left">
                                                        <button type="button"
                                                            class="draft-badge px-2 py-1 rounded text-xs font-medium {{ $statusColor }} focus:outline-none flex items-center gap-1"
                                                            data-event-id="{{ $event->id }}">
                                                            {{ ucfirst($event->status) }}
                                                            <svg class="ml-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M19 9l-7 7-7-7" />
                                                            </svg>
                                                        </button>

                                                        <div class="draft-menu origin-top-right absolute right-0 mt-2 w-32 shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-10"
                                                            id="draft-menu-{{ $event->id }}">
                                                            <div class="py-1">
                                                                <button
                                                                    onclick="approveEvent({{ $event->id }}, this)"
                                                                    class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                                                    Approve
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span
                                                        class="block w-full text-xs px-2 py-1 rounded font-semibold {{ $statusColor }}">
                                                        {{ ucfirst($event->status) }}
                                                    </span>
                                                @endif
                                                @if ($canManage && $activeNestedTab !== 'Attending')
                                                    <!-- Edit icon (pencil) -->
                                                    <a href="{{ route('edit-event', ['event' => $event->id]) }}"
                                                        class="text-blue-600 hover:text-blue-800 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 20h9M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z" />
                                                        </svg>

                                                    </a>

                                                    <!-- Delete icon (trash can) -->
                                                    <button onclick="deleteEvent({{ $event->id }}, this)"
                                                        class="text-red-600 hover:text-red-800 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
                                                        </svg>

                                                    </button>
                                                @endif
                                            </div>

                                        </div>

                                        <!-- Event Info Row -->
                                        <div class="mt-4 border-t border-pink-100 pt-3">
                                            <div class="flex flex-wrap gap-x-16 gap-y-6 text-sm text-gray-600">

                                                <!-- Date -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                        <!-- Calendar Icon -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Date</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->starts_at?->format('D, M j, Y') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Time -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                        <!-- Clock Icon -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 7v5l3 3" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Time</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->starts_at?->format('g:i A') }} –
                                                            {{ $event->ends_at?->format('g:i A') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Location -->
                                                @if ($event->location)
                                                    <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                        <span
                                                            class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                            <!-- Location Icon -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 11a3 3 0 100-6 3 3 0 000 6z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 22s8-4.5 8-11a8 8 0 10-16 0c0 6.5 8 11 8 11z" />
                                                            </svg>
                                                        </span>
                                                        <div>
                                                            <p class="text-gray-500 text-[11px] leading-tight">Location
                                                            </p>
                                                            <p class="font-medium text-gray-800 text-[13px]">
                                                                {{ $event->location }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Attendance -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">

                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M5 13l4 4L19 7M16 6a4 4 0 11-8 0 4 4 0 018 0zM4 20h16v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2z" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Capacity</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->accepted_count }}/{{ $event->capacity ?? '∞' }}
                                                            spots filled
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>


                                        <!-- Footer -->
                                        <div class="mt-6 flex justify-end items-center border-t border-gray-200 pt-4">


                                            <!-- Buttons -->
                                            <div class="flex gap-3 w-full">

                                                @php
                                                    $isAttending = $event->attendees->contains(
                                                        fn($att) => $att->user_id === auth()->id() &&
                                                            $att->status === 'accepted',
                                                    );
                                                @endphp
                                                <button
                                                    class="flex-1 px-4 py-2 rounded-full text-sm font-medium shadow-md transition-all duration-300 
                          {{ $isAttending
                              ? 'bg-gray-200 text-gray-400 cursor-default'
                              : 'bg-gradient-to-r from-blue-400 to-sky-500 text-white hover:shadow-lg hover:scale-[1.02]' }}"
                                                    @if (!$isAttending) onclick="sendRSVP({{ $event->id }}, 'accepted', this)" @endif
                                                    @if ($isAttending) disabled @endif>
                                                    {{ $isAttending ? 'Attending' : 'RSVP to Event' }}
                                                </button>


                                                <a href="{{ route('event.details', ['event' => $event->id]) }}"
                                                    class="flex-1 px-4 py-2 rounded-full border border-blue-200 bg-white/60 backdrop-blur-md text-blue-700 hover:bg-blue-50 text-sm font-medium shadow-sm hover:shadow-md transition-all duration-300 text-center">
                                                    View Details
                                                </a>

                                            </div>


                                        </div>
                                        <div class="text-xs text-gray-400 mt-9">
                                            Created {{ $event->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>


                    <div id="attending-view" class="hidden space-y-6">
                        @if ($attendingEvents->isEmpty())
                            <div
                                class=" p-8 w-full max-w-5xl h-64 mx-auto flex flex-col items-center justify-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10m-12 8h14a2 2 0 002-2V9a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <p class="text-gray-500 text-md text-center">You're not attending any events yet.</p>
                            </div>
                        @else
                            @foreach ($attendingEvents as $event)
                                @php
                                    $canManage = false;
                                    $uid = auth()->id();
                                    if ($uid) {
                                        if ($event->owner_id === $uid) {
                                            $canManage = true;
                                        }
                                        if (!$canManage) {
                                            $isHost = \App\Models\EventAttendee::where('event_id', $event->id)
                                                ->where('user_id', $uid)
                                                ->where('role', 'host')
                                                ->exists();
                                            if ($isHost) {
                                                $canManage = true;
                                            }
                                        }
                                        if (!$canManage && $event->community_id) {
                                            $isAdmin = \App\Models\CommunityMembership::where(
                                                'community_id',
                                                $event->community_id,
                                            )
                                                ->where('user_id', $uid)
                                                ->whereIn('role', ['owner', 'admin', 'moderator'])
                                                ->where('status', 'active')
                                                ->exists();
                                            if ($isAdmin) {
                                                $canManage = true;
                                            }
                                        }
                                    }

                                    $statusColor =
                                        $event->status === 'draft'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-green-100 text-green-700';
                                @endphp

                                <div
                                    class="bg-white/90 backdrop-blur-sm border border-blue-100 rounded-2xl shadow-md shadow-blue-100/50 p-5 relative transition-all duration-300 hover:shadow-lg hover:shadow-blue-200/70 hover:translate-y-[-2px]">

                                    <div class="p-6">

                                        <div class="flex justify-between items-start mb-3">

                                            <div>
                                                <!-- Host Info -->
                                                <div class="flex items-center gap-2 -mt-2">
                                                    <div
                                                        class="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            {{ $event->owner->name ?? 'Community' }}
                                                        </p>
                                                        <p class="text-xs text-gray-500">Event Organizer</p>
                                                    </div>
                                                </div>

                                                <h3 class="text-xl font-semibold text-gray-900 mt-4">
                                                    {{ $event->title }}
                                                </h3>
                                                <p class="text-sm text-gray-600 mt-1">{{ $event->description ?? '' }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="px-2 py-1 rounded-md text-xs font-semibold {{ $statusColor }} capitalize">
                                                    {{ ucfirst($event->status) }}
                                                </span>

                                            </div>
                                        </div>

                                        <!-- Event Info Row -->
                                        <div class="mt-4 border-t border-gray-200 pt-3">
                                            <div class="flex flex-wrap gap-x-16 gap-y-6 text-sm text-gray-600">

                                                <!-- Date -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                        <!-- Calendar Icon -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Date</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->starts_at?->format('D, M j, Y') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Time -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                        <!-- Clock Icon -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <circle cx="12" cy="12" r="9" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 7v5l3 3" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Time</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->starts_at?->format('g:i A') }} –
                                                            {{ $event->ends_at?->format('g:i A') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Location -->
                                                @if ($event->location)
                                                    <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                        <span
                                                            class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                                            <!-- Location Icon -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 11a3 3 0 100-6 3 3 0 000 6z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 22s8-4.5 8-11a8 8 0 10-16 0c0 6.5 8 11 8 11z" />
                                                            </svg>
                                                        </span>
                                                        <div>
                                                            <p class="text-gray-500 text-[11px] leading-tight">Location
                                                            </p>
                                                            <p class="font-medium text-gray-800 text-[13px]">
                                                                {{ $event->location }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Attendance -->
                                                <div class="flex items-start gap-2 px-4 py-3 w-full sm:w-auto">
                                                    <span
                                                        class="bg-gradient-to-br from-blue-100 to-sky-100 text-blue-600 p-2 rounded-xl shadow-sm">

                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M5 13l4 4L19 7M16 6a4 4 0 11-8 0 4 4 0 018 0zM4 20h16v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2z" />
                                                        </svg>
                                                    </span>
                                                    <div>
                                                        <p class="text-gray-500 text-[11px] leading-tight">Capacity</p>
                                                        <p class="font-medium text-gray-800 text-[13px]">
                                                            {{ $event->accepted_count }}/{{ $event->capacity ?? '∞' }}
                                                            spots filled
                                                        </p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="mt-6 flex justify-end items-center border-t border-gray-200 pt-4">
                                            <!-- Buttons -->
                                            <div class="flex gap-3 w-full">
                                                <button onclick="sendRSVP({{ $event->id }}, 'declined', this)"
                                                    class="flex-1 px-4 py-2 rounded-full text-sm font-medium shadow-md transition-all duration-300 bg-gradient-to-r from-blue-400 to-sky-500 text-white hover:shadow-lg hover:scale-[1.02]">
                                                    Leave Event
                                                </button>
                                                <a href="{{ route('event.details', ['event' => $event->id]) }}"
                                                    class="flex-1 px-4 py-2 rounded-full border border-blue-200 bg-white/60 backdrop-blur-md text-blue-700 hover:bg-blue-50 text-sm font-medium shadow-sm hover:shadow-md transition-all duration-300 text-center">
                                                    View Details
                                                </a>

                                            </div>

                                        </div>
                                        <div class="text-xs text-gray-400 mt-9">
                                            Created {{ $event->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                    </div>
        @endforeach
        @endif
    </div>
    </div>
    </div>
@else
    <div class="min-h-screen flex flex-col items-center"></div>
    @endif
    </div>

    @php
        $eventsForCalendar = $visibleUpcomingEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at?->format('Y-m-d\TH:i:s'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i:s'),
                'owner' => ['name' => $event->owner->name ?? 'Community'],
                'description' => $event->description,
                'location' => $event->location,
                'status' => $event->status,
                'visibility' => $event->visibility,
                'capacity' => $event->capacity,
                'community' => ['name' => $event->community->name ?? 'Community'],
                'attendees' => $event->attendees->map(fn($a) => ['user' => ['name' => $a->user->name ?? 'Unknown']]),
            ];
        });
    @endphp

    <script>
        // Tab elements
        const calendarTab = document.getElementById('calendar-tab');
        const listTab = document.getElementById('list-tab');
        const calendarView = document.getElementById('calendar-view');
        const listView = document.getElementById('list-view');

        // Function to activate the correct tab visually
        function activateTab(activeTab, inactiveTab) {
            // Show/hide views
            if (activeTab === calendarTab) {
                calendarView.classList.remove('hidden');
                listView.classList.add('hidden');
            } else {
                listView.classList.remove('hidden');
                calendarView.classList.add('hidden');
            }

            // Active (white, blue border)
            activeTab.classList.add('bg-white', 'text-gray-900', 'border', 'border-blue-400', 'shadow-sm');
            activeTab.classList.remove('bg-gray-200', 'text-gray-700');

            // Inactive (gray)
            inactiveTab.classList.remove('bg-white', 'border', 'border-blue-400', 'shadow-sm', 'text-gray-900');
            inactiveTab.classList.add('bg-gray-200', 'text-gray-700');
        }

        // Default: Event List active on load
        activateTab(listTab, calendarTab);

        // Click handlers
        listTab.addEventListener('click', () => activateTab(listTab, calendarTab));
        calendarTab.addEventListener('click', () => activateTab(calendarTab, listTab));



        calendarTab.addEventListener('click', () => {
            calendarView.classList.remove('hidden');
            listView.classList.add('hidden');

            calendarTab.classList.add('text-blue-600');
            calendarTab.classList.remove('text-gray-500');

            listTab.classList.remove('text-blue-600');
            listTab.classList.add('text-gray-500');
        });

        listTab.addEventListener('click', () => {
            listView.classList.remove('hidden');
            calendarView.classList.add('hidden');

            listTab.classList.add('text-blue-600');
            listTab.classList.remove('text-gray-500');

            calendarTab.classList.remove('text-blue-600');
            calendarTab.classList.add('text-gray-500');
        });


        // Nested tabs for list
        const upcomingTab = document.getElementById('upcoming-tab');
        const attendingTab = document.getElementById('attending-tab');
        const upcomingView = document.getElementById('upcoming-view');
        const attendingView = document.getElementById('attending-view');

        upcomingTab.addEventListener('click', () => {
            upcomingView.classList.remove('hidden');
            attendingView.classList.add('hidden');
            upcomingTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            attendingTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        });

        attendingTab.addEventListener('click', () => {
            attendingView.classList.remove('hidden');
            upcomingView.classList.add('hidden');
            attendingTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            upcomingTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        });

        // Calendar rendering
        const events = @json($eventsForCalendar);
        const calendarDays = document.getElementById('calendar-days');
        const calendarMonthLabel = document.getElementById('calendar-month');
        const dayEvents = document.getElementById('day-events');

        let currentDate = new Date();

        function renderCalendar() {
            calendarDays.textContent = '';
            dayEvents.textContent = '';

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            calendarMonthLabel.textContent = currentDate.toLocaleString('default', {
                month: 'long',
                year: 'numeric'
            });

            const firstDay = new Date(year, month, 1).getDay();
            const lastDate = new Date(year, month + 1, 0).getDate();
            const prevLastDate = new Date(year, month, 0).getDate();

            const today = new Date();
            const isThisMonth = today.getFullYear() === year && today.getMonth() === month;
            let selectedCell = null;

            // --- PREVIOUS MONTH DAYS ---
            for (let i = firstDay - 1; i >= 0; i--) {
                const dayNum = prevLastDate - i;
                const cell = document.createElement('div');
                cell.textContent = dayNum;
                cell.className =
                    'flex items-center justify-center text-gray-300 text-center w-10 h-10 mx-auto cursor-pointer hover:bg-blue-100 rounded-full transition';
                cell.addEventListener('click', () => {
                    currentDate.setMonth(month - 1);
                    renderCalendar();
                    setTimeout(() => selectDay(dayNum), 0);
                });
                calendarDays.appendChild(cell);
            }

            // --- CURRENT MONTH DAYS ---
            for (let d = 1; d <= lastDate; d++) {
                const cell = document.createElement('div');
                cell.className =
                    'relative flex items-center justify-center text-center cursor-pointer transition rounded-full w-10 h-10 mx-auto hover:bg-blue-200';
                cell.textContent = d;

                const dayDate = new Date(year, month, d);
                const dayDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const dayEventsList = events.filter(e => e.starts_at?.slice(0, 10) === dayDateStr);

                if (dayEventsList.length > 0) {
                    const dot = document.createElement('span');
                    dot.className =
                        'absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-blue-600 rounded-full';
                    cell.appendChild(dot);
                }

                if (isThisMonth && d === today.getDate()) {
                    cell.classList.add('bg-blue-100', 'text-blue-700', 'font-semibold');
                }

                cell.addEventListener('click', () => {
                    handleDayClick(cell, dayDate, dayEventsList);
                });

                calendarDays.appendChild(cell);
            }

            // --- NEXT MONTH DAYS ---
            const remaining = 42 - calendarDays.children.length;
            for (let i = 1; i <= remaining; i++) {
                const cell = document.createElement('div');
                cell.textContent = i;
                cell.className =
                    'flex items-center justify-center text-gray-300 text-center w-10 h-10 mx-auto cursor-pointer hover:bg-blue-100 rounded-full transition';
                cell.addEventListener('click', () => {
                    currentDate.setMonth(month + 1);
                    renderCalendar();
                    setTimeout(() => selectDay(i), 0);
                });
                calendarDays.appendChild(cell);
            }

            // --- Default message ---
            const defaultMsg = document.createElement('div');
            defaultMsg.className = 'border-t border-gray-300 pt-2';
            const msgRow = document.createElement('div');
            msgRow.className = 'flex items-center space-x-2 text-gray-500';

            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('class', 'h-4 w-4 text-blue-600');
            icon.setAttribute('fill', 'none');
            icon.setAttribute('viewBox', '0 0 24 24');
            icon.setAttribute('stroke', 'currentColor');
            icon.setAttribute('stroke-width', '2');
            icon.innerHTML =
                `<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />`;

            const msgText = document.createElement('span');
            msgText.textContent = 'Select a date with a blue dot to view events for that day.';

            msgRow.appendChild(icon);
            msgRow.appendChild(msgText);
            defaultMsg.appendChild(msgRow);
            dayEvents.appendChild(defaultMsg);
            dayEvents.classList.remove('hidden');

            // --- Click handler ---
            function handleDayClick(cell, dayDate, dayEventsList) {
                if (selectedCell && selectedCell !== cell) {
                    selectedCell.classList.remove('bg-blue-600', 'text-white');
                    if (isThisMonth && selectedCell.textContent == today.getDate()) {
                        selectedCell.classList.add('bg-blue-100', 'text-blue-700');
                    } else {
                        selectedCell.classList.remove('bg-blue-100', 'text-blue-700');
                    }
                    const prevDot = selectedCell.querySelector('span');
                    if (prevDot) prevDot.classList.replace('bg-white', 'bg-blue-600');
                }

                cell.classList.remove('bg-blue-100', 'text-blue-700');
                cell.classList.add('bg-blue-600', 'text-white');
                selectedCell = cell;

                const dot = cell.querySelector('span');
                if (dot) dot.classList.replace('bg-blue-600', 'bg-white');

                dayEvents.textContent = '';
                const divider = document.createElement('div');
                divider.className = 'border-t border-gray-300 mt-2 mb-2';
                dayEvents.appendChild(divider);

                if (dayEventsList.length) {
                    dayEventsList.forEach(ev => {
                        const card = document.createElement('div');
                        card.className = 'border border-gray-200 p-2 shadow-sm';

                        const wrapper = document.createElement('div');
                        wrapper.className = 'space-y-1 text-sm text-gray-800';

                        const title = document.createElement('p');
                        title.className = 'font-semibold';
                        title.textContent = ev.title;

                        const timeRow = document.createElement('div');
                        timeRow.className = 'flex items-center gap-1 text-gray-600';

                        const clockIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        clockIcon.setAttribute('class', 'h-4 w-4 text-blue-600');
                        clockIcon.setAttribute('fill', 'none');
                        clockIcon.setAttribute('viewBox', '0 0 24 24');
                        clockIcon.setAttribute('stroke', 'currentColor');
                        clockIcon.setAttribute('stroke-width', '2');
                        clockIcon.innerHTML =
                            `<circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />`;

                        const start = ev.starts_at ? new Date(ev.starts_at) : null;
                        const end = ev.ends_at ? new Date(ev.ends_at) : null;
                        const startTime = formatEventTime(ev.starts_at);
                        const endTime = end ? end.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true,
                            timeZone: 'America/New_York'
                        }) : '';

                        const timeText = document.createElement('span');
                        timeText.textContent = `${startTime}${endTime ? ` – ${endTime}` : ''}`;

                        timeRow.appendChild(clockIcon);
                        timeRow.appendChild(timeText);
                        wrapper.appendChild(title);
                        wrapper.appendChild(timeRow);
                        card.appendChild(wrapper);
                        dayEvents.appendChild(card);
                    });
                } else {
                    const formattedDate = dayDate.toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    });

                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'border-t border-gray-300 pt-2';

                    const p = document.createElement('p');
                    p.className = 'text-gray-500';
                    p.textContent = `No events on ${formattedDate}.`;

                    emptyMsg.appendChild(p);
                    dayEvents.appendChild(emptyMsg);
                }

                dayEvents.classList.remove('hidden');
            }

            function selectDay(day) {
                const dayCells = calendarDays.querySelectorAll('div');
                const target = [...dayCells].find(cell => cell.textContent == day && cell.classList.contains(
                    'cursor-pointer'));
                if (target) target.click();
            }
        }
        renderCalendar();

        function setActiveTab(active, inactive, activeView, inactiveView) {
            activeView.classList.remove('hidden');
            inactiveView.classList.add('hidden');

            active.classList.add('text-blue-600', 'border-blue-600');
            active.classList.remove('text-gray-500', 'border-transparent');

            inactive.classList.remove('text-blue-600', 'border-blue-600');
            inactive.classList.add('text-gray-500', 'border-transparent');
        }

        upcomingTab.addEventListener('click', () =>
            setActiveTab(upcomingTab, attendingTab, upcomingView, attendingView)
        );
        attendingTab.addEventListener('click', () =>
            setActiveTab(attendingTab, upcomingTab, attendingView, upcomingView)
        );

        // Set default tab on load
        setActiveTab(upcomingTab, attendingTab, upcomingView, attendingView);

        const params = new URLSearchParams(window.location.search);
        const initialTab = params.get('tab');

        // Activate the correct main tab if present
        if (initialTab === 'calendar') {
            activateTab(calendarTab, listTab);
        } else if (initialTab === 'list') {
            activateTab(listTab, calendarTab);
        }

        async function sendRSVP(eventId, status, button) {
            if (button) button.disabled = true;

            try {
                const res = await fetch(`/events/${eventId}/rsvp`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        status
                    })
                });

                let data = null;
                try {
                    data = await res.json();
                } catch (e) {}

                if (res.status === 200) {
                    showToastify('RSVP updated successfully.', 'success');
                    // Refresh the page when the RSVP is successful
                    window.location.reload();
                } else if (res.status === 202) {
                    const pos = data?.waitlist_position ?? 'unknown';
                    const size = data?.waitlist_size ?? 'unknown';
                    showToastify(`Added to waitlist: #${pos} of ${size}`, 'confirm');
                } else {
                    const msg = data?.message ?? 'Failed to update RSVP.';
                    showToastify(msg, 'error');
                }

                setTimeout(() => window.location.reload(), 1500);

            } catch (err) {
                console.error(err);
                showToastify('Failed to RSVP due to a network error.', 'error');
                if (!navigator.onLine) showToastify('You appear to be offline.', 'error');
            } finally {
                if (button) button.disabled = false;
            }
        }


        function formatEventTime(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'America/New_York'
            });
        }

        function formatEventDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                timeZone: 'America/New_York'
            });
        }

        function confirmToast(message, onConfirm) {
            showToast(`${message}`, 'alert', 3000, [{
                    text: 'Cancel',
                    type: 'alert',
                    onClick: () => {} // no-op
                },
                {
                    text: 'Delete',
                    type: 'confirm',
                    onClick: onConfirm
                }
            ]);
        }

        async function deleteEvent(eventId, button) {
            showConfirmToast('Are you sure you want to delete this event?', async () => {
                    button.disabled = true;
                    const original = button.textContent;
                    button.textContent = '...';

                    try {
                        const res = await fetch(`/events/${eventId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                'Content-Type': 'application/json'
                            }
                        });

                        if (res.ok) {
                            const params = new URLSearchParams(window.location.search);
                            const community = params.get('community');
                            window.location.href =
                                `/events${community ? '?community=' + community + '&tab=list' : '?tab=list'}`;
                        } else {
                            const data = await res.json().catch(() => ({}));
                            showToastify(data.message || 'Failed to delete event.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showToastify('Failed to delete event.', 'error');
                    } finally {
                        button.disabled = false;
                        button.textContent = original;
                    }
                },
                'bg-red-400 hover:bg-red-500',
                'Delete');
        }


        async function approveEvent(eventId, button) {
            showConfirmToast('Are you sure you want to publish the event?', async () => {
                    button.disabled = true;
                    const original = button.textContent;
                    button.textContent = 'Approving...';

                    try {
                        const res = await fetch(`/events/${eventId}/approve`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                'Content-Type': 'application/json'
                            }
                        });

                        if (res.ok) {
                            window.location.reload();
                        } else {
                            const data = await res.json().catch(() => ({}));
                            showToastify(data.message || 'Failed to approve event.', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showToastify('Failed to approve event.', 'error');
                    } finally {
                        button.disabled = false;
                        button.textContent = original;
                    }
                },
                'bg-blue-500 hover:bg-blue-600',
                'Publish');
        }


        function closeEventDetails() {
            document.getElementById('view-event-modal').classList.add('hidden');
        }
        document.querySelectorAll('.draft-badge').forEach(btn => {
            const eventId = btn.dataset.eventId;
            const menu = document.getElementById(`draft-menu-${eventId}`);

            btn.addEventListener('click', e => {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
        });

        // Close dropdowns if clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.draft-menu').forEach(menu => menu.classList.add('hidden'));
        });
    </script>

</x-layout>
