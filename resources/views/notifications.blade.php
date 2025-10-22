<x-layout title="Notifications">
    <div 
        x-data="{
            notifications: [
                { id: 1, text: 'Shauna commented on your post 💬', time: '2m ago', read: false },
                { id: 2, text: 'Gerrit created an event: Movie Night 🎬', time: '1h ago', read: true },
                { id: 3, text: 'New member joined your community 👋', time: 'Yesterday', read: false },
                { id: 4, text: 'Your event “Coffee Meetup” starts soon ☕', time: '2 days ago', read: true }
            ],
            markAllAsRead() {
                this.notifications.forEach(n => n.read = true);
                // Confetti burst when all read 🎉
                if (this.notifications.every(n => n.read)) {
                    import('https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js')
                        .then(mod => mod.default({ particleCount: 150, spread: 100, origin: { y: 0.7 } }));
                }
            }
        }"
        class="relative w-full min-h-[90vh] bg-gradient-to-b from-white via-white to-gray-50 flex flex-col items-center justify-start py-16 px-8 overflow-hidden"
    >
        <!-- Subtle White Glow -->
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute top-1/4 left-1/3 w-[600px] h-[600px] bg-white/50 blur-[150px] rounded-full animate-pulse"></div>
            <div class="absolute bottom-1/3 right-1/3 w-[600px] h-[600px] bg-gray-100/40 blur-[150px] rounded-full animate-pulse delay-200"></div>
        </div>

        <!-- Header -->
        <div class="w-full max-w-7xl flex items-center justify-between mb-10">
            <h1 class="text-5xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-purple-600 drop-shadow-[0_2px_4px_rgba(0,0,0,0.05)]">
                Notifications
            </h1>
            <button 
                @click="markAllAsRead()"
                class="text-sm font-semibold text-blue-600 hover:text-purple-600 transition-all duration-200 hover:scale-110 active:scale-95">
                Mark all as read
            </button>
        </div>

        <!-- Notification Grid -->
        <div 
            class="w-full max-w-7xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8"
            x-init="$nextTick(() => {
                document.querySelectorAll('[data-animate]').forEach((el, i) => {
                    setTimeout(() => el.classList.add('opacity-100', 'translate-y-0'), i * 120)
                })
            })"
        >
            <template x-for="note in notifications" :key="note.id">
                <div 
                    data-animate
                    class="opacity-0 translate-y-6 transition-all duration-500 ease-out relative bg-white border border-gray-100 
                           shadow-[0_8px_40px_rgba(0,0,0,0.05)] rounded-3xl p-6 cursor-pointer
                           hover:scale-[1.04] hover:-translate-y-1 hover:shadow-[0_15px_50px_rgba(147,197,253,0.25)] hover:border-blue-200"
                    :class="note.read ? 'opacity-90' : 'ring-2 ring-blue-300/40'"
                    @click="note.read = true"
                >
                    <div class="flex justify-between items-start mb-3">
                        <p 
                            x-text="note.text" 
                            :class="note.read ? 'text-gray-700' : 'text-gray-900 font-semibold'"
                            class="text-base leading-relaxed"
                        ></p>
                        <span 
                            x-show="!note.read" 
                            class="w-3 h-3 bg-blue-500 rounded-full animate-ping mt-1"
                        ></span>
                    </div>

                    <p x-text="note.time" class="text-xs text-gray-400 mt-1"></p>

                    <!-- Soft Hover Glow -->
                    <div 
                        class="absolute inset-0 rounded-3xl bg-gradient-to-br from-blue-50/0 to-purple-50/0 opacity-0 hover:opacity-100 transition-all duration-500"
                    ></div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div 
            x-show="notifications.length === 0"
            class="flex flex-col items-center justify-center text-center text-gray-500 mt-20 text-sm"
        >
            <i data-lucide="bell" class="w-8 h-8 text-gray-300 mb-2"></i>
            No notifications yet 📭
        </div>
    </div>
</x-layout>
