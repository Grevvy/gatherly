<div class="relative w-full px-4 g-welcome bg-gradient-to-b from-slate-50 via-blue-50 to-indigo-50">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');

        .g-welcome {
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
        }

        .animated-gradient {
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%
            }

            50% {
                background-position: 100% 50%
            }

            100% {
                background-position: 0% 50%
            }
        }

        .card-icon {
            transition: transform .3s ease;
        }

        .group:hover .card-icon {
            transform: rotate(3deg) scale(1.06);
        }
    </style>
    <!-- Decorative Background -->
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div
            class="absolute -top-24 -left-24 w-[420px] h-[420px] bg-gradient-to-br from-blue-200 via-indigo-200 to-transparent blur-3xl opacity-50 animate-pulse">
        </div>
        <div
            class="absolute -bottom-16 -right-16 w-[380px] h-[380px] bg-gradient-to-tr from-purple-200 via-pink-100 to-transparent blur-3xl opacity-50 animate-pulse">
        </div>
    </div>

    <div class="w-full py-0">

        <!-- Hero -->
        <section class="animate-fade-in">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Left: Copy (shifted up without moving the image) -->
                <div class="text-center md:text-left -mt-3 md:-mt-10">

                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight text-center">
                        <span
                            class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 drop-shadow animated-gradient">
                            Welcome to Gatherly
                        </span>
                    </h1>

                    <span
                        class="mt-2 md:mt-3 block h-1.5 w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 rounded-full">
                    </span>

                    <p
                        class="mt-3 md:mt-4 text-gray-700 text-base md:text-lg max-w-2xl md:max-w-none mx-auto md:mx-0 leading-relaxed text-center">
                        Build communities, host events, and collaborate in real time.
                    </p>
                    <!-- CTAs -->
                    <div class="mt-11 flex flex-wrap items-start justify-center md:justify-start gap-5">
                        <div class="flex flex-col items-center">
                            <a href="{{ route('create-community') }}"
                                class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2.5 rounded-xl font-semibold shadow-md hover:brightness-110 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                                <i class="fas fa-plus"></i>
                                Create Community
                            </a>
                            <span class="mt-1 text-[11px] leading-snug text-gray-500 max-w-[18rem] text-center">
                                Start a new space for your group.
                            </span>
                        </div>

                        <div class="flex flex-col items-center">
                            <a href="{{ route('explore') }}"
                                class="group inline-flex items-center gap-2 bg-white text-blue-700 border border-blue-200 px-5 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-blue-50 hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2">
                                <i class="fas fa-compass"></i>
                                Explore Communities
                                <i
                                    class="fas fa-arrow-right text-blue-500 opacity-0 -mr-1 group-hover:opacity-100 group-hover:translate-x-0.5 transition"></i>
                            </a>
                            <span class="mt-1 text-[11px] leading-snug text-gray-500 max-w-[18rem] text-center">
                                Browse and join communities based on your chosen interests or view all existing
                                communities.
                            </span>
                        </div>
                    </div>
                    <p class="mt-8 text-gray-500 text-xs flex items-center justify-center md:justify-start gap-1.5">
                        <span>
                            Tip: Click
                            <span class="inline-flex items-center gap-1 relative top-[5px]">
                                <img src="{{ asset('images/gatherly-logo.png') }}" alt="Gatherly Logo"
                                    class="w-5 h-5 rounded shadow-sm object-contain">
                                <span
                                    class="text-sm font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-purple-500 tracking-tight">
                                    Gatherly
                                </span>
                            </span>

                            in the top‑left corner to come back to this welcome page anytime.
                        </span>
                    </p>

                </div>

                <!-- Right: Hero image (fits content) -->
                <div class="hidden md:block">
                    <div class="relative max-w-md mx-auto rounded-2xl overflow-hidden shadow-xl ring-1 ring-indigo-100">
                        <img src="{{ asset('images/hero-image.png') }}" alt="Hero illustration"
                            class="block w-full h-auto object-cover" />
                        <span
                            class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500"></span>
                    </div>
                </div>

            </div>
        </section>

        <!-- Feature Grid: How-to snapshots matching the app -->
        <section class="mt-8 md:mt-10 grid grid-cols-1 gap-5">
            <!-- How to discover and join communities -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-blue-200 via-indigo-200 to-purple-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-blue-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                            <i class="fas fa-map" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900">Discover and join communities</h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Find communities that match your interests or
                        start your own.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Open <a href="{{ route('explore') }}"
                                    class="text-blue-700 underline hover:text-blue-800"> <i class="fas fa-compass">
                                    </i> Explore Communities</a> in the
                                sidebar to browse all
                                communities</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Search by name or description to find a good fit</span>
                        </li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Join a community — or click the <span
                                    class="inline-flex items-center justify-center w-6 h-6 text-blue-600 text-sm font-medium hover:text-blue-800 rounded-full border border-blue-600 transition">
                                    +
                                </span> in the
                                sidebar to create your own</span></li>
                    </ul>
                </div>
            </div>

            <!-- Share updates in the feed -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-amber-200 via-yellow-100 to-orange-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-amber-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 via-yellow-500 to-orange-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                            <i class="fas fa-newspaper" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900">Share updates in the feed</h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Post announcements, highlights, or questions —
                        even share images — to your community’s feed.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Select a community, then open <strong>Feed</strong></span>
                        </li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Create a post with text and optional images</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Like and comment to keep the conversation going</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- How to plan an event -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-indigo-200 via-blue-100 to-purple-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-indigo-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900">Plan and attend events</h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Create events in your community and keep track
                        of who’s going.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Select a community, then open
                                <strong>Events</strong></span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Create your event with title, date, time, and
                                details</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>View events in <strong>List</strong> or
                                <strong>Calendar</strong> tabs; filter by <strong>Upcoming</strong> or
                                <strong>Attending</strong></span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>RSVP — if full, you may be added to the waitlist</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- How to start a conversation -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-purple-200 via-indigo-200 to-blue-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-purple-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 via-fuchsia-500 to-pink-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                            <i class="fas fa-comments" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900">Chat in channels or DMs</h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Talk with your community in real time, in
                        channels or one‑on‑one.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Select a community, then open
                                <strong>Messages</strong></span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Choose between <strong>Channel</strong> or
                                <strong>Direct</strong> tabs</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Create a channel or start a direct thread and chat
                                live</span></li>
                    </ul>
                </div>
            </div>


            <!-- See your community members -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-cyan-200 via-blue-100 to-indigo-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-cyan-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center">
                            <i class="fas fa-user-friends" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900">Meet your members</h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Browse, search, and manage members in each
                        community.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Select a community, then open
                                <strong>Members</strong></span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Search by name and filter by <strong>All</strong>,
                                <strong>Online</strong>, or <strong>Staff</strong></span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Admins and moderators can review <strong>Pending</strong>,
                                promote, demote, or remove members</span></li>
                    </ul>
                </div>
            </div>

            <!-- Share photos (coming soon) -->
            <div
                class="group w-full p-[1px] rounded-2xl bg-gradient-to-br from-pink-200 via-fuchsia-200 to-purple-200 transition hover:shadow-md hover:-translate-y-0.5 hover:shadow-pink-200/80">
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/60 bg-white/70 backdrop-blur p-6 h-full">
                    <span
                        class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-500"></span>
                    <div class="flex items-center gap-3">
                        <div
                            class="card-icon w-10 h-10 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center">
                            <i class="fas fa-images" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base md:text-lg font-semibold text-gray-900 flex items-center gap-2">
                            Share photos

                        </h3>
                    </div>
                    <p class="mt-3 text-sm text-gray-600 leading-relaxed">Collect highlights from moments
                        your community cares about.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Select a community and open <strong>Photo
                                    Gallery</strong></span>
                        </li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-green-500 mt-0.5"
                                aria-hidden="true"></i><span>Upload images and browse them in a clean grid</span></li>

                    </ul>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="mt-10">
            <h2 class="text-center md:text-left text-2xl md:text-3xl font-extrabold text-gray-900">Get started in
                minutes</h2>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                <div
                    class="rounded-2xl border border-gray-100 bg-white/70 backdrop-blur p-5 shadow-sm hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Create your community</h3>
                    <p class="text-sm text-gray-600 mt-1">Name your community, add a description and photo, then set
                        your visibility, who can join, and add tags.</p>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-white/70 backdrop-blur p-5 shadow-sm hover:shadow-md transition">
                    <div
                        class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-3">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Invite your people</h3>
                    <p class="text-sm text-gray-600 mt-1">Add members directly — it only takes
                        a moment.</p>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-white/70 backdrop-blur p-5 shadow-sm hover:shadow-md transition">
                    <div
                        class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-3">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900">Start the conversation</h3>
                    <p class="text-sm text-gray-600 mt-1">Create channels, start threads, post, and plan events — all
                        in one
                        place.</p>
                </div>
            </div>
        </section>


    </div>
</div>
