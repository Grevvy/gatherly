<x-layout>
 
    <!-- Messages Section -->
    <div class="grid grid-cols-3 gap-6 mt-6">

        <!-- Left Sidebar -->
        <div class="bg-white rounded-lg shadow flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-base font-semibold text-gray-800">Messages</h3>
                <button class="text-blue-600 text-sm font-semibold hover:underline">+ New</button>
            </div>

            <!-- Search -->
            <div class="p-4 border-b">
                <input type="text" placeholder="Search messages..."
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none">
            </div>

            <!-- Tabs (Groups / Direct) -->
            <div class="flex items-center justify-around border-b p-2 bg-gray-50">
                <button
                    class="flex-1 text-sm font-medium py-2 rounded-md
                    {{ request('tab') !== 'direct' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                    <i class="fa-solid fa-hashtag mr-1"></i> Groups
                </button>
                <button
                    class="flex-1 text-sm font-medium py-2 rounded-md
                    {{ request('tab') === 'direct' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:text-blue-600' }}">
                    <i class="fa-regular fa-user mr-1"></i> Direct
                </button>
            </div>

            <!-- Channel List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                @foreach ($channels ?? [] as $channel)
                    <button
                        class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 flex justify-between items-center
                        {{ $loop->first ? 'bg-blue-100 text-blue-600' : 'text-gray-700' }}">
                        <span># {{ $channel->name }}</span>
                        @if (!empty($channel->unread_count))
                            <span class="ml-2 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">
                                {{ $channel->unread_count }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Right Chat Section -->
        <div class="col-span-2 bg-white rounded-lg shadow flex flex-col">
            <!-- Chat Header -->
            @if (!empty($channels[0]->name))
                <div class="border-b p-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">#{{ $channels[0]->name }}</h2>
                        <p class="text-sm text-gray-500">{{ count($messages ?? []) }} messages</p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                </div>
            @endif

            <!-- Message Thread -->
            <div class="flex-1 p-4 space-y-5 overflow-y-auto">
                @forelse($messages ?? [] as $message)
                    <div class="flex flex-col">
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $message->user->name ?? 'User' }}
                            <span class="text-gray-400 text-xs ml-2">{{ $message->created_at ?? 'Time' }}</span>
                        </p>
                        <p class="text-gray-700 bg-gray-50 p-3 rounded-lg mt-1 w-fit max-w-[80%]">
                            {{ $message->body ?? 'Message content...' }}
                        </p>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-10">
                        <p class="text-sm">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse
            </div>

            <!-- Chat Input -->
            <form class="border-t p-3 flex gap-2">
                <input type="text" placeholder="Type your message..."
                    class="flex-1 px-3 py-2 border rounded-lg focus:outline-none text-sm">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Send
                </button>
            </form>
        </div>
    </div>
</x-layout>
