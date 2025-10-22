@php
    use App\Models\Community;

    $tab = request('tab', 'channel');
    $channelId = request('channel_id');
    $threadId = request('thread_id');
    $slug = request('community');
    $userId = auth()->id();

    $community = $slug
        ? Community::with(['owner', 'memberships.user', 'channels', 'messageThreads.participants'])
            ->where('slug', $slug)
            ->first()
        : null;

    // load communities the current user belongs to (for sidebar)
    $communities = collect();
    if (auth()->check()) {
        $communities = Community::whereHas('memberships', function ($q) {
            $q->where('user_id', auth()->id());
        })->get();
    }

    $channels = $community?->channels ?? collect();
    $threads = $community
        ? $community->messageThreads->filter(fn($t) => $t->participants->contains('id', $userId))
        : collect();

    $channel = $tab === 'channel' ? $channels->firstWhere('id', (int) $channelId) : null;
    $thread = $tab === 'direct' ? $threads->firstWhere('id', (int) $threadId) : null;

    $messages = collect();

    if ($channel) {
        $messages = $channel->messages()->with('user')->latest()->get();
    } elseif ($thread) {
        $messages = $thread->messages()->with('user')->latest()->get();
    }

    $isOwner = $community && $community->owner_id === $userId;
@endphp

<x-layout :title="'Messages'" :community="$community" :communities="$communities">
    @if ($community)
        <div
            class="grid grid-cols-3 h-[650px] rounded-2xl overflow-hidden shadow overflow-y-hidden border border-blue-200">
            <!-- Sidebar -->
            <div class="flex flex-col bg-white border-r border-gray-200 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Messages</h3>
                    <button onclick="document.getElementById('newForm').classList.toggle('hidden')"
                        class="text-blue-600 hover:text-blue-700 transition transform hover:scale-110 active:scale-95">
                        <i class="fa-solid fa-plus text-lg"></i>
                    </button>
                </div>

                <!-- New Form -->
                <div id="newForm" class="hidden px-4 py-3 border-b border-gray-200 bg-gray-50">
                    @if ($tab === 'channel')
                        <form action="{{ route('channels.store', $community) }}" method="POST" class="space-y-2">
                            @csrf
                            <input type="text" name="name" required placeholder="New channel"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none placeholder-gray-400">
                            <button type="submit"
                                class="w-full items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">Create
                                Channel</button>
                        </form>
                    @elseif ($tab === 'direct')
                        <form action="{{ route('threads.store', $community) }}" method="POST"
                            class="space-y-4  rounded-xl">
                            @csrf
                            <label for="participant_ids"
                                class="block text-sm font-semibold text-gray-700 mb-1 text-center"> Select
                                one or more members to DM</label>

                            {{-- Avatar-enhanced member list --}}
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach ($community->members->where('id', '!=', $userId)->sortBy('name') as $member)
                                    <label
                                        class="flex items-center gap-3 px-3 py-2 rounded-md border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="participant_ids[]" value="{{ $member->id }}"
                                            class="accent-blue-500 w-4 h-4">
                                        <div
                                            class="w-7 h-7 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-semibold">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <span class="text-sm text-gray-800">{{ $member->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit"
                                class="w-full items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">Start
                                Conversation</button>
                        </form>

                    @endif
                </div>

                <!-- Tabs -->
                <div class="flex items-center justify-around bg-gray-100 border-b border-gray-200">
                    <a href="{{ route('messages', ['tab' => 'channel', 'community' => $community->slug]) }}"
                        class="flex-1 text-sm font-medium py-3 text-center transition {{ $tab === 'channel' ? 'bg-blue-100 text-blue-700 shadow-inner' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        <i class="fa-solid fa-hashtag mr-1"></i> Groups
                    </a>
                    <a href="{{ route('messages', ['tab' => 'direct', 'community' => $community->slug]) }}"
                        class="flex-1 text-sm font-medium py-3 text-center transition {{ $tab === 'direct' ? 'bg-blue-100 text-blue-700 shadow-inner' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        <i class="fa-regular fa-user mr-1"></i> Direct
                    </a>
                </div>

                <!-- Search -->
                <div class="px-4 py-3 border-b border-gray-200 bg-white relative">
                    <input type="text" id="searchInput" placeholder="Search..."
                        class="w-full text-sm pl-9 pr-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-400 focus:outline-none placeholder-gray-400 shadow-sm transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-7 top-5 text-gray-400 text-sm"></i>
                </div>

                <!-- Conversation List -->
                <div id="listContainer" class="flex-1 overflow-y-auto bg-white divide-y divide-gray-100">
                    @foreach ($tab === 'channel' ? $channels : $threads as $item)
                        @php
                            $isActive =
                                ($tab === 'channel' && isset($channel) && $channel->id === $item->id) ||
                                ($tab === 'direct' && isset($thread) && $thread->id === $item->id);

                            $name =
                                $tab === 'channel'
                                    ? '# ' . $item->name
                                    : $item->participants->where('id', '!=', $userId)->pluck('name')->join(', ');

                            $preview = optional($item->messages->last())->body ?? 'No messages yet';
                            $timestamp =
                                optional($item->messages->last())
                                    ->created_at?->timezone('America/New_York')
                                    ->format('g:i A') ?? '';
                        @endphp

                        <div class="relative group">
                            <a href="{{ route('messages', ['tab' => $tab, 'community' => $community->slug, $tab === 'channel' ? 'channel_id' : 'thread_id' => $item->id]) }}"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition {{ $isActive ? 'bg-blue-100/50' : '' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <span
                                            class="text-sm font-medium text-gray-800 truncate">{{ $name }}</span>
                                        <span class="text-xs text-gray-400">{{ $timestamp }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 truncate">{{ $preview }}</p>
                                </div>
                            </a>

                            <div class="absolute bottom-0 right-1">
                                @if ($tab === 'channel' && $isOwner)
                                    <form method="POST" action="{{ route('channels.destroy', $item) }}"
                                        data-id="{{ $item->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDeleteChannel(this)">
                                            <div class="text-red-400 hover:text-red-500 text-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
                                                </svg>
                                            </div>
                                        </button>
                                    </form>
                                @elseif ($tab === 'direct' && $item->messages->isEmpty() && $item->participants->contains('id', $userId))
                                    <form method="POST" action="{{ route('threads.destroy', $item) }}"
                                        data-id="{{ $item->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDeleteThread(this)">
                                            <div class="text-red-400 hover:text-red-500 text-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
                                                </svg>
                                            </div>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <!-- Chat -->
            <div class="col-span-2 flex flex-col h-full bg-white overflow-hidden">

                @if ($channel || $thread)
                    <div
                        class="sticky top-0 border-b border-gray-300 p-4 bg-gray-100/30 flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ $tab === 'channel'
                                    ? '# ' . $channel->name
                                    : $thread->participants->where('id', '!=', $userId)->pluck('name')->join(', ') }}
                            </h2>
                            <p class="text-xs text-gray-500">
                                {{ $messages->count() }} {{ Str::plural('message', $messages->count()) }}
                            </p>

                        </div>
                    </div>

                    <div id="message-scroll" class="flex-1 overflow-y-auto bg-white px-2 space-y-2 text-center">

                        @if ($tab === 'channel' && $channel)
                            <p class="text-[11px] text-gray-400">
                                Started
                                {{ $channel->created_at->timezone('America/New_York')->format('F j, Y \a\t g:i A') }}
                            </p>
                        @elseif ($tab === 'direct' && $thread)
                            <p class="text-[11px] text-gray-400">
                                Started
                                {{ $thread->created_at->timezone('America/New_York')->format('F j, Y \a\t g:i A') }}
                            </p>
                        @endif

                        @foreach ($messages->reverse() as $message)
                            <div
                                class="flex {{ $message->user_id === $userId ? 'justify-end' : 'justify-start' }} group items-start gap-2">
                                @if ($message->user_id === $userId)
                                    {{-- Trash icon --}}
                                    <form method="POST" action="{{ route('messages.destroy', $message->id) }}"
                                        data-id="{{ $message->id }}"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 mt-[8px] mr-[2px]">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmDeleteMessage(this)"
                                            class="text-red-400 hover:text-red-500 transition transform hover:scale-110"
                                            title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if ($message->user_id !== $userId)
                                    <div
                                        class="w-9 h-9 bg-blue-500 rounded-full flex ml-3 items-center justify-center text-white font-bold text-lg mt-8">
                                        {{ strtoupper(substr($message->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif

                                <div class="max-w-[75%] space-y-1">
                                    @if ($message->user_id !== $userId)
                                        <div class="text-left">
                                            <span
                                                class="text-[10px] font-medium text-gray-500 block">{{ $message->user->name }}</span>
                                        </div>
                                    @endif

                                    <div
                                        class="{{ $message->user_id === $userId
                                            ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-xl rounded-br-none flex justify-end mr-2'
                                            : 'bg-gray-200/50 text-gray-900 rounded-xl rounded-bl-none flex justify-start' }}
                    px-4 py-2 shadow-sm text-sm transition-transform hover:scale-[1.02] duration-150 mt-1">
                                        {!! nl2br(e($message->body)) !!}
                                    </div>

                                    <div
                                        class="text-[9px] text-gray-400 {{ $message->user_id === $userId ? 'text-right mr-2' : 'text-left' }}">
                                        {{ $message->created_at->timezone('America/New_York')->format('g:i A') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>

                    <!-- Message Input -->
                    <form action="{{ route('messages.store') }}" method="POST"
                        class="sticky bottom-0 border-t border-gray-300 px-4 py-3 bg-gray-100/40 flex gap-3 items-end">
                        @csrf
                        <input type="hidden" name="messageable_type"
                            value="{{ isset($channel) ? 'channel' : 'thread' }}">
                        <input type="hidden" name="messageable_id" value="{{ $channel->id ?? $thread->id }}">

                        <div class="flex flex-col flex-1">
                            <div class="text-xs text-gray-500 text-left mb-1">
                                <span id="charCount">0</span>/500
                            </div>
                            <textarea id="messageInput" name="body" rows="1" maxlength="500" placeholder="Type your message..."
                                class="resize-none px-4 py-2 rounded-2xl border border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm leading-5"
                                oninput="updateCharCount()" required></textarea>
                        </div>


                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-md transition-all duration-200 flex items-center justify-center hover:shadow-blue-200 hover:scale-110"
                            title="Send">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
                            </svg>
                        </button>
                    </form>
                @else
                    <div class="flex-1 flex items-center justify-center text-gray-400 italic bg-white">
                        <p>Select a conversation or channel to start chatting</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Scripts -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('message-scroll');

            if (container) {
                // Wait for messages to render, then scroll
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100); // slight delay ensures content is painted
            }


            const searchInput = document.getElementById('searchInput');
            const items = document.querySelectorAll('#listContainer a');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    items.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }
        });

        function confirmDeleteMessage(button) {
            const form = button.closest('form');
            const messageId = form.dataset.id;
            const token = document.querySelector('input[name="_token"]').value;

            showConfirmToast('Are you sure you want to delete this message?', async () => {
                try {
                    const res = await fetch(`/messages/${messageId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: '_method=DELETE'
                    });

                    if (res.status >= 200 && res.status < 400) {
                        const msgEl = form.closest('.flex');
                        if (msgEl) {
                            msgEl.style.transition = 'opacity 0.3s, transform 0.3s';
                            msgEl.style.opacity = '0';
                            msgEl.style.transform = 'translateY(-5px)';
                            setTimeout(() => msgEl.remove(), 300);
                        }
                        showToastify('Message deleted successfully.', 'success');
                    } else {
                        const data = await res.json().catch(() => ({}));
                        showToastify(data.message || 'Failed to delete message.', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Something went wrong.', 'error');
                }
            }, 'bg-red-400 hover:bg-red-500', 'Delete');
        }

        function confirmDeleteChannel(button) {
            const form = button.closest('form');
            const channelId = form.dataset.id;
            const token = document.querySelector('input[name="_token"]').value;

            showConfirmToast('Are you sure you want to delete this channel?', async () => {
                try {
                    const res = await fetch(`/channels/${channelId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: '_method=DELETE'
                    });

                    if (res.status >= 200 && res.status < 400) {
                        const channelEl = form.closest('.group');
                        if (channelEl) {
                            channelEl.style.transition = 'opacity 0.3s, transform 0.3s';
                            channelEl.style.opacity = '0';
                            channelEl.style.transform = 'translateY(-5px)';
                            setTimeout(() => channelEl.remove(), 300);
                        }
                        showToastify('Channel deleted successfully.', 'success');
                    } else {
                        const data = await res.json().catch(() => ({}));
                        showToastify(data.message || 'Failed to delete channel.', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Something went wrong.', 'error');
                }
            }, 'bg-red-400 hover:bg-red-500', 'Delete');
        }

        function confirmDeleteThread(button) {
            const form = button.closest('form');
            const threadId = form.dataset.id;
            const token = document.querySelector('input[name="_token"]').value;

            showConfirmToast('Are you sure you want to delete this conversation?', async () => {
                try {
                    const res = await fetch(`/threads/${threadId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: '_method=DELETE'
                    });

                    if (res.status >= 200 && res.status < 400) {
                        const threadEl = form.closest('.group');
                        if (threadEl) {
                            threadEl.style.transition = 'opacity 0.3s, transform 0.3s';
                            threadEl.style.opacity = '0';
                            threadEl.style.transform = 'translateY(-5px)';
                            setTimeout(() => threadEl.remove(), 300);
                        }
                        showToastify('Conversation deleted successfully.', 'success');
                    } else {
                        const data = await res.json().catch(() => ({}));
                        showToastify(data.message || 'Failed to delete conversation.', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToastify('Something went wrong.', 'error');
                }
            }, 'bg-red-400 hover:bg-red-500', 'Delete');
        }

        function updateCharCount() {
            const input = document.getElementById('messageInput');
            const counter = document.getElementById('charCount');
            counter.textContent = input.value.length;
        }
    </script>
</x-layout>
