<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Channel;
use App\Models\MessageThread;
use App\Notifications\MessageReceived;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'messageable_type' => ['required', 'string', 'in:channel,thread'],
            'messageable_id' => ['required', 'integer'],
        ]);

        $messageableType = $validated['messageable_type'] === 'channel' 
            ? Channel::class 
            : MessageThread::class;
        
        $messageable = $messageableType::findOrFail($validated['messageable_id']);
        
        $this->authorize('create', [Message::class, $messageable]);

        $message = $messageable->messages()->create([
            'body' => $validated['body'],
            'user_id' => Auth::id(),
        ]);

        $message->loadMissing('user:id,name', 'messageable');
        $notification = new MessageReceived($message);
        $dispatcher = app(NotificationService::class);

        if ($messageable instanceof Channel) {
            $messageable->loadMissing('community:id,name,slug');
            $dispatcher->notifyCommunityMembers(
                $messageable->community,
                $notification,
                Auth::id()
            );
        } elseif ($messageable instanceof MessageThread) {
            $messageable->loadMissing('community:id,name,slug');
            $dispatcher->notifyThreadParticipants(
                $messageable,
                $notification,
                Auth::id()
            );
        }

        if ($request->expectsJson()) {
            $message->loadMissing('user:id,name,avatar');

            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'user_id' => $message->user_id,
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                    'user' => [
                        'id' => $message->user?->id,
                        'name' => $message->user?->name,
                        'avatar' => $message->user?->avatar
                            ? asset('storage/' . $message->user->avatar)
                            : null,
                    ],
                ],
            ], 201);
        }

        return back()->with('success', 'Message sent successfully.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Message deleted successfully.');
    }

    public function feed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messageable_type' => ['required', 'string', 'in:channel,thread'],
            'messageable_id' => ['required', 'integer'],
            'since_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $messageableType = $validated['messageable_type'] === 'channel'
            ? Channel::class
            : MessageThread::class;

        /** @var \App\Models\Channel|\App\Models\MessageThread $messageable */
        $messageable = $messageableType::with([
            $messageableType === Channel::class ? 'community.members' : 'participants',
        ])->findOrFail($validated['messageable_id']);

        if ($messageable instanceof Channel) {
            $this->authorize('view', $messageable);
        } else {
            $this->authorize('view', $messageable);
        }

        $messages = $messageable->messages()
            ->with('user:id,name,avatar')
            ->when($validated['since_id'] ?? null, fn($query, $sinceId) => $query->where('id', '>', $sinceId))
            ->orderBy('id')
            ->get();

        $latestId = $messages->last()?->id;

        return response()->json([
            'messages' => $messages->map(function (Message $message) {
                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'user_id' => $message->user_id,
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                    'user' => [
                        'id' => $message->user?->id,
                        'name' => $message->user?->name,
                        'avatar' => $message->user?->avatar
                            ? asset('storage/' . $message->user->avatar)
                            : null,
                    ],
                ];
            }),
            'latest_id' => $latestId,
        ]);
    }
}