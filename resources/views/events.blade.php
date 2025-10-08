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
    $attendingEvents = $allUpcomingEvents->filter(
        fn($event) => $event->attendees->contains(
            fn($att) => $att->user_id === auth()->id() && in_array($att->status, ['accepted', 'waitlist']),
        ),
    );
    $eventsToShow = $activeNestedTab === 'Attending' ? $attendingEvents : $allUpcomingEvents;
@endphp <x-layout :community="$community" :communities="$communities">
    <div class="bg-gray-50 min-h-screen">
        @if ($community)
            <div class="bg-white shadow p-6 mt-6 max-w-6xl mx-auto">
                <div class="flex items-center justify-between mb-4">
                    <div> </div> <button id="open-event-form"
                        class="px-4 py-2 bg-blue-500 text-white text-sm hover:bg-blue-600"> + Create Event </button>
                </div>
                <div class="flex border border-gray-300 rounded-lg overflow-hidden mb-6"> <button id="calendar-tab"
                        class="flex-1 px-4 py-2 text-center font-medium text-gray-600 border-r border-gray-300">Calendar
                        View</button> <button id="list-tab"
                        class="flex-1 px-4 py-2 text-center font-medium text-gray-600">Event List</button> </div>
                <!-- Calendar View -->
                <div id="calendar-view" class="space-y-4">
                    <div class="flex justify-between items-center text-black px-4 py-2 rounded-lg">
                        <h2 id="calendar-month" class="font-semibold"></h2>
                        <div class="flex gap-2"> <button id="prev-month"
                                class="px-2 py-1 rounded hover:bg-gray-300">&lt;</button> <button id="next-month"
                                class="px-2 py-1 rounded hover:bg-gray-300">&gt;</button> </div>
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
                </div> <!-- Event List View -->
                <div id="list-view" class="hidden space-y-4">
                    <div class="flex border-b border-gray-200 mb-4"> <button id="upcoming-tab"
                            class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600">Upcoming
                            ({{ $allUpcomingEvents->count() }})</button> <button id="attending-tab"
                            class="px-4 py-2 text-sm font-medium text-gray-500">Attending
                            ({{ $attendingEvents->count() }})</button> </div>
                    <div id="upcoming-view" class="space-y-4">
                        @foreach ($allUpcomingEvents as $event)
                            <div class="border border-gray-200 p-4 shadow-sm hover:shadow transition">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $event->title }}</h4>
                                        <p class="text-xs text-gray-500">Hosted by
                                            {{ $event->owner->name ?? 'Community' }} </p>
                                    </div> <span
                                        class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded capitalize">{{ $event->status }}</span>
                                </div>
                                <div class="flex flex-wrap gap-6 text-sm text-gray-500 mb-3"> <span>📅
                                        {{ $event->starts_at?->format('Y-m-d') }}</span> <span>⏰
                                        {{ $event->starts_at?->format('g:i A') }} -
                                        {{ $event->ends_at?->format('g:i A') ?? '' }}</span> </div>
                                <div class="flex justify-end gap-2 text-sm"> <button
                                        onclick="showEventDetails({{ $event->id }})"
                                        class="px-3 py-1 border border-gray-300 text-gray-700 hover:bg-gray-100">View
                                        Details</button>
                                    <div class="mt-2"> <label for="rsvp-{{ $event->id }}"
                                            class="text-xs text-gray-500">RSVP:</label> <select
                                            id="rsvp-{{ $event->id }}" class="ml-2 px-2 py-1 border text-sm"
                                            onchange="updateRSVP({{ $event->id }}, this.value)">
                                            <option value="">--Select--</option>
                                            <option value="accepted"
                                                {{ optional($event->attendees->firstWhere('user_id', auth()->id()))?->status === 'accepted' ? 'selected' : '' }}>
                                                Accepted</option>
                                            <option value="waitlist"
                                                {{ optional($event->attendees->firstWhere('user_id', auth()->id()))?->status === 'waitlist' ? 'selected' : '' }}>
                                                Waitlisted</option>
                                            <option value="declined"
                                                {{ optional($event->attendees->firstWhere('user_id', auth()->id()))?->status === 'declined' ? 'selected' : '' }}>
                                                Declined</option>
                                        </select> </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="attending-view" class="hidden space-y-4">
                        @foreach ($attendingEvents as $event)
                            <div class="border border-gray-200 rounded p-4 shadow-sm hover:shadow transition">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $event->title }}</h4>
                                        <p class="text-sm text-gray-600">{{ $event->description }}</p>
                                        <p class="text-xs text-gray-500">Hosted by
                                            {{ $event->owner->name ?? 'Community' }} </p>
                                    </div> <span
                                        class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded capitalize">{{ $event->status }}</span>
                                </div>
                                <div class="flex flex-wrap gap-6 text-sm text-gray-500 mb-3"> <span>📅
                                        {{ $event->starts_at?->format('Y-m-d') }}</span> <span>⏰
                                        {{ $event->starts_at?->format('H:i') }} -
                                        {{ $event->ends_at?->format('H:i') ?? '' }}</span> <span>📍
                                        {{ $event->location }}</span> </div>
                                <div class="flex justify-end gap-2 text-sm"> <button
                                        onclick="showEventDetails({{ $event->id }})"
                                        class="px-3 py-1 border border-gray-300 text-gray-700 hover:bg-gray-100 rounded">View
                                        Details</button> <button
                                        onclick="sendRSVP({{ $event->id }}, 'declined', this)"
                                        class="px-3 py-1 bg-gray-300 hover:bg-gray-400 rounded">Leave Event</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
    </div> <!-- Create Event Modal -->
    <div id="event-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 w-96 shadow-lg rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Create Event</h2> <button onclick="toggleEventModal()"
                    class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <form id="create-event-form"> @csrf <label class="block text-sm font-semibold mb-1">Event Title</label>
                <input type="text" name="title" required class="w-full p-2 border mb-3" /> <label
                    class="block text-sm font-semibold mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full p-2 border mb-3"></textarea> <label class="block text-sm font-semibold mb-1">Location</label> <input
                    type="text" name="location" class="w-full p-2 border mb-3" /> <label
                    class="block text-sm font-semibold mb-1">Starts At</label> <input type="datetime-local"
                    name="starts_at" required class="w-full p-2 border mb-3" /> <label
                    class="block text-sm font-semibold mb-1">Ends At</label> <input type="datetime-local" name="ends_at"
                    class="w-full p-2 border mb-3" /> <label class="block text-sm font-semibold mb-1">Capacity</label>
                <input type="number" name="capacity" min="1" class="w-full p-2 border mb-3" /> <label
                    class="block text-sm font-semibold mb-1">Visibility</label> <select name="visibility"
                    class="w-full p-2 border mb-3">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select> <label class="block text-sm font-semibold mb-1">Status</label> <select name="status"
                    class="w-full p-2 border mb-3">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="cancelled">Cancelled</option>
                </select> <button type="submit" class="bg-blue-500 text-white w-full py-2 hover:bg-blue-600">Create
                    Event</button>
            </form>
        </div>
    </div> <!-- View Event Modal -->
    <div id="view-event-modal"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50"
        onclick="if(event.target.id==='view-event-modal') closeEventDetails()">
        <div class="bg-white p-6 w-[520px] shadow-lg relative"> <button onclick="closeEventDetails()"
                class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">✕</button>
            <div id="event-details-content" class="space-y-4">
                <p class="text-gray-500 text-center">Loading...</p>
            </div>
        </div>
    @else
        <div class="min-h-screen flex flex-col items-center"> </div>
        @endif
    </div>

    <script>
        // Tabs switching
        const calendarTab = document.getElementById('calendar-tab');
        const listTab = document.getElementById('list-tab');
        const calendarView = document.getElementById('calendar-view');
        const listView = document.getElementById('list-view');

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
        const events = @json($allUpcomingEvents);
        const calendarDays = document.getElementById('calendar-days');
        const calendarMonthLabel = document.getElementById('calendar-month');
        const dayEvents = document.getElementById('day-events');

        let currentDate = new Date();

        function renderCalendar() {
            calendarDays.innerHTML = '';
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            calendarMonthLabel.textContent = currentDate.toLocaleString('default', {
                month: 'long',
                year: 'numeric'
            });

            const firstDay = new Date(year, month, 1).getDay();
            const lastDate = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                calendarDays.appendChild(document.createElement('div'));
            }

            for (let d = 1; d <= lastDate; d++) {
                const dateCell = document.createElement('div');
                dateCell.className = 'p-2 border rounded cursor-pointer hover:bg-gray-100';
                dateCell.textContent = d;

                const dayDateStr = `${year}-${String(month + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const dayEventsList = events.filter(e => e.starts_at?.slice(0, 10) === dayDateStr);

                if (dayEventsList.length > 0) {
                    const dot = document.createElement('span');
                    dot.className = 'block w-2 h-2 bg-blue-600 rounded-full mx-auto mt-1';
                    dateCell.appendChild(dot);
                }

                dateCell.addEventListener('click', () => {
                    dayEvents.innerHTML = '';
                    if (dayEventsList.length) {
                        dayEventsList.forEach(ev => {
                            const evDiv = document.createElement('div');
                            evDiv.className = 'border border-gray-200 rounded p-2 shadow-sm';
                            const startTime = new Date(ev.starts_at).toLocaleTimeString([], {
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                            evDiv.innerHTML = `<strong>${ev.title}</strong> - ${startTime}`;
                            dayEvents.appendChild(evDiv);
                        });
                    } else {
                        dayEvents.innerHTML = '<p class="text-gray-500">No events this day.</p>';
                    }
                    dayEvents.classList.remove('hidden');
                });

                calendarDays.appendChild(dateCell);
            }
        }

        document.getElementById('prev-month').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });
        document.getElementById('next-month').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        renderCalendar();

        // Modal handling
        const eventModal = document.getElementById('event-modal');
        const openEventBtn = document.getElementById('open-event-form');

        function toggleEventModal() {
            eventModal.classList.toggle('hidden');
        }

        openEventBtn.addEventListener('click', toggleEventModal);
        eventModal.addEventListener('click', e => {
            if (e.target === eventModal) toggleEventModal();
        });

        // Event creation form
        const form = document.getElementById('create-event-form');
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('community_id', '{{ $community?->id ?? '' }}');

            try {
                const res = await fetch('/events', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: formData
                });

                if (!res.ok) throw new Error('Failed');
                const event = await res.json();

                // Add to Upcoming list
                const container = document.getElementById('upcoming-view');
                const startTime = new Date(event.starts_at).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                const endTime = event.ends_at ? new Date(event.ends_at).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : '';
                const div = document.createElement('div');
                div.className = 'border border-gray-200 rounded p-4 shadow-sm hover:shadow transition';
                div.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">${event.title}</h4>
                        <p class="text-sm text-gray-600">${event.description || ''}</p>
                        <p class="text-xs text-gray-500">Hosted by ${event.owner?.name || 'Community'}</p>
                    </div>
                    <span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded capitalize">${event.status}</span>
                </div>
                <div class="flex flex-wrap gap-6 text-sm text-gray-500 mb-3">
                    <span>📅 ${new Date(event.starts_at).toLocaleDateString()}</span>
                    <span>⏰ ${startTime} - ${endTime}</span>
                    <span>📍 ${event.location || ''}</span>
                </div>
                <div class="flex justify-end gap-2 text-sm">
                    <button onclick="showEventDetails(${event.id})" class="px-3 py-1 border border-gray-300 text-gray-700 hover:bg-gray-100 rounded">View Details</button>
                    <button onclick="sendRSVP(${event.id}, 'accepted', this)" class="px-3 py-1 bg-blue-600 text-white hover:bg-blue-700 rounded">RSVP</button>
                </div>
            `;
                container.prepend(div);

                // Add to calendar and re-render
                events.push(event);
                renderCalendar();

                toggleEventModal();
                form.reset();
            } catch (err) {
                console.error(err);
                alert('Failed to create event.');
            }
        });

        async function sendRSVP(eventId, status, button) {
            button.disabled = true;
            const original = button.textContent;
            button.textContent = '...';
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
                const data = await res.json();
                if (res.status === 200) alert(`RSVP ${data.status}`);
                else if (res.status === 202) alert(`Waitlist #${data.waitlist_position}/${data.waitlist_size}`);
                window.location.reload();
            } catch {
                alert('Failed to RSVP.');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }

        async function showEventDetails(eventId) {
            const modal = document.getElementById('view-event-modal');
            const content = document.getElementById('event-details-content');
            modal.classList.remove('hidden');
            content.innerHTML = `<p class="text-gray-500 text-center">Loading...</p>`;
            try {
                const res = await fetch(`/events/${eventId}`);
                const event = await res.json();
                const startTime = new Date(event.starts_at).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                const endTime = event.ends_at ? new Date(event.ends_at).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : 'N/A';
                content.innerHTML = `
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">${event.title}</h2>
                    <p class="text-sm text-gray-500">Hosted by <strong>${event.owner?.name || 'Unknown'}</strong> in <strong>${event.community?.name || 'Community'}</strong></p>
                </div>
                <div class="border-t pt-4 space-y-2">
                    <p><b>Description:</b><br>${event.description || 'No description'}</p>
                    <p><b>Location:</b> ${event.location || 'N/A'}</p>
                    <p><b>Starts:</b> ${new Date(event.starts_at).toLocaleDateString()} ${startTime}</p>
                    <p><b>Ends:</b> ${event.ends_at ? new Date(event.ends_at).toLocaleDateString() + ' ' + endTime : 'N/A'}</p>
                    <p><b>Capacity:</b> ${event.capacity || 'Unlimited'}</p>
                    <p><b>Visibility:</b> ${event.visibility}</p>
                    <p><b>Status:</b> <span class="capitalize">${event.status}</span></p>
                </div>
                <div class="border-t pt-4">
                    <h3 class="font-semibold">Attendees (${event.attendees?.length || 0}/${event.capacity || '∞'})</h3>
                    ${event.attendees?.length ? `<ul>${event.attendees.map(a => `<li>${a.user?.name || 'Unknown'}</li>`).join('')}</ul>` : '<p>No attendees yet.</p>'}
                </div>
            
            `;
            } catch {
                content.innerHTML = `<p class="text-red-600 text-center">Error loading event.</p>`;
            }
        }

        function closeEventDetails() {
            document.getElementById('view-event-modal').classList.add('hidden');
        }
    </script>
</x-layout>
