<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
                ->whereIn('role', ['owner','admin','moderator'])
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

    /**
     * Calendar feed: return events overlapping a given date range.
     * Accepts ?start=YYYY-MM-DD&end=YYYY-MM-DD and optional &community=ID
     */
    public function calendar(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        // If not provided, default to current month's range
        try {
            $startDt = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->startOfMonth();
            $endDt = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfMonth();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid start or end date'], 422);
        }

        $q = Event::query()
            ->where('status', 'published')
            ->when($request->filled('community'), fn($qq) => $qq->where('community_id', $request->community))
            ->where(function ($qq) use ($startDt, $endDt) {
                $qq->where(function ($q2) use ($startDt, $endDt) {
                    $q2->whereNotNull('ends_at')
                        ->where('ends_at', '>=', $startDt)
                        ->where('starts_at', '<=', $endDt);
                })->orWhere(function ($q2) use ($startDt, $endDt) {
                    $q2->whereNull('ends_at')
                        ->whereBetween('starts_at', [$startDt, $endDt]);
                });
            })
            ->with(['community:id,slug,name'])
            ->withCount(['attendees as accepted_count' => function ($q) {
                $q->where('status', 'accepted');
            }])
            ->orderBy('starts_at');

        $events = $q->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'ends_at' => $e->ends_at?->toIso8601String(),
                'allDay' => false,
                'status' => $e->status,
                'capacity' => $e->capacity,
                'accepted_count' => $e->accepted_count ?? 0,
                'community' => $e->community ? $e->community->only(['id','slug','name']) : null,
            ];
        });

        return response()->json($events);
    }

    public function show(Event $event)
    {
        // If event is a draft, only allow viewing by the event owner/host or community owner/admin/moderator
        if ($event->status === 'draft') {
            $uid = Auth::id();

            // Event owner
            if ($event->owner_id === $uid) {
                return response()->json($event->load(['owner:id,name','community:id,slug,name','attendees.user']));
            }

            // Event host
            $isHost = EventAttendee::where('event_id', $event->id)
                ->where('user_id', $uid)
                ->where('role', 'host')
                ->exists();
            if ($isHost) {
                return response()->json($event->load(['owner:id,name','community:id,slug,name','attendees.user']));
            }

            // Community admin/owner/moderator
            if ($event->community_id) {
                $isAdmin = \App\Models\CommunityMembership::where('community_id', $event->community_id)
                    ->where('user_id', $uid)
                    ->whereIn('role', ['owner','admin','moderator'])
                    ->where('status', 'active')
                    ->exists();
                if ($isAdmin) {
                    return response()->json($event->load(['owner:id,name','community:id,slug,name','attendees.user']));
                }
            }

            // Not allowed to view drafts
            abort(404);
        }

        // Published (or otherwise viewable) events
        return response()->json($event->load(['owner:id,name','community:id,slug,name','attendees.user']));
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
        // Default to draft
        $data['status'] = $data['status'] ?? 'draft';

        // If this event is for a community, enforce membership rules:
        // - user must be a member of the community to create an event
        // - only community owners/admins may publish directly; regular members' events
        //   will be created as 'draft' and require approval to publish (notifications are future work)
        if (!empty($data['community_id'])) {
            $membership = \App\Models\CommunityMembership::where('community_id', $data['community_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (! $membership) {
                return response()->json(['message' => 'You must be a member of this community to create events'], 403);
            }

            $canPublishRoles = ['owner', 'admin', 'moderator'];
            if (! in_array($membership->role, $canPublishRoles)) {
                // Regular members cannot publish directly; force to draft
                $data['status'] = 'draft';
            }
        }

        $event = Event::create($data);
        // Return with owner relation so front-end can display Hosted by <name>
        return response()->json($event->load(['owner:id,name']), 201);
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

    /**
     * Approve a draft event: only allowed for event owner or community owner/admin/moderator
     */
    public function approve(Event $event)
    {
        $uid = Auth::id();

        if ($event->status !== 'draft') {
            return response()->json(['message' => 'Only draft events can be approved'], 400);
        }

        // Allow site admins
        if (Auth::user()?->isSiteAdmin()) {
            // allowed
        } else {
            // For community events: require community membership role in owner/admin/moderator
            if ($event->community_id) {
                $isAdmin = \App\Models\CommunityMembership::where('community_id', $event->community_id)
                    ->where('user_id', $uid)
                    ->whereIn('role', ['owner','admin','moderator'])
                    ->where('status', 'active')
                    ->exists();
                if (! $isAdmin) {
                    abort(403, 'Forbidden');
                }
            } else {
                // Non-community (site-wide) events: only site admins may approve
                abort(403, 'Forbidden');
            }
        }

        $event->status = 'published';
        $event->published_at = now();
        $event->save();

        return response()->json($event->fresh());
    }

    // RSVP: accept/decline/waitlist
    public function rsvp(Request $request, Event $event)
    {
        $data = $request->validate([
            'status' => ['required','in:accepted,declined,waitlist']
        ]);

        // Do not allow RSVP on drafts or non-published events
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Cannot RSVP to an event that is not published.'], 403);
        }

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
                // Postgres doesn't allow FOR UPDATE with aggregate functions
                // select the matching rows with FOR UPDATE and count them in PHP
                $acceptedRows = DB::table('event_attendees')
                    ->where('event_id', $ev->id)
                    ->where('status', 'accepted')
                    ->lockForUpdate()
                    ->get(['id']);
                $acceptedCount = $acceptedRows->count();

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
                $positionRows = DB::table('event_attendees')
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
                    ->get(['id']);

                $position = $positionRows->count();

                $totalRows = DB::table('event_attendees')
                    ->where('event_id', $ev->id)
                    ->where('status', 'waitlist')
                    ->lockForUpdate()
                    ->get(['id']);

                $total = $totalRows->count();

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

                $positionRows = DB::table('event_attendees')
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
                    ->get(['id']);

                $position = $positionRows->count();

                $totalRows = DB::table('event_attendees')
                    ->where('event_id', $event->id)
                    ->where('status', 'waitlist')
                    ->lockForUpdate()
                    ->get(['id']);

                $total = $totalRows->count();

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
