<?php

namespace App\Http\Controllers;

use App\Models\MessageThread;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class MessageThreadController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('create', [MessageThread::class, $community]);

        $validated = $request->validate([
            'participant_ids' => ['required', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        // Verify all participants are community members
        $memberIds = $community->members()->pluck('users.id')->toArray();
        $invalidParticipants = array_diff($validated['participant_ids'], $memberIds);
        
        if (!empty($invalidParticipants)) {
            return back()->withErrors([
                'participant_ids' => 'All participants must be members of this community.'
            ]);
        }

        $thread = $community->messageThreads()->create();
        
        // Add the current user and selected participants
        $participants = array_unique(array_merge(
            $validated['participant_ids'],
            [Auth::id()]
        ));
        
        $thread->participants()->attach($participants);

        return redirect()->route('messages', [
        'tab' => 'direct',
        'community' => $community->slug,
        ]);
    }

    public function show(MessageThread $thread): View
    {
        $this->authorize('view', $thread);

        $community = $thread->community;
        
        return view('messages', [
            'tab' => 'direct',
            'thread' => $thread,
            'communities' => Auth::user()->communities,
            'channels' => $community->channels,
            'messages' => $thread->messages()->with('user')->latest()->get(),
        ]);
    }

    public function destroy(MessageThread $thread): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $thread->delete();

        return redirect()
            ->to('/communities/' . $thread->community->slug)
            ->with('success', 'Conversation deleted.');
    }
}