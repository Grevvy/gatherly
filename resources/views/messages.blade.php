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
                                            class="w-7 h-7 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-sky-300 to-indigo-300">
                                            @if (!empty($member->avatar))
                                                <img src="{{ asset('storage/' . $member->avatar) }}"
                                                    alt="{{ $member->name }}'s avatar"
                                                    class="w-full h-full object-cover">
                                            @else
                                                <span class="text-white text-xs font-semibold">
                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                </span>
                                            @endif
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
                        class="flex-1 text-sm font-medium py-3 text-center transition rounded-t-xl {{ $tab === 'channel' ? 'bg-blue-100 text-blue-600 shadow-inner' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
                        <i class="fa-solid fa-hashtag mr-1"></i> Groups
                    </a>
                    <a href="{{ route('messages', ['tab' => 'direct', 'community' => $community->slug]) }}"
                        class="flex-1 text-sm font-medium py-3 text-center transition rounded-t-xl {{ $tab === 'direct' ? 'bg-blue-100 text-blue-600 shadow-inner' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}">
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
                                        class="w-9 h-9 rounded-full ml-3 mt-7 flex items-center justify-center overflow-hidden bg-gradient-to-br from-sky-300 to-indigo-300 z-[1]">
                                        @php
                                            $sender = $message->user;
                                        @endphp

                                        @if ($sender && $sender->avatar)
                                            <img src="{{ asset('storage/' . $sender->avatar) }}"
                                                alt="{{ $sender->name }}'s avatar"
                                                class="w-full h-full object-cover">
                                        @else
                                            <span class="text-white font-bold text-lg">
                                                {{ strtoupper(substr($sender->name ?? 'U', 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div
                                    class="max-w-[75%] flex flex-col {{ $message->user_id === $userId ? 'items-end' : 'items-start' }}">
                                    @if ($message->user_id !== $userId)
                                        <div class="text-left">
                                            <span
                                                class="text-[10px] font-medium text-gray-500 block">{{ $message->user->name }}</span>
                                        </div>
                                    @endif

                                    <div
                                        class="relative px-4 py-2 max-w-[255px] break-words text-sm 
        {{ $message->user_id === $userId
            ? 'bg-gradient-to-r from-blue-500 to-blue-500 text-white rounded-[15px] self-end shadow-sm hover:scale-[1.02] transition-transform mr-2'
            : 'bg-gray-200 text-gray-900 rounded-[15px] self-start shadow-sm hover:scale-[1.02] transition-transform' }}">
                                        {!! nl2br(e($message->body)) !!}
                                        <div
                                            class="absolute bottom-0
            {{ $message->user_id === $userId
                ? 'right-0 translate-x-[6px] w-[18px] h-[22px] bg-blue-500 rounded-bl-[16px_14px] after:content-[""] after:absolute after:right-[-18px] after:w-[24px] after:h-[22px] after:bg-white after:rounded-bl-[10px]'
                : 'left-0 -translate-x-[6px] w-[18px] h-[22px] bg-gray-200 rounded-br-[16px_14px] after:content-[""] after:absolute after:left-[-18px] after:w-[24px] after:h-[22px] after:bg-white after:rounded-br-[10px]' }}">
                                        </div>
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
        document.addEventListener("DOMContentLoaded", () => {
            const scrollContainer = document.getElementById('message-scroll');
            const searchInput = document.getElementById('searchInput');
            const groupTab = document.querySelector('a[href*="tab=channel"]');
            const directTab = document.querySelector('a[href*="tab=direct"]');
            const listContainer = document.getElementById('listContainer');
            const chatArea = document.querySelector('.col-span-2.flex.flex-col.h-full.bg-white');


            // --- Auto-scroll on load
            if (scrollContainer) {
                setTimeout(() => (scrollContainer.scrollTop = scrollContainer.scrollHeight), 100);
            }

            // --- Search filter
            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    const term = e.target.value.toLowerCase();
                    const items = document.querySelectorAll('#listContainer a'); // query live each time
                    items.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }

            // --- Initialize message form (no reload)
            function initializeMessageForm() {
                const form = document.querySelector('form[action="{{ route('messages.store') }}"]');
                const input = document.getElementById('messageInput');
                const scrollContainer = document.getElementById('message-scroll');
                const token = form?.querySelector('input[name="_token"]')?.value;
                if (!form || !input || !token) return;

                form.addEventListener('submit', async e => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const body = formData.get('body').trim();
                    if (!body) return;

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token
                            },
                            body: formData
                        });

                        if (!res.ok) throw new Error('Failed to send');
                        const html = await res.text();
                        const match = html.match(/\/messages\/(\d+)/);
                        const newId = match ? match[1] : Date.now();

                        const newMsg = document.createElement('div');
                        newMsg.className = 'flex justify-end group items-start gap-2 fade-in';
                        newMsg.dataset.id = newId;
                        newMsg.innerHTML = `
  <form method="POST" data-id="${newId}"
    class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 mt-[8px] mr-[2px] delete-message-form">
    <input type="hidden" name="_token" value="${token}">
    <input type="hidden" name="_method" value="DELETE">
    <button type="button"
      class="delete-message-btn text-red-400 hover:text-red-500 transition transform hover:scale-110"
      title="Delete">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
        viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M6 7h12M8 7v12a1 1 0 001 1h6a1 1 0 001-1V7M10 7V5a1 1 0 011-1h2a1 1 0 011 1v2" />
      </svg>
    </button>
  </form>

  <div class="max-w-[75%] flex flex-col items-end">
  <div class="relative px-4 py-2 max-w-[255px] break-words text-sm
      bg-gradient-to-r from-blue-500 to-blue-500 text-white rounded-[15px] self-end shadow-sm
      hover:scale-[1.02] transition-transform mr-2">
    ${body.replace(/\n/g, '<br>')}
    <div class="absolute bottom-0 right-0 translate-x-[6px] w-[18px] h-[22px] bg-blue-500 rounded-bl-[16px_14px]
        after:content-[''] after:absolute after:right-[-18px] after:w-[24px] after:h-[22px] after:bg-white after:rounded-bl-[10px]">
    </div>
  </div>
  <div class="text-[9px] text-gray-400 text-right mr-2">
    ${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}
  </div>
</div>
`;

                        scrollContainer.appendChild(newMsg);
                        // Update sidebar preview & timestamp
                        updateSidebarAfterMessage({
                                body: body,
                                created_at: new Date().toISOString()
                            },
                            formData.get('messageable_type'),
                            formData.get('messageable_id')
                        );

                        const deleteBtn = newMsg.querySelector('.delete-message-btn');
                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', () => confirmDeleteMessage(deleteBtn));
                        }
                        input.value = '';

                        setTimeout(() => (scrollContainer.scrollTop = scrollContainer.scrollHeight),
                            100);
                    } catch (err) {
                        console.error(err);
                    }
                });
            }

            initializeMessageForm();

            const newForm = document.getElementById('newForm');
            if (newForm) {
                newForm.addEventListener('submit', async e => {
                    e.preventDefault();
                    const form = e.target;
                    const token = form.querySelector('input[name="_token"]').value;
                    const formData = new FormData(form);

                    const currentTab = groupTab.classList.contains('bg-blue-100') ? 'channel' :
                        'direct';

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token
                            },
                            body: formData
                        });
                        if (!res.ok) throw new Error('Failed to create');

                        showToastify(
                            currentTab === 'channel' ? 'Channel created successfully.' :
                            'Conversation created successfully.',
                            'success'
                        );

                        // Reload sidebar list
                        const url = new URL(window.location.href);
                        url.searchParams.set('tab', currentTab);
                        const listRes = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const listHtml = await listRes.text();
                        const doc = new DOMParser().parseFromString(listHtml, 'text/html');
                        const newList = doc.querySelector('#listContainer');
                        const newFormContent = doc.querySelector('#newForm');
                        if (newList && listContainer) listContainer.innerHTML = newList.innerHTML;
                        if (newFormContent && document.getElementById('newForm'))
                            document.getElementById('newForm').innerHTML = newFormContent.innerHTML;

                        form.reset();
                        newForm.classList.add('hidden');

                        // --- NEW: Reset chat panel
                        if (chatArea) {
                            chatArea.innerHTML = `
                    <div class="flex-1 flex items-center justify-center text-gray-400 italic bg-white">
                        <p>Select a conversation or channel to start chatting</p>
                    </div>`;
                        }

                        // --- NEW: Remove any sidebar highlights
                        listContainer.querySelectorAll('a').forEach(a => a.classList.remove(
                            'bg-blue-100/50'));


                    } catch (err) {
                        console.error(err);
                        showToastify('Failed to create.', 'error');
                    }
                });
            }

            // --- Conversation click (no reload)
            if (listContainer && chatArea) {
                listContainer.addEventListener('click', async e => {
                    const link = e.target.closest('a[href*="channel_id"], a[href*="thread_id"]');
                    if (!link) return;
                    e.preventDefault();
                    const url = link.getAttribute('href');
                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const html = await res.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newChat = doc.querySelector('.col-span-2.flex.flex-col.h-full.bg-white');
                        if (newChat) {
                            chatArea.innerHTML = newChat.innerHTML;
                            window.history.pushState({}, '', url);
                            initializeMessageForm();
                        }
                        listContainer.querySelectorAll('a').forEach(a => a.classList.remove(
                            'bg-blue-100/50'));
                        link.classList.add('bg-blue-100/50');
                        const scrollContainer = document.getElementById('message-scroll');
                        if (scrollContainer) {
                            setTimeout(() => (scrollContainer.scrollTop = scrollContainer.scrollHeight),
                                100);
                        }
                    } catch (err) {
                        console.error('Failed to load conversation:', err);
                    }
                });
            }

            // --- Delete channel
            window.confirmDeleteChannel = async button => {
                const form = button.closest('form');
                const id = form.dataset.id;
                const token = document.querySelector('input[name="_token"]').value;
                showConfirmToast('Are you sure you want to delete this channel?', async () => {
                    try {
                        const res = await fetch(`/channels/${id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: '_method=DELETE'
                        });
                        if (res.ok) {
                            const el = form.closest('.group');
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-5px)';
                            setTimeout(() => el.remove(), 300);
                            chatArea.innerHTML = `
                        <div class="flex-1 flex items-center justify-center text-gray-400 italic bg-white">
                            <p>Select a conversation or channel to start chatting</p>
                        </div>`;
                            showToastify('Channel deleted successfully.', 'success');
                        }
                    } catch {
                        showToastify('Failed to delete channel.', 'error');
                    }
                }, 'bg-red-400 hover:bg-red-500', 'Delete');
            };

            // --- Delete thread
            window.confirmDeleteThread = async button => {
                const form = button.closest('form');
                const id = form.dataset.id;
                const token = document.querySelector('input[name="_token"]').value;
                showConfirmToast('Are you sure you want to delete this conversation?', async () => {
                    try {
                        const res = await fetch(`/threads/${id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: '_method=DELETE'
                        });
                        if (res.ok) {
                            const el = form.closest('.group');
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-5px)';
                            setTimeout(() => el.remove(), 300);
                            chatArea.innerHTML = `
                        <div class="flex-1 flex items-center justify-center text-gray-400 italic bg-white">
                            <p>Select a conversation or channel to start chatting</p>
                        </div>`;
                            showToastify('Conversation deleted successfully.', 'success');
                        }
                    } catch {
                        showToastify('Failed to delete conversation.', 'error');
                    }
                }, 'bg-red-400 hover:bg-red-500', 'Delete');
            };

            async function loadTab(tab) {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newList = doc.querySelector('#listContainer');
                    const newForm = doc.querySelector('#newForm');
                    if (newList && listContainer) listContainer.innerHTML = newList.innerHTML;
                    if (newForm && document.getElementById('newForm'))
                        document.getElementById('newForm').innerHTML = newForm.innerHTML;

                    // Reset chat panel
                    if (chatArea) {
                        chatArea.innerHTML = `
                <div class="flex-1 flex items-center justify-center text-gray-400 italic bg-white">
                    <p>Select a conversation or channel to start chatting</p>
                </div>`;
                    }

                    // Remove sidebar highlights
                    listContainer.querySelectorAll('a').forEach(a => a.classList.remove('bg-blue-100/50'));

                    // --- FIXED TAB COLOR SWITCHING WITH HOVER SUPPORT ---
                    const activeClasses = ['bg-blue-100', 'text-blue-600', 'shadow-inner'];
                    const inactiveClasses = ['text-gray-600'];
                    const hoverClasses = ['hover:bg-blue-50', 'hover:text-blue-600'];

                    if (tab === 'channel') {
                        groupTab.classList.add(...activeClasses);
                        groupTab.classList.remove(...inactiveClasses, ...hoverClasses);

                        directTab.classList.remove(...activeClasses);
                        directTab.classList.add(...inactiveClasses, ...hoverClasses);
                    } else {
                        directTab.classList.add(...activeClasses);
                        directTab.classList.remove(...inactiveClasses, ...hoverClasses);

                        groupTab.classList.remove(...activeClasses);
                        groupTab.classList.add(...inactiveClasses, ...hoverClasses);
                    }

                } catch (err) {
                    console.error('Failed to load tab:', err);
                }
            }

            if (groupTab && directTab) {
                groupTab.addEventListener('click', e => {
                    e.preventDefault();
                    loadTab('channel');
                });
                directTab.addEventListener('click', e => {
                    e.preventDefault();
                    loadTab('direct');
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
                            setTimeout(async () => {
                                msgEl.remove();

                                const chat = document.querySelector(
                                    'form[action="{{ route('messages.store') }}"]');
                                if (chat) {
                                    const messageableType = chat.querySelector(
                                        'input[name="messageable_type"]').value;
                                    const messageableId = chat.querySelector(
                                        'input[name="messageable_id"]').value;

                                    // Instead of only local DOM read, fetch sidebar fresh
                                    await refreshSidebarAfterDelete(messageableType, messageableId);
                                }
                            }, 300);

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

        function updateSidebarAfterMessage(message, messageableType, messageableId) {
            const listItems = document.querySelectorAll('#listContainer .group');

            listItems.forEach(item => {
                const link = item.querySelector('a');
                if (!link) return;

                const urlParam = messageableType === 'channel' ? 'channel_id' : 'thread_id';
                if (link.href.includes(`${urlParam}=${messageableId}`)) {

                    // Update last message text and timestamp if a message exists
                    if (message) {
                        const preview = item.querySelector('p.text-gray-500');
                        if (preview) preview.textContent = message.body;

                        const timestamp = item.querySelector('span.text-xs.text-gray-400');
                        if (timestamp) {
                            const time = new Date(message.created_at).toLocaleTimeString([], {
                                hour: 'numeric',
                                minute: '2-digit'
                            });
                            timestamp.textContent = time;
                        }
                    } else {
                        // If no message (like after deletion), clear preview and timestamp
                        const preview = item.querySelector('p.text-gray-500');
                        if (preview) preview.textContent = '';
                        const timestamp = item.querySelector('span.text-xs.text-gray-400');
                        if (timestamp) timestamp.textContent = '';
                    }

                    // Handle trash icon visibility
                    const trashForm = item.querySelector('form');
                    if (!trashForm) return;

                    if (messageableType === 'channel') {
                        // For channels, always show if user is owner
                        trashForm.classList.remove('hidden');
                    } else {
                        // For threads, show trash only if chat is empty
                        const chatMessages = document.querySelectorAll('#message-scroll .flex');
                        if (chatMessages.length === 0) {
                            trashForm.classList.remove('hidden');
                        } else {
                            trashForm.classList.add('hidden');
                        }
                    }
                }
            });
        }


        async function refreshSidebarAfterDelete(messageableType, messageableId) {
            try {
                const listItems = document.querySelectorAll('#listContainer .group');

                listItems.forEach(item => {
                    const link = item.querySelector('a');
                    if (!link) return;

                    const urlParam = messageableType === 'channel' ? 'channel_id' : 'thread_id';
                    if (!link.href.includes(`${urlParam}=${messageableId}`)) return;

                    // Get all visible message bubbles
                    const messageEls = document.querySelectorAll('#message-scroll .flex');
                    let lastMessage = null;
                    let lastTimestamp = null;

                    for (let i = messageEls.length - 1; i >= 0; i--) {
                        const bubble = messageEls[i].querySelector(
                            '[class*="bg-gradient-to-r"], [class*="bg-gray-200"]'
                        );
                        const timestampEl = messageEls[i].querySelector('div.text-gray-400');
                        if (bubble && timestampEl) {
                            lastMessage = bubble.textContent.trim();
                            lastTimestamp = timestampEl.textContent.trim();
                            break;
                        }
                    }

                    const preview = item.querySelector('p.text-gray-500');
                    const timestamp = item.querySelector('span.text-xs.text-gray-400');

                    if (lastMessage) {
                        if (preview) preview.textContent = lastMessage;
                        if (timestamp) timestamp.textContent = lastTimestamp;
                    } else {
                        if (preview) preview.textContent = 'No messages yet';
                        if (timestamp) timestamp.textContent = '';
                    }

                    const trashForm = item.querySelector('form');
                    if (trashForm) {
                        if (messageableType === 'channel') {
                            trashForm.classList.remove('hidden');
                        } else {
                            trashForm.classList.toggle('hidden', messageEls.length > 0);
                        }
                    }
                });
            } catch (err) {
                console.error('Sidebar refresh failed:', err);
            }
        }

        function updateCharCount() {
            const input = document.getElementById('messageInput');
            const counter = document.getElementById('charCount');
            counter.textContent = input.value.length;
        }
    </script>
</x-layout>
