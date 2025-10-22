<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Channel;
use App\Models\MessageThread;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request): RedirectResponse
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

        $messageable->messages()->create([
            'body' => $validated['body'],
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Message sent successfully.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        return back()->with('success', 'Message deleted successfully.');
    }
}