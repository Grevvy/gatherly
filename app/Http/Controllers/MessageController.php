<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Channel;
use App\Models\MessageThread;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Events\MessageSent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\MessageDeleted;

class MessageController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request)
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

        event(new MessageSent($message));
        Log::info('Broadcasting message', ['id' => $message->id]);

        // If this was an AJAX request, return the created message id so clients
        // that send via fetch can reliably set the element id without scraping HTML.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['id' => $message->id]);
        }

        return back()->with('success', 'Message sent successfully.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $this->authorize('delete', $message);

        // capture context before deletion
        $messageId = $message->id;
        $messageableType = strtolower(class_basename($message->messageable_type)) === 'messagethread' ? 'messagethread' : strtolower(class_basename($message->messageable_type));
        $messageableId = $message->messageable_id;

        // try to extract community id from the parent (channel or thread)
        $communityId = null;
        try {
            $communityId = $message->messageable->community_id ?? null;
        } catch (\Throwable $e) {
            // ignore if relationship not loaded
        }

        $message->delete();

        try {
            Log::info('Dispatching MessageDeleted', ['id' => $messageId, 'type' => $messageableType, 'messageable_id' => $messageableId, 'community_id' => $communityId]);
            event(new MessageDeleted($messageId, $messageableType, $messageableId, $communityId));
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Message deleted successfully.');
    }
}
