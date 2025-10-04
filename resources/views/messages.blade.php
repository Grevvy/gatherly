<x-layout>
    <!-- Page Heading (Community Info) -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="w-full h-48 bg-gray-200"></div>
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-800">
                {{ $community->name ?? 'Community Name' }}
            </h2>
            <p class="text-gray-600">
                {{ $community->description ?? 'This is a sample description for the community.' }}
            </p>
        </div>
    </div>

    <!-- Messages Section -->
    <div class="grid grid-cols-3 gap-6 mt-6">

        <!-- Channels Sidebar -->
        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-500">Channels</h3>
                <button class="text-blue-600 text-sm font-semibold hover:underline">
                    + New
                </button>
            </div>

            <!-- Search -->
            <input type="text" placeholder="Search messages..."
                class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none mb-4">

            <!-- Channel List -->
            <div class="space-y-1 overflow-y-auto max-h-[calc(100vh-200px)]">
                @foreach ($channels ?? [] as $channel)
                    <button
                        class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-100
                        {{ $loop->first ? 'bg-blue-100 text-blue-600' : '' }}">
                        {{ $channel->name }}
                        @if (!empty($channel->unread_count))
                            <span class="ml-2 text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">
                                {{ $channel->unread_count }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Chat Thread -->
        <div class="col-span-2 bg-white rounded-lg shadow flex flex-col" style="height: 600px;">

            <!-- Channel Title -->
            @if (!empty($channels[0]->name))
                <div class="border-b p-4 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ $channels[0]->name }}
                    </h2>
                </div>
            @endif

            <!-- Message History -->
            <div class="flex-1 p-4 space-y-5 overflow-y-auto">
                @forelse($messages ?? [] as $message)
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $message->user->name ?? 'User' }}
                            <span class="text-gray-400 text-xs ml-2">
                                {{ $message->created_at ?? 'Time' }}
                            </span>
                        </p>
                        <p class="text-gray-700 mt-1">
                            {{ $message->body ?? 'Message content...' }}
                        </p>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-10">
                        <p class="text-sm">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input -->
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
