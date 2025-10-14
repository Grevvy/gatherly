@php
    use App\Models\Community;
    use Illuminate\Support\Facades\Storage;

    $community = null;
    $slug = request('community');

    if ($slug) {
        $community = Community::with(['owner', 'memberships.user'])
            ->where('slug', $slug)
            ->first();
    }

    // load communities the current user belongs to (for sidebar)
    $communities = collect();
    if (auth()->check()) {
        $communities = Community::whereHas('memberships', function ($q) {
            $q->where('user_id', auth()->id());
        })->get();
    }
@endphp

<x-layout :title="'Dashboard - Gatherly'" :community="$community" :communities="$communities">
    <div class="bg-gray-50 min-h-screen">
        <main class="max-w-6xl mx-auto mt-6 px-4 grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Posts Section -->
            <section class="lg:col-span-2 space-y-6">
                @if ($community)
                    <div class="bg-white border border-gray-200 shadow p-6">
                        <div class="flex gap-5 items-start">
                            <div
                                class="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>

                            <div class="flex-1">
                                <textarea placeholder="What's happening in {{ $community->name }}?"
                                    class="w-full bg-blue-50 border border-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 p-4 text-gray-800 placeholder-gray-400 text-sm resize-none"
                                    rows="3"></textarea>

                                <div class="flex justify-between items-center mt-4">
                                    <span class="flex items-center gap-1 text-sm text-orange-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3" />
                                        </svg>
                                        Members' posts require approval.
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <!-- Photo Button -->
                                        <label for="photo-upload"
                                            class="flex items-center justify-center w-10 h-10   hover:bg-gray-100 text-gray-700 cursor-pointer transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 7h2l2-3h10l2 3h2a2 2 0 012 2v9a2 2 0 01-2 2H3a2 2 0 01-2-2V9a2 2 0 012-2zm9 3a4 4 0 100 8 4 4 0 000-8z" />
                                            </svg>
                                        </label>
                                        <input type="file" id="photo-upload" class="hidden" accept="image/*">

                                        <!-- Post Button -->
                                        <button type="button"
                                            class="inline-flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 shadow transition">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                            </svg>
                                            Post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="min-h-screen flex flex-col items-center pt-16">
                        <div class="text-center text-gray-500 flex flex-col items-center gap-2 pl-32">
                            <!-- Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>

                            <!-- Text -->
                            <span>Select/Create a community to get started.</span>
                        </div>
                    </div>
                @endif

                <!-- Feed -->
                <div class="space-y-5">
                    @if ($community)
                        <div class="flex items-center justify-center h-40 text-gray-600">
                            No posts yet.
                        </div>
                    @endif
                </div>
            </section>

            @if ($community)
                <aside id="sidebar" class="space-y-6">
                    <div class="bg-white border border-gray-200 p-6 shadow">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Community Info</h3> <!-- Activity -->
                        <div class="border-b border-gray-300 pb-3 mb-3">
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2"> <svg
                                    class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg> Activity </h4>
                            <p id="community-activity" class="mt-1 text-sm text-gray-600">
                                {{ $community->memberships->count() }} active members </p>
                        </div> <!-- Leaders -->
                        <div class="border-b border-gray-300 pb-3 mb-3">
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2"> <svg
                                    class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.121 17.804A4 4 0 016 16h12a4 4 0 01.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg> Leaders </h4>
                            <ul id="community-leaders" class="mt-1 text-sm text-gray-600 space-y-1">
                                <li>{{ $community->owner->name ?? 'Unknown Owner' }}</li>
                            </ul>
                        </div> <!-- Quick Info -->
                        <div>
                            <h4 class="text-gray-700 font-semibold flex items-center gap-2"> <svg
                                    class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg> Quick Info </h4>
                            <p id="community-info" class="mt-1 text-sm text-gray-600">
                                {{ ucfirst($community->visibility) }} • {{ ucfirst($community->join_policy) }} </p>
                        </div>
                    </div>
                </aside>
            @endif
        </main>
    </div>
</x-layout>
