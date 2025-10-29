<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Mail\EventRSVPConfirmation;
use App\Mail\EventWaitlistConfirmation;
use App\Mail\EventWaitlistPromotion;
use App\Notifications\EventPublished;
use App\Notifications\EventPendingApproval;
use App\Notifications\EventRsvpUpdated;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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

        if ($event->community_id) {
            $event->loadMissing(['community:id,name,slug', 'owner:id,name']);
            
            if ($event->status === 'published') {
                app(NotificationService::class)->notifyCommunityMembers(
                    $event->community,
                    new EventPublished($event)
                );
            } else if ($event->status === 'draft') {
                // Notify moderators about the pending event
                app(NotificationService::class)->notifyCommunityModerators(
                    $event->community,
                    new EventPendingApproval($event),
                    Auth::id()
                );
            }
        }

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

        // If attempting to set status to published, ensure user has rights (site admin or community owner/admin/moderator)
        if (isset($data['status']) && $data['status'] === 'published') {
            $user = Auth::user();
            if (! ($user?->isSiteAdmin())) {
                // if community event, check membership role
                if ($event->community_id) {
                    $isAdmin = \App\Models\CommunityMembership::where('community_id', $event->community_id)
                        ->where('user_id', Auth::id())
                        ->whereIn('role', ['owner','admin','moderator'])
                        ->where('status', 'active')
                        ->exists();
                    if (! $isAdmin) {
                        return response()->json(['message' => 'Insufficient permissions to publish event'], 403);
                    }
                } else {
                    // Non-community events can only be published by site admins
                    return response()->json(['message' => 'Insufficient permissions to publish event'], 403);
                }
            }
        }

        $originalStatus = $event->status;

        $event->update($data);
        $event->refresh();

        if ($originalStatus !== 'published' && $event->status === 'published' && $event->community_id) {
            $event->loadMissing(['community:id,name,slug', 'owner:id,name']);
            app(NotificationService::class)->notifyCommunityMembers(
                $event->community,
                new EventPublished($event),
                $event->owner_id
            );
        }

        return response()->json($event);
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
        $event->refresh();

        if ($event->community_id) {
            $event->loadMissing(['community:id,name,slug', 'owner:id,name']);
            app(NotificationService::class)->notifyCommunityMembers(
                $event->community,
                new EventPublished($event)
            );
        }

        return response()->json($event);
    }

    // RSVP: accept/decline/waitlist
    public function rsvp(Request $request, Event $event)
    {
        $data = $request->validate([
            'status' => ['required','in:accepted,declined,waitlist']
        ]);

        if ($event->status !== 'published') {
            return response()->json(['message' => 'Cannot RSVP to an event that is not published.'], 403);
        }

        $userId = Auth::id();

        if ($data['status'] === 'accepted') {
            $result = DB::transaction(function () use ($event, $userId) {
                $ev = Event::where('id', $event->id)->lockForUpdate()->first();

                if ($ev->capacity === null) {
                    $att = EventAttendee::updateOrCreate(
                        ['event_id' => $ev->id, 'user_id' => $userId],
                        ['status' => 'accepted', 'role' => 'attendee']
                    );
                    return ['att' => $att, 'waitlisted' => false];
                }

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

                $att = EventAttendee::updateOrCreate(
                    ['event_id' => $ev->id, 'user_id' => $userId],
                    ['status' => 'waitlist', 'role' => 'attendee']
                );

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
            $context = $waitlisted ? [
                'waitlist_position' => $result['waitlist_position'] ?? null,
                'waitlist_size' => $result['waitlist_size'] ?? null,
            ] : [];

            $this->notifyEventRsvp($event, $att, $waitlisted ? 'waitlist' : 'accepted', $context);

            // Send transactional email to the attendee; allow safe fallback to the same authenticated user
            try {
                $att->loadMissing('user:id,name,email');
                $event->loadMissing('community:id,name', 'owner:id,name');
                $recipientEmail = $att->user?->email;
                if (!$recipientEmail && Auth::id() === $att->user_id) {
                    $recipientEmail = Auth::user()?->email;
                }
                if ($recipientEmail) {
                    if ($waitlisted) {
                        $pos = (int)($result['waitlist_position'] ?? 1);
                        $size = (int)($result['waitlist_size'] ?? 1);
                        Log::info('RSVP email: waitlist confirmation', [
                            'event_id' => $event->id,
                            'user_id' => $att->user->id ?? Auth::id(),
                            'email' => $recipientEmail,
                            'position' => $pos,
                            'size' => $size,
                        ]);
                        $userForMail = $att->user ?? (Auth::id() === $att->user_id ? Auth::user() : null);
                        if ($userForMail) {
                            Mail::to($recipientEmail)->send(new EventWaitlistConfirmation($event, $userForMail, $pos, $size));
                        }
                    } else {
                        Log::info('RSVP email: accepted confirmation', [
                            'event_id' => $event->id,
                            'user_id' => $att->user->id ?? Auth::id(),
                            'email' => $recipientEmail,
                        ]);
                        $userForMail = $att->user ?? (Auth::id() === $att->user_id ? Auth::user() : null);
                        if ($userForMail) {
                            Mail::to($recipientEmail)->send(new EventRSVPConfirmation($event, $userForMail));
                        }
                    }
                } else {
                    Log::warning('RSVP email: missing user email', [
                        'event_id' => $event->id,
                        'user_id' => $att->user->id ?? Auth::id(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Swallow email errors so RSVP flow isn't blocked
                Log::error('RSVP email send failed', [
                    'event_id' => $event->id,
                    'user_id' => $att->user->id ?? null,
                    'message' => $e->getMessage(),
                ]);
                report($e);
            }

            if ($waitlisted) {
                return response()->json(array_merge(
                    $att->toArray(),
                    [
                        'placed_on_waitlist' => true,
                        'waitlist_position' => $result['waitlist_position'] ?? null,
                        'waitlist_size' => $result['waitlist_size'] ?? null,
                    ]
                ), 202);
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

            $this->notifyEventRsvp($event, $result['att'], 'waitlist', [
                'waitlist_position' => $result['waitlist_position'],
                'waitlist_size' => $result['waitlist_size'] ?? null,
            ]);

            // Send waitlist confirmation email to the attendee; allow safe fallback to the same authenticated user
            try {
                $att = $result['att'];
                $att->loadMissing('user:id,name,email');
                $event->loadMissing('community:id,name', 'owner:id,name');
                $recipientEmail = $att->user?->email;
                if (!$recipientEmail && Auth::id() === $att->user_id) {
                    $recipientEmail = Auth::user()?->email;
                }
                if ($recipientEmail) {
                    Log::info('RSVP email: waitlist (explicit status)', [
                        'event_id' => $event->id,
                        'user_id' => $att->user->id ?? Auth::id(),
                        'email' => $recipientEmail,
                        'position' => (int)$result['waitlist_position'],
                        'size' => (int)($result['waitlist_size'] ?? $result['waitlist_position']),
                    ]);
                    $userForMail = $att->user ?? (Auth::id() === $att->user_id ? Auth::user() : null);
                    if ($userForMail) {
                        Mail::to($recipientEmail)->send(new EventWaitlistConfirmation(
                            $event,
                            $userForMail,
                            (int)$result['waitlist_position'],
                            (int)($result['waitlist_size'] ?? $result['waitlist_position'])
                        ));
                    }
                } else {
                    Log::warning('RSVP email: missing user email (explicit waitlist)', [
                        'event_id' => $event->id,
                        'user_id' => $att->user->id ?? Auth::id(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('RSVP email send failed (explicit waitlist)', [
                    'event_id' => $event->id,
                    'user_id' => $att->user->id ?? null,
                    'message' => $e->getMessage(),
                ]);
                report($e);
            }

            return response()->json(array_merge(
                $result['att']->toArray(),
                [
                    'waitlist_position' => $result['waitlist_position'],
                    'waitlist_size' => $result['waitlist_size'] ?? null,
                ]
            ), 202);
        }

        if ($data['status'] === 'declined') {
            $txResult = DB::transaction(function () use ($event, $userId) {
                $existing = EventAttendee::where('event_id', $event->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                $wasAccepted = $existing && $existing->status === 'accepted';

                $updated = EventAttendee::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $userId],
                    ['status' => 'declined', 'role' => 'attendee']
                );

                $promoted = null;
                if ($wasAccepted && $event->capacity !== null) {
                    $next = EventAttendee::where('event_id', $event->id)
                        ->where('status', 'waitlist')
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if ($next) {
                        $next->update(['status' => 'accepted']);
                        $promoted = $next;
                    }
                }

                return ['updated' => $updated, 'promoted' => $promoted];
            });

            /** @var \App\Models\EventAttendee $att */
            $att = $txResult['updated'];
            /** @var \App\Models\EventAttendee|null $promoted */
            $promoted = $txResult['promoted'];

            $this->notifyEventRsvp($event, $att, 'declined');

            // If a waitlisted attendee was promoted due to this decline, email them
            if ($promoted) {
                try {
                    $promoted->loadMissing('user:id,name,email');
                    $event->loadMissing('community:id,name', 'owner:id,name');
                    if ($promoted->user?->email) {
                        Log::info('RSVP email: waitlist promotion', [
                            'event_id' => $event->id,
                            'user_id' => $promoted->user->id,
                            'email' => $promoted->user->email,
                        ]);
                        Mail::to($promoted->user->email)->send(new EventWaitlistPromotion($event, $promoted->user));
                    }
                    // Optionally, also notify in-app about their acceptance
                    $this->notifyEventRsvp($event, $promoted, 'accepted');
                } catch (\Throwable $e) {
                    Log::error('RSVP email send failed (promotion)', [
                        'event_id' => $event->id,
                        'user_id' => $promoted->user->id ?? null,
                        'message' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }

            return response()->json($att);
        }

        return response()->json(['message' => 'Invalid RSVP status'], 422);
    }

    // Host checks-in an attendee
    public function checkin(Event $event, EventAttendee $attendee)
    {
        $this->authorizeCheckin($event);
        $attendee->update(['checked_in' => true]);
        return response()->json($attendee);
    }

    protected function notifyEventRsvp(Event $event, EventAttendee $attendee, string $status, array $context = []): void
    {
        $attendee->loadMissing('user:id,name,avatar');
        $user = $attendee->user;

        if (! $user) {
            return;
        }

        $event->loadMissing('community:id,name,slug', 'owner:id,name');

        $notification = new EventRsvpUpdated($event, $user, $status, $context);
        $service = app(NotificationService::class);

        // Always notify the event owner if it's not their own RSVP
        if ($event->owner && $event->owner_id !== $user->id) {
            $event->owner->notify($notification);
        }
        
        // Additionally notify community moderators if it's a community event
        if ($event->community) {
            $service->notifyCommunityModerators(
                $event->community,
                $notification,
                $user->id
            );
        }
    }
}