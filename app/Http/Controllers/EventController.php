<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Abort unless current user may manage the event (owner, event host, or community admin/owner).
     */
    protected function authorizeManage(Event $event): void
    {
        $uid = Auth::id();

        // Owner
        if ($event->owner_id === $uid) return;

        // Event host
        $isHost = EventAttendee::where('event_id', $event->id)
            ->where('user_id', $uid)
            ->where('role', 'host')
            ->exists();
        if ($isHost) return;

        // Community admin/owner (if event belongs to community)
        if ($event->community_id) {
            $isAdmin = \App\Models\CommunityMembership::where('community_id', $event->community_id)
                ->where('user_id', $uid)
                ->whereIn('role', ['owner','admin'])
                ->where('status', 'active')
                ->exists();
            if ($isAdmin) return;
        }

        abort(403);
    }

    /**
     * Authorization for checkin: same as manage for now.
     */
    protected function authorizeCheckin(Event $event): void
    {
        $this->authorizeManage($event);
    }
    public function index(Request $request)
    {
        $q = Event::query()
            ->when($request->filled('community'), fn($qq) => $qq->where('community_id', $request->community))
            ->when($request->filled('q'), fn($qq) => $qq->where('title', 'like', '%' . $request->q . '%'))
            ->orderBy('starts_at', 'asc');

        return response()->json($q->paginate(12));
    }

    public function show(Event $event)
    {
        return response()->json($event->load(['owner:id,name','community:id,slug,name','attendees']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:200'],
            'description' => ['nullable','string'],
            'community_id' => ['nullable','exists:communities,id'],
            'location' => ['nullable','string','max:255'],
            'starts_at' => ['required','date'],
            'ends_at' => ['nullable','date','after_or_equal:starts_at'],
            'capacity' => ['nullable','integer','min:1'],
            'visibility' => ['nullable','in:public,private'],
            'status' => ['nullable','in:draft,published,cancelled'],
        ]);

        $data['owner_id'] = Auth::id();
        $data['visibility'] = $data['visibility'] ?? 'public';
        $data['status'] = $data['status'] ?? 'draft';

        $event = Event::create($data);
        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        // Authorization: owner, host, or community admin
        $this->authorizeManage($event);

        $data = $request->validate([
            'title' => ['sometimes','string','max:200'],
            'description' => ['sometimes','nullable','string'],
            'location' => ['sometimes','nullable','string','max:255'],
            'starts_at' => ['sometimes','date'],
            'ends_at' => ['sometimes','nullable','date','after_or_equal:starts_at'],
            'capacity' => ['sometimes','nullable','integer','min:1'],
            'visibility' => ['sometimes','in:public,private'],
            'status' => ['sometimes','in:draft,published,cancelled'],
        ]);

        $event->update($data);
        return response()->json($event->fresh());
    }

    public function destroy(Event $event)
    {
        $this->authorizeManage($event);
        $event->delete();
        return response()->json(['message' => 'Event deleted']);
    }

    // RSVP: accept/decline/waitlist
    public function rsvp(Request $request, Event $event)
    {
        $data = $request->validate([
            'status' => ['required','in:accepted,declined,waitlist']
        ]);

        $userId = Auth::id();

        // Handle accepted / waitlist / declined with transactional safety.
        if ($data['status'] === 'accepted') {
            $result = DB::transaction(function () use ($event, $userId) {
                // Lock the event row to stabilize capacity reads/updates
                $ev = Event::where('id', $event->id)->lockForUpdate()->first();

                // unlimited capacity
                if ($ev->capacity === null) {
                    $att = EventAttendee::updateOrCreate(
                        ['event_id' => $ev->id, 'user_id' => $userId],
                        ['status' => 'accepted', 'role' => 'attendee']
                    );
                    return ['att' => $att, 'waitlisted' => false];
                }

                // Count accepted attendees under lock
                $acceptedCount = DB::table('event_attendees')
                    ->where('event_id', $ev->id)
                    ->where('status', 'accepted')
                    ->lockForUpdate()
                    ->count();

                if ($acceptedCount < $ev->capacity) {
                    $att = EventAttendee::updateOrCreate(
                        ['event_id' => $ev->id, 'user_id' => $userId],
                        ['status' => 'accepted', 'role' => 'attendee']
                    );
                    return ['att' => $att, 'waitlisted' => false];
                }

                // No capacity -> place on waitlist
                $att = EventAttendee::updateOrCreate(
                    ['event_id' => $ev->id, 'user_id' => $userId],
                    ['status' => 'waitlist', 'role' => 'attendee']
                );

                // Compute waitlist position (order by created_at, tie-break by id)
                $position = DB::table('event_attendees')
                    ->where('event_id', $ev->id)
                    ->where('status', 'waitlist')
                    ->where(function ($q) use ($att) {
                        $q->where('created_at', '<', $att->created_at)
                          ->orWhere(function ($q2) use ($att) {
                              $q2->where('created_at', '=', $att->created_at)
                                 ->where('id', '<=', $att->id);
                          });
                    })
                    ->lockForUpdate()
                    ->count();

                $total = DB::table('event_attendees')
                    ->where('event_id', $ev->id)
                    ->where('status', 'waitlist')
                    ->lockForUpdate()
                    ->count();

                return ['att' => $att, 'waitlisted' => true, 'waitlist_position' => $position + 1, 'waitlist_size' => $total];
            });

            $att = $result['att'];
            $waitlisted = $result['waitlisted'];
            if ($waitlisted) {
                return response()->json(array_merge($att->toArray(), ['placed_on_waitlist' => true]), 202);
            }
            return response()->json($att);
        }

        if ($data['status'] === 'waitlist') {
            $result = DB::transaction(function () use ($event, $userId) {
                $att = EventAttendee::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $userId],
                    ['status' => 'waitlist', 'role' => 'attendee']
                );

                $position = DB::table('event_attendees')
                    ->where('event_id', $event->id)
                    ->where('status', 'waitlist')
                    ->where(function ($q) use ($att) {
                        $q->where('created_at', '<', $att->created_at)
                          ->orWhere(function ($q2) use ($att) {
                              $q2->where('created_at', '=', $att->created_at)
                                 ->where('id', '<=', $att->id);
                          });
                    })
                    ->lockForUpdate()
                    ->count();

                $total = DB::table('event_attendees')
                    ->where('event_id', $event->id)
                    ->where('status', 'waitlist')
                    ->lockForUpdate()
                    ->count();

                return ['att' => $att, 'waitlist_position' => $position + 1, 'waitlist_size' => $total];
            });

            return response()->json(array_merge($result['att']->toArray(), ['waitlist_position' => $result['waitlist_position']]), 202);
        }

        // Decline: if the attendee was accepted, free a slot and promote the earliest waitlisted user.
        if ($data['status'] === 'declined') {
            $att = DB::transaction(function () use ($event, $userId) {
                // Lock the attendee row if exists
                $existing = EventAttendee::where('event_id', $event->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                $wasAccepted = $existing && $existing->status === 'accepted';

                $updated = EventAttendee::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $userId],
                    ['status' => 'declined', 'role' => 'attendee']
                );

                // If they were accepted and the event has capacity, promote the next waitlisted user
                if ($wasAccepted && $event->capacity !== null) {
                    $next = EventAttendee::where('event_id', $event->id)
                        ->where('status', 'waitlist')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                            if ($next) {
                                $next->update(['status' => 'accepted']);
                            }
                }

                return $updated;
            });

            return response()->json($att);
        }

        // Fallback (shouldn't happen due to validation)
        return response()->json(['message' => 'Invalid RSVP status'], 422);
    }

    // Host checks-in an attendee
    public function checkin(Event $event, EventAttendee $attendee)
    {
        $this->authorizeCheckin($event);
        $attendee->update(['checked_in' => true]);
        return response()->json($attendee);
    }
}
