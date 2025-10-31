<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Community;
use App\Notifications\ChannelCreated;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Events\ChannelCreated as ChannelCreatedEvent;
use App\Events\ChannelDeleted;
use Illuminate\Support\Facades\Auth;

class ChannelController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('create', [Channel::class, $community]);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $channel = $community->channels()->create($validated);

        // Broadcast to community members that a channel was created
        try {
            event(new ChannelCreatedEvent($channel));
        } catch (\Throwable $e) {
            // do not fail the request if broadcasting fails
        }

        $channel->loadMissing('community:id,name,slug');
        app(NotificationService::class)->notifyCommunityMembers(
            $community,
            new ChannelCreated($channel),
            Auth::id()
        );

        return back()->with('success', 'Channel created successfully.');
    }

    public function show(Channel $channel): View
    {
        $this->authorize('view', $channel);

        return view('messages', [
            'channels' => $channel->community->channels,
            'messages' => $channel->messages()->with('user')->latest()->get(),
        ]);
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $this->authorize('delete', $channel);

        $channelId = $channel->id;
        $communityId = $channel->community_id;
        $slug = $channel->community->slug;

        $channel->delete();

        try {
            event(new ChannelDeleted($communityId, $channelId));
        } catch (\Throwable $e) {
        }

        return redirect()
            ->to('/communities/' . $slug)
            ->with('success', 'Channel deleted successfully.');
    }
}
