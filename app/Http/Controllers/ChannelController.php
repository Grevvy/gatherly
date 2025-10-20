<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class ChannelController extends BaseController
{
    use AuthorizesRequests;

    public function store(Request $request, Community $community): RedirectResponse
    {
        $this->authorize('create', [Channel::class, $community]);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $community->channels()->create($validated);

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

        $channel->delete();

        return redirect()
            ->route('communities.show', $channel->community)
            ->with('success', 'Channel deleted successfully.');
    }
}