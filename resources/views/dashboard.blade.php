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
    <div class="bg-gradient-to-b from-white to-gray-50/40 min-h-screen">
        <main
            class="{{ $community
                ? 'w-full max-w-none mx-auto px-4 2xl:px-8 grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8'
                : 'w-full px-0' }}">


            <style>
                body {
                    scroll-behavior: smooth;
                }
            </style>


            <!-- Posts Section -->
            <section class="lg:col-span-8 xl:col-span-9 2xl:col-span-9 space-y-6">
                @if ($community)
                    <div class="flex items-center justify-between  backdrop-blur-md px-4 py-3 rounded-xl">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">
                                Welcome back, {{ auth()->user()->name ?? 'Member' }} 👋
                            </h2>
                            <p class="text-sm text-gray-500">
                                Here’s what’s happening in {{ $community->name ?? 'your community' }} today.
                            </p>
                        </div>
                        <div class="hidden sm:block">
                            <img src="https://cdn-icons-png.flaticon.com/512/4712/4712139.png" alt="community"
                                class="w-9 h-9 opacity-70">
                        </div>
                    </div>
                @endif

                @if ($community)
                    @php
                        $userId = auth()->id();
                        $membership = $community->memberships->firstWhere('user_id', $userId);
                        $role = $membership?->role;
                        $requiresApproval = !in_array($role, ['owner', 'admin', 'moderator']);
                    @endphp

                    <div
                        class="bg-white/80 backdrop-blur-sm border border-blue-200 shadow-xl shadow-blue-100/50 rounded-2xl p-5 transition hover:shadow-blue-200/70">
                        <form method="POST" action="{{ route('posts.store', $community->slug) }}"
                            enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div class="flex gap-4 items-start">
                                <!-- Avatar -->
                                @php
                                    $user = auth()->user();
                                @endphp

                                <div
                                    class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-sky-300 to-indigo-300">
                                    @if ($user && $user->avatar)
                                        <img src="{{ $user->avatar_url }}"
                                            alt="{{ $user->name }}'s avatar" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-white font-bold text-lg">
                                            {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                        </span>
                                    @endif
                                </div>





                                <!-- Form Content -->
                                <div class="flex-1 flex flex-col gap-4">
                                    <!-- Textarea -->
                                    <textarea name="content" placeholder="Share something with {{ $community->name }}..."
                                        class="w-full bg-white border border-gray-200 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 p-4 text-gray-800 text-sm resize-none shadow-sm transition"
                                        rows="3" required></textarea>

                                    <!-- Image Preview -->
                                    <div id="image-preview-container" class="flex flex-wrap gap-3"></div>



                                    <!-- Approval Message -->
                                    @if ($requiresApproval)
                                        <div
                                            class="text-sm text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 2" />
                                            </svg>
                                            Members’ posts require approval.
                                        </div>
                                    @endif


                                    <!-- Actions -->
                                    <div class="flex justify-end items-center gap-3 pt-2">
                                        <label for="photo-upload"
                                            class="flex items-center justify-center w-10 h-10 border border-gray-200 rounded-full bg-white hover:bg-blue-50 hover:border-blue-300 text-gray-600 cursor-pointer shadow-sm transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 7h2l2-3h10l2 3h2a2 2 0 012 2v9a2 2 0 01-2 2H3a2 2 0 01-2-2V9a2 2 0 012-2zm9 3a4 4 0 100 8 4 4 0 000-8z" />
                                            </svg>
                                        </label>
                                        <input type="file" name="image" id="photo-upload" class="hidden"
                                            accept="image/*">

                                        <button type="submit"
                                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                            </svg>
                                            Post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                @else
                    <section class="col-span-3 w-full">
                        <x-community-welcome />
                    </section>

    </div>
    @endif

    <!-- Feed -->
    <div class="space-y-5">
        @if ($posts->count())
            @foreach ($posts as $post)
                @php
                    $userId = auth()->id();
                    $membership = $community->memberships->firstWhere('user_id', $userId);

                    // For UI controls only - post visibility is handled by the controller
                    $canModerate =
                        $membership &&
                        in_array($membership->role, ['owner', 'admin', 'moderator']) &&
                        $membership->status === 'active';

                    // All posts that made it to the view should be visible
                    $canSeePost = true;
                @endphp
                @if ($canSeePost)
                    <div class="bg-white/90 backdrop-blur-sm border border-blue-100 rounded-2xl shadow-md shadow-blue-100/50 p-5 relative transition-all duration-300 hover:shadow-lg hover:shadow-blue-200/70 hover:translate-y-[-2px]"
                        id="post-{{ $post->id }}" data-can-moderate="{{ $canModerate ? 'true' : 'false' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                @php
                                    $postUser = $post->user;
                                @endphp

                                <div
                                    class="w-12 h-12 rounded-full bg-gradient-to-br from-sky-300 to-indigo-300 flex items-center justify-center overflow-hidden">
                                    @if ($postUser && $postUser->avatar)
                                        <img src="{{ $postUser->avatar_url }}"
                                            alt="{{ $postUser->name }}'s avatar" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-white font-semibold">
                                            {{ strtoupper(substr($postUser->name ?? 'U', 0, 1)) }}
                                        </span>
                                    @endif
                                </div>


                                <div>
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ $post->user->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ '@' .
                                            (\App\Models\User::find($post->user_id)?->username ??
                                                Str::slug(\App\Models\User::find($post->user_id)?->name ?? 'user')) }}
                                    </p>



                                </div>
                            </div>

                            @if ($canModerate || $post->user_id === $userId)
                                <div class="relative">
                                    <button onclick="toggleDropdown({{ $post->id }})"
                                        class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                        &#x2026;
                                    </button>
                                    <div id="dropdown-{{ $post->id }}"
                                        class="absolute right-0 mt-2 w-32 bg-white border border-gray-200 rounded shadow-lg hidden z-10">
                                        @if ($canModerate && $post->status === 'pending')
                                            <form method="POST"
                                                action="{{ route('posts.update', [$community->slug, $post->id]) }}"
                                                data-community="{{ $community->slug }}"
                                                onsubmit="return handlePostAction(event)">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="published">
                                                <input type="hidden" name="content" value="{{ $post->content }}">
                                                <button type="submit"
                                                    class="block w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-gray-100">
                                                    Publish
                                                </button>
                                            </form>
                                        @endif
                                        <button onclick="startEdit({{ $post->id }})"
                                            class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</button>
                                        <form method="POST"
                                            action="{{ route('posts.destroy', [$community->slug, $post->id]) }}"
                                            data-community="{{ $community->slug }}"
                                            onsubmit="return handlePostAction(event)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="deletePost({{ $post->id }}, this)"
                                                class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>


                        <div class="post-content relative" id="content-{{ $post->id }}">
                            <p class="text-black text-sm mb-1 mt-4">{{ $post->content }}</p>


                            @if ($post->image_path)
                                <div class="mt-2">
                                    <img src="{{ $post->image_url }}"
                                        class="w-full h-auto object-contain rounded border border-gray-300" />
                                </div>
                            @endif

                            <div class="text-xs text-gray-400 mt-9">
                                Posted {{ $post->created_at->diffForHumans() }}
                                @if ($post->content_updated_at && $post->content_updated_at > $post->created_at)
                                    • <span class="text-xs text-gray-400 mt-9 italic">(edited)</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-5 mt-4 text-sm">
                                <!-- ❤️ Like Button -->
                                <button onclick="toggleLike({{ $post->id }}, '{{ $community->slug }}')"
                                    id="like-btn-{{ $post->id }}"
                                    class="flex items-center gap-2 group transition">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center bg-pink-50 border border-pink-200 text-pink-500 hover:bg-pink-100 hover:scale-105 transition">
                                        <i
                                            class="fa-solid fa-heart group-hover:scale-110 transition-transform duration-200"></i>
                                    </div>
                                    <span id="like-count-{{ $post->id }}"
                                        class="text-gray-600 group-hover:text-pink-600">
                                        {{ $post->likes->count() }}
                                    </span>
                                </button>

                                <!-- 💬 Reply Button -->
                                <button onclick="toggleCommentBox({{ $post->id }})"
                                    class="flex items-center gap-2 group transition">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center bg-blue-50 border border-blue-200 text-blue-500 hover:bg-blue-100 hover:scale-105 transition">
                                        <i
                                            class="fa-regular fa-comment group-hover:scale-110 transition-transform duration-200"></i>
                                    </div>
                                    <span class="text-gray-600 group-hover:text-blue-600">Reply</span>
                                </button>
                            </div>


                            <!-- Comment box -->
                            <div id="comment-box-{{ $post->id }}" class="hidden mt-4 animate-fadeIn">
                                <form
                                    onsubmit="return postComment(event, {{ $post->id }}, '{{ $community->slug }}')"
                                    class="flex gap-3 items-center">
                                    <input type="text" name="content" placeholder="Write a reply..."
                                        class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                                        required>
                                    <button type="submit"
                                        class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-md hover:scale-105 transition">
                                        Send
                                    </button>
                                </form>

                                <div id="comments-list-{{ $post->id }}" class="mt-3 space-y-2">
                                    @foreach ($post->comments as $comment)
                                        <div class="flex items-start gap-2" data-comment-id="{{ $comment->id }}">
                                            <div
                                                class="w-7 h-7 rounded-full bg-gradient-to-br from-sky-300 to-indigo-300 flex items-center justify-center overflow-hidden">
                                                @if ($comment->user->avatar)
                                                    <img src="{{ $comment->user->avatar_url }}"
                                                        alt="{{ $comment->user->name }}'s avatar"
                                                        class="w-full h-full object-cover">
                                                @else
                                                    <span class="text-white font-semibold text-xs">
                                                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex-1 flex items-start justify-between gap-2">
                                                <p
                                                    class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 shadow-sm">
                                                    <strong>{{ $comment->user->name }}:</strong>
                                                    {{ $comment->content }}
                                                </p>
                                                @if ($comment->user_id === auth()->id() || $canModerate)
                                                    <button
                                                        onclick="deleteComment({{ $comment->id }}, {{ $post->id }}, '{{ $community->slug }}')"
                                                        class="text-red-500 hover:text-red-600 transition">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @php
                                $statusStyles = [
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'published' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-700',
                                ];
                                $status = strtolower($post->status);
                            @endphp

                            <div class="absolute top-2 right-3 text-xs">

                                <span
                                    class="inline-block px-2 py-0.5 rounded font-medium {{ $statusStyles[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        </div>


                        <!-- Edit Form -->
                        @if ($canModerate || $post->user_id === $userId)
                            <form method="POST" action="{{ route('posts.update', [$community->slug, $post->id]) }}"
                                enctype="multipart/form-data" class="edit-form space-y-3 mt-2 hidden"
                                id="edit-form-{{ $post->id }}" data-community="{{ $community->slug }}"
                                onsubmit="return handlePostAction(event)">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="remove_image" id="remove-image-{{ $post->id }}"
                                    value="0">

                                <textarea name="content" rows="3" required
                                    class="w-full bg-blue-50 border border-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 p-3 text-sm text-gray-800 resize-none">{{ $post->content }}</textarea>

                                <div id="edit-image-preview-{{ $post->id }}"
                                    class="relative w-full max-w-xs mt-2">
                                    @if ($post->image_path)
                                        <img id="edit-crop-preview-{{ $post->id }}"
                                            src="{{ $post->image_url }}"
                                            class="rounded border object-contain w-full max-h-60 shadow" />
                                        <button type="button" onclick="startEditCrop({{ $post->id }})"
                                            class="absolute top-1 left-1 bg-white text-blue-600 border border-blue-200 w-7 h-7 flex items-center justify-center shadow hover:bg-blue-50 transition rounded-full"
                                            title="Crop image">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M6 2v14a2 2 0 0 0 2 2h14" />
                                                <path d="M2 6h14a2 2 0 0 1 2 2v14" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="removeEditPreview({{ $post->id }})"
                                            class="absolute top-1 right-1 bg-white text-red-600 border border-red-200 w-7 h-7 flex items-center justify-center shadow hover:bg-red-50 transition rounded-full"
                                            title="Remove image">×</button>
                                    @endif
                                </div>

                                <input type="file" name="image" id="edit-photo-upload-{{ $post->id }}"
                                    class="hidden" accept="image/*">
                                <label for="edit-photo-upload-{{ $post->id }}"
                                    class="flex items-center justify-center w-10 h-10 border border-gray-200 rounded-full bg-white hover:bg-blue-50 hover:border-blue-300 text-gray-600 cursor-pointer shadow-sm transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 7h2l2-3h10l2 3h2a2 2 0 012 2v9a2 2 0 01-2 2H3a2 2 0 01-2-2V9a2 2 0 012-2zm9 3a4 4 0 100 8 4 4 0 000-8z" />
                                    </svg>
                                </label>

                                <div class="flex gap-3">
                                    <button type="submit"
                                        class="bg-blue-600 text-white px-3 py-1 text-sm rounded hover:bg-blue-700">Save</button>
                                    <button type="button" onclick="cancelEdit({{ $post->id }})"
                                        class="text-gray-600 hover:underline text-sm">Cancel</button>
                                </div>
                            </form>
                        @endif

                    </div>
                @endif
            @endforeach
        @else
            @if ($community)
                <div class="flex flex-col items-center justify-center mt-20">
                    <p class="text-gray-600 text-md">No posts yet — be the first to share something!</p>
                </div>
            @endif
        @endif
    </div>

    </section>

    @if ($community)
        <aside id="sidebar" class="lg:col-span-4 xl:col-span-3 2xl:col-span-3 space-y-6">
            <div
                class="bg-white/70 backdrop-blur-xl border border-blue-100/60 rounded-2xl shadow-[0_8px_24px_rgba(59,130,246,0.2)] hover:shadow-[0_12px_30px_rgba(59,130,246,0.3)] transition-all duration-300 p-6">

                <h3 class="text-2xl font-bold text-gray-900 mb-4">
                    {{ $community->name ?? 'Community Info' }}
                    Information
                </h3>


                <!-- Activity -->
                <div class="border-b border-gray-300 pb-3 mb-3">
                    <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Activity
                    </h4>
                    <p id="community-activity" class="mt-1 text-sm text-gray-600">
                        {{ $community->memberships->count() }} active
                        member{{ $community->memberships->count() !== 1 ? 's' : '' }}
                    </p>
                </div>

                <!-- Leaders -->
                <div class="border-b border-gray-300 pb-3 mb-3">
                    <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A4 4 0 016 16h12a4 4 0 01.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Leaders
                    </h4>
                    <ul id="community-leaders" class="mt-1 text-sm text-gray-600 space-y-1">
                        <li>{{ $community->owner->name ?? 'Unknown Owner' }}</li>
                        @foreach ($community->memberships->where('role', 'admin') as $admin)
                            @if ($admin->user->id !== $community->owner->id)
                                <li>{{ $admin->user->name }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                <!-- Quick Info -->
                <div>
                    <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Quick Info
                    </h4>
                    <p id="community-info" class="mt-1 text-sm text-gray-600">
                        {{ ucfirst($community->visibility) }} •
                        {{ ucfirst(str_replace('_', ' ', $community->join_policy)) }}
                    </p>
                </div>
            </div>
            @if (!empty($community->tags))
                <div class="mt-4 border-t border-gray-300 pt-3">
                    <h4 class="text-gray-700 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9 9 9-9" />
                        </svg>
                        Tags
                    </h4>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($community->tags as $tag)
                            <span
                                class="px-3 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full shadow-sm">
                                {{ ucfirst($tag) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

        </aside>

    @endif
    </main>
    </div>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
        // Track removed images so they don't reappear
        const removedImages = new Set();

        function toggleDropdown(postId) {
            const dropdown = document.getElementById(`dropdown-${postId}`);
            dropdown.classList.toggle('hidden');
        }

        function startEdit(postId) {
            setupEditImagePreview(postId);

            // Check if this image was marked for removal
            const hiddenFlag = document.getElementById(`remove-image-${postId}`);
            if (removedImages.has(postId) && hiddenFlag) {
                hiddenFlag.value = '1';
            }

            cancelAllDropdowns();
            document.getElementById(`content-${postId}`).style.display = 'none';
            document.getElementById(`edit-form-${postId}`).style.display = 'block';
        }


        function cancelEdit(postId) {
            document.getElementById(`edit-form-${postId}`).style.display = 'none';
            document.getElementById(`content-${postId}`).style.display = 'block';
        }

        function cancelAllDropdowns() {
            document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        }

        function handlePostAction(event) {
            event.preventDefault();
            const form = event.target;
            const slug = form.dataset.community;
            const formData = new FormData(form);

            fetch(form.action, {
                method: form.method,
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'Accept': 'text/html'
                },
                body: formData
            }).then((response) => {
                if (response.ok) {
                    // Clear any localStorage entries for image removals since the form was successful
                    localStorage.removeItem('removedImages');
                }
                window.location.href = `/dashboard?community=${slug}`;
            }).catch(() => {
                alert('Something went wrong. Please try again.');
            });

            return false;
        }

        let cropper = null;
        let currentFile = null;
        let originalImageSrc = null;
        let lastCropBoxData = null;
        let lastCroppedImageUrl = null;

        const input = document.getElementById('photo-upload');
        const previewContainer = document.getElementById('image-preview-container');

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            currentFile = file;
            const reader = new FileReader();
            reader.onload = (event) => {
                originalImageSrc = event.target.result;
                showPreviewWithCropButton(originalImageSrc);
            };
            reader.readAsDataURL(file);
        });

        function showPreviewWithCropButton(imageSrc) {
            previewContainer.innerHTML = `
        <div class="relative w-full max-w-xs">
            <img id="crop-preview-image" src="${imageSrc}" class="rounded border object-contain w-full max-h-60 shadow" />
            <button type="button" onclick="startInlineCrop()" title="Crop image"
                class="absolute top-1 left-1 bg-white text-blue-600 border border-blue-200 w-7 h-7 flex items-center justify-center shadow hover:bg-blue-50 transition rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2v14a2 2 0 0 0 2 2h14"/>
                    <path d="M2 6h14a2 2 0 0 1 2 2v14"/>
                </svg>
            </button>
            <button onclick="removePreview()" title="Remove image"
                class="absolute top-1 right-1 bg-white text-red-600 border border-red-200 w-7 h-7 flex items-center justify-center shadow hover:bg-red-50 transition rounded-full">
                ×
            </button>
        </div>
    `;
        }


        function startInlineCrop() {
            const img = document.getElementById('crop-preview-image');
            if (!img || !originalImageSrc) return;

            img.src = originalImageSrc; // ✅ always start from full image

            if (cropper) cropper.destroy();

            cropper = new Cropper(img, {
                aspectRatio: NaN,
                viewMode: 1,
                autoCropArea: 1,
                background: false,
                responsive: true,
                ready() {
                    if (lastCropBoxData) {
                        cropper.setData(lastCropBoxData);
                    }
                }
            });

            // Remove existing buttons
            document.getElementById('apply-crop-btn')?.remove();
            document.getElementById('exit-crop-btn')?.remove();

            // Apply button
            const applyBtn = document.createElement('button');
            applyBtn.id = 'apply-crop-btn';
            applyBtn.textContent = 'Crop';
            applyBtn.className =
                'absolute bottom-2 right-2 bg-blue-600 text-white px-1.5 py-0.5 rounded text-sm shadow-sm hover:bg-blue-700';
            applyBtn.onclick = () => {
                lastCropBoxData = cropper.getData();
                applyInlineCrop();
                applyBtn.remove();
                exitBtn.remove();
            };
            img.parentElement.appendChild(applyBtn);

            // Cancel button
            const exitBtn = document.createElement('button');
            exitBtn.id = 'exit-crop-btn';
            exitBtn.textContent = 'Cancel';
            exitBtn.className =
                'absolute bottom-2 left-2 bg-gray-300 text-gray-700 px-1.5 py-0.5 rounded text-sm shadow-sm hover:bg-gray-400';
            exitBtn.onclick = () => {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }

                // ✅ revert to last cropped image if available
                if (lastCroppedImageUrl) {
                    img.src = lastCroppedImageUrl;
                } else {
                    img.src = originalImageSrc;
                }

                applyBtn.remove();
                exitBtn.remove();
            };
            img.parentElement.appendChild(exitBtn);
        }


        function applyInlineCrop() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                maxWidth: 1200,
                maxHeight: 1200
            });
            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                lastCroppedImageUrl = url; // ✅ store cropped version

                const img = document.getElementById('crop-preview-image');
                img.src = url;

                cropper.destroy();
                cropper = null;

                const dt = new DataTransfer();
                const croppedFile = new File([blob], currentFile.name, {
                    type: 'image/png'
                });
                dt.items.add(croppedFile);
                document.getElementById('photo-upload').files = dt.files;
            }, 'image/png');
        }


        function removePreview() {
            previewContainer.innerHTML = '';
            document.getElementById('photo-upload').value = '';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            originalImageSrc = null;
            lastCropBoxData = null;
        }

        async function deletePost(postId, button) {
            const slug = button.closest('form')?.dataset.community;
            if (!slug) return showToastify('Community slug missing', 'error');

            showConfirmToast(
                'Are you sure you want to delete this post?',
                async () => {
                        button.disabled = true;
                        const original = button.textContent;
                        button.textContent = 'Deleting...';

                        try {
                            const token = button.closest('form').querySelector('input[name="_token"]').value;

                            const res = await fetch(`/communities/${slug}/posts/${postId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Content-Type': 'application/json'
                                }
                            });

                            if (res.ok) {
                                const postEl = document.getElementById(`post-${postId}`);
                                if (postEl) postEl.remove();
                                showToastify('Post deleted successfully.', 'success');
                            } else {
                                const data = await res.json().catch(() => ({}));
                                showToastify(data.message || 'Failed to delete post.', 'error');
                            }
                        } catch (e) {
                            console.error(e);
                            showToastify('Failed to delete post.', 'error');
                        } finally {
                            button.disabled = false;
                            button.textContent = original;
                        }
                    },
                    'bg-red-400 hover:bg-red-500',
                    'Delete'
            );
        }

        function setupEditImagePreview(postId) {
            const input = document.getElementById(`edit-photo-upload-${postId}`);
            const previewContainer = document.getElementById(`edit-image-preview-${postId}`);

            let cropper = null;
            let originalImageSrc = null;
            let lastCropBoxData = null;
            let lastCroppedImageUrl = null;
            let currentFile = null;

            // If there's an existing image, initialize originalImageSrc
            const existingImg = document.getElementById(`edit-crop-preview-${postId}`);
            if (existingImg && !removedImages.has(postId)) {
                originalImageSrc = existingImg.src;
            } else {
                previewContainer.innerHTML = ''; // ensure no leftover HTML
            }


            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                currentFile = file;
                const reader = new FileReader();
                reader.onload = (event) => {
                    originalImageSrc = event.target.result;
                    showEditPreview(originalImageSrc);
                };
                reader.readAsDataURL(file);
            });

            function showEditPreview(imageSrc) {
                previewContainer.innerHTML = `
            <div class="relative w-full max-w-xs">
                <img id="edit-crop-preview-${postId}" src="${imageSrc}" class="rounded border object-contain w-full max-h-60 shadow" />
                <button type="button" onclick="startEditCrop(${postId})"
                    class="absolute top-1 left-1 bg-white text-blue-600 border border-blue-200 w-7 h-7 flex items-center justify-center shadow hover:bg-blue-50 transition rounded-full"
                    title="Crop image">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2v14a2 2 0 0 0 2 2h14"/>
                        <path d="M2 6h14a2 2 0 0 1 2 2v14"/>
                    </svg>
                </button>
                <button onclick="removeEditPreview(${postId})"
                    class="absolute top-1 right-1 bg-white text-red-600 border border-red-200 w-7 h-7 flex items-center justify-center shadow hover:bg-red-50 transition rounded-full"
                    title="Remove image">×</button>
            </div>
        `;
            }

            window.startEditCrop = function(postId) {
                const img = document.getElementById(`edit-crop-preview-${postId}`);
                if (!img || !originalImageSrc) return;

                img.src = originalImageSrc;

                cropper = new Cropper(img, {
                    aspectRatio: NaN,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                    ready() {
                        if (lastCropBoxData) {
                            cropper.setData(lastCropBoxData); // ✅ apply previous crop box
                        }
                    }
                });

                document.getElementById(`apply-crop-btn-${postId}`)?.remove();
                document.getElementById(`exit-crop-btn-${postId}`)?.remove();

                const applyBtn = document.createElement('button');
                applyBtn.id = `apply-crop-btn-${postId}`;
                applyBtn.textContent = 'Crop';
                applyBtn.className =
                    'absolute bottom-2 right-2 bg-blue-600 text-white px-1.5 py-0.5 rounded text-xs shadow-sm hover:bg-blue-700';
                applyBtn.onclick = () => {
                    lastCropBoxData = cropper.getData();
                    applyEditCrop();
                    applyBtn.remove();
                    cancelBtn.remove();
                };
                img.parentElement.appendChild(applyBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.id = `exit-crop-btn-${postId}`;
                cancelBtn.textContent = 'Cancel';
                cancelBtn.className =
                    'absolute bottom-2 right-14 bg-gray-300 text-gray-700 px-1.5 py-0.5 rounded text-xs shadow-sm hover:bg-gray-400';
                cancelBtn.onclick = () => {
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    img.src = lastCroppedImageUrl || originalImageSrc;
                    applyBtn.remove();
                    cancelBtn.remove();
                };
                img.parentElement.appendChild(cancelBtn);
            };

            function applyEditCrop() {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 1200,
                    maxHeight: 1200
                });
                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    lastCroppedImageUrl = url;

                    const img = document.getElementById(`edit-crop-preview-${postId}`);
                    img.src = url;

                    cropper.destroy();
                    cropper = null;

                    const dt = new DataTransfer();
                    const croppedFile = new File([blob], currentFile?.name || `cropped-${postId}.png`, {
                        type: 'image/png'
                    });
                    dt.items.add(croppedFile);
                    document.getElementById(`edit-photo-upload-${postId}`).files = dt.files;
                }, 'image/png');
            }

        }

        document.addEventListener('DOMContentLoaded', () => {
            // When page loads, remove any images marked as "removed" before
            const removed = JSON.parse(localStorage.getItem('removedImages') || '[]');
            removed.forEach(id => {
                const container = document.getElementById(`edit-image-preview-${id}`);
                const hiddenFlag = document.getElementById(`remove-image-${id}`);

                if (container) container.innerHTML = '';
                if (hiddenFlag) hiddenFlag.value = '1';

                // Also add to our Set for consistency
                removedImages.add(id);
            });
        });

        // When the user clicks ✖, also store that removal persistently
        window.removeEditPreview = function(postId) {
            const previewContainer = document.getElementById(`edit-image-preview-${postId}`);
            const input = document.getElementById(`edit-photo-upload-${postId}`);
            const hiddenFlag = document.getElementById(`remove-image-${postId}`);

            // Clear the preview container
            if (previewContainer) previewContainer.innerHTML = '';

            // Clear the file input
            if (input) input.value = '';

            // Set the removal flag to indicate the image should be removed
            if (hiddenFlag) hiddenFlag.value = '1';

            // Track this post's image as removed in our Set
            removedImages.add(postId);

            // Remember removal across reloads
            let removed = JSON.parse(localStorage.getItem('removedImages') || '[]');
            if (!removed.includes(postId)) {
                removed.push(postId);
                localStorage.setItem('removedImages', JSON.stringify(removed));
            }
        };
        async function toggleLike(postId, slug) {
            const res = await fetch(`/communities/${slug}/posts/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            document.getElementById(`like-count-${postId}`).textContent = data.like_count;
        }

        function toggleCommentBox(postId) {
            const box = document.getElementById(`comment-box-${postId}`);
            box.classList.toggle('hidden');
        }

        async function deleteComment(commentId, postId, slug) {
            showConfirmToast(
                'Are you sure you want to delete this comment?',
                async () => {
                        const res = await fetch(`/communities/${slug}/posts/${postId}/comment/${commentId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        });

                        if (res.ok) {
                            try {
                                // Find and remove the comment element
                                const commentList = document.getElementById(`comments-list-${postId}`);
                                const commentElement = commentList.querySelector(
                                    `[data-comment-id="${commentId}"]`);
                                if (commentElement) {
                                    // Add fade-out animation
                                    commentElement.style.transition = 'opacity 0.2s ease-out';
                                    commentElement.style.opacity = '0';
                                    setTimeout(() => {
                                        commentElement.remove();
                                    }, 200);
                                    showToastify('Comment deleted successfully.', 'success');
                                }
                            } catch (err) {
                                console.error('Error removing comment from UI:', err);
                            }
                        } else {
                            showToastify('Failed to delete comment.', 'error');
                        }
                    },
                    'bg-red-400 hover:bg-red-500',
                    'Delete'
            );
        }

        async function postComment(e, postId, slug) {
            e.preventDefault();
            const content = e.target.content.value.trim();
            if (!content) return;

            const res = await fetch(`/communities/${slug}/posts/${postId}/comment`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content
                })
            });
            const data = await res.json();

            if (data.success) {
                const list = document.getElementById(`comments-list-${postId}`);
                // Get canModerate value from the post's container
                const postContainer = document.getElementById(`post-${postId}`);
                const canModerate = postContainer.querySelector('[data-can-moderate="true"]') !== null;

                list.innerHTML += `
    <div class="flex items-start gap-2 animate-fadeIn" data-comment-id="${data.comment.id}">
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-sky-300 to-indigo-300 flex items-center justify-center overflow-hidden">
            ${data.comment.avatar 
                ? `<img src="/storage/${data.comment.avatar}" alt="${data.comment.user}'s avatar" class="w-full h-full object-cover">`
                : `<span class="text-white font-semibold text-xs">${data.comment.user.charAt(0).toUpperCase()}</span>`
            }
        </div>
        <div class="flex-1 flex items-start justify-between gap-2">
            <p class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 shadow-sm">
                <strong>${data.comment.user}:</strong> ${data.comment.content}
            </p>
            ${(data.comment.is_author || canModerate) ? `
                            <button onclick="deleteComment(${data.comment.id}, ${postId}, '${slug}')" 
                                    class="text-red-500 hover:text-red-600 transition">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        ` : ''}
        </div>
    </div>
`;
                e.target.reset();
            }
            return false;
        }
    </script>




</x-layout>
