@php
    use App\Models\Community;

    $slug = request('community');
    $photoId = request('photo');
    $community = $slug
        ? Community::with(['owner', 'memberships.user', 'photos.user'])
            ->where('slug', $slug)
            ->first()
        : null;

    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
@endphp

<x-layout :title="'Dashboard - Gatherly'" :community="$community" :communities="$communities">
    <div class="bg-gradient-to-b from-white to-gray-50/40 min-h-screen">
        <div class="space-y-8 pt-5 max-w-5xl mx-auto">
            <!-- Header Section -->
            @if ($community)
                <div class="mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-center gap-3">
                        <div class="md:col-start-2 md:justify-self-center text-center">
                            <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-2">
                                {{ Str::endsWith($community->name, 's') ? $community->name . '’' : $community->name . '’s' }}
                                Photo Gallery
                            </h2>
                            <div class="h-1 w-24 mx-auto mt-2 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500">
                            </div>
                        </div>

                        <a href="{{ route('photos.create', ['community' => $community->slug]) }}"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300 justify-self-center md:justify-self-end">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Upload Photo
                        </a>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="max-w-md mx-auto">
                        <h3 class="text-xl font-medium text-gray-900 mb-2">Select a Community</h3>
                        <p class="text-gray-500">Choose a community from the sidebar to view its photo gallery.</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- 📸 Photo Grid -->
        <div id="photoGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($photos ?? [] as $photo)
                <div class="relative group w-full max-w-[40rem] rounded-2xl shadow-sm border border-gray-200 bg-white transition-transform transform duration-300 ease-out hover:scale-[1.02] hover:z-50 hover:shadow-2xl cursor-pointer photo-card"
                    data-photo-id="{{ data_get($photo, 'id') }}"
                    data-photo-url="{{ data_get($photo, 'image_url', '') }}"
                    data-photo-caption="{{ data_get($photo, 'caption', '') }}"
                    data-photo-user="{{ data_get($photo, 'user.name', 'Anonymous') }}"
                    data-photo-time="{{ optional(data_get($photo, 'created_at'))->diffForHumans() ?? '' }}"
                    data-photo-status="{{ data_get($photo, 'status') }}">

                    <!-- Image area -->
                    <div class="relative w-full h-60 overflow-hidden rounded-t-2xl">
                        <img src="{{ data_get($photo, 'image_url', 'https://via.placeholder.com/400x300?text=Photo') }}"
                            alt="Community photo"
                            class="w-full h-full object-cover transition-transform duration-300 ease-out group-hover:scale-105">
                        @if ($photo->isPending())
                            <span
                                class="absolute top-2 right-2 inline-flex items-center rounded-full bg-yellow-100/95 px-2.5 py-1 text-xs font-medium text-yellow-800 shadow-sm">
                                Pending Approval
                            </span>
                        @endif
                    </div>

                    <!-- Info area (visible under image) -->
                    <div class="h-px w-full bg-gray-200 "></div>

                    <div class="text-sm px-3 {{ $photo->isPending() ? 'py-2' : 'py-1' }} flex items-end min-h-12">
                        <div class="flex justify-between items-end gap-2 w-full">
                            <div class="min-w-0 flex flex-col justify-end">
                                @if (!empty(data_get($photo, 'caption')))
                                    <p class="mt-0.5 text-[13px] text-gray-600 truncate "
                                        title="{{ data_get($photo, 'caption') }}">
                                        “{{ data_get($photo, 'caption') }}”
                                    </p>
                                @endif

                                <p class="font-semibold text-gray-900 truncate">
                                    {{ data_get($photo, 'user.name', 'Anonymous') }}
                                    <span class="ml-2 text-xs font-normal text-gray-500">•
                                        {{ optional(data_get($photo, 'created_at'))->diffForHumans() ?? 'Just now' }}</span>
                                </p>

                            </div>

                            <form id="csrf-form" class="hidden">
                                @csrf
                            </form>
                            @auth
                                <div class="flex flex-wrap gap-2 shrink-0" onclick="event.stopPropagation()">
                                    @if ($photo->isPending() && $isModeratorOrOwner)
                                        <form action="{{ route('photos.approve', $photo) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="bg-green-500 hover:bg-green-600 text-white rounded px-2 py-1 text-xs">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('photos.reject', $photo) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to reject this photo? This will delete it permanently.')">
                                            @csrf
                                            <button type="submit"
                                                class="bg-red-500 hover:bg-red-600 text-white rounded px-2 py-1 text-xs">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    @if (
                                        (data_get($photo, 'user_id') === optional(auth()->user())->id && $photo->isPending()) ||
                                            (($isModeratorOrOwner ?? false) && $photo->isApproved()))
                                        <button type="button" data-photo-id="{{ data_get($photo, 'id') }}"
                                            class="delete-photo-btn bg-gray-500 hover:bg-red-600 text-white rounded px-2 py-1 text-xs">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            @endauth
                        </div>


                    </div>
                </div>

            @empty
                <div class="text-center py-12 col-span-full">
                    <div class="max-w-md mx-auto">
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No Photos Yet</h3>
                        <p class="text-gray-500">Be the first to upload a photo to this community!</p>
                    </div>
                </div>
            @endforelse
        </div>

    </div>

    <!-- Include the photo modal component -->
    <x-photo-modal />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete photo functionality
            document.querySelectorAll('.delete-photo-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent opening modal when clicking delete
                    const photoId = this.dataset.photoId;

                    showConfirmToast(
                        'Are you sure you want to delete this photo?',
                        async () => {
                                try {
                                    const res = await fetch(`/photos/${photoId}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector(
                                                    '#csrf-form input[name="_token"]')
                                                .value,
                                            'Accept': 'application/json'
                                        }
                                    });

                                    if (res.ok) {
                                        const photoCard = document.querySelector(
                                            `[data-photo-id="${photoId}"]`).closest(
                                            '.photo-card');
                                        photoCard.remove();
                                        showToastify('Photo deleted successfully', 'success');
                                    } else {
                                        const data = await res.json().catch(() => ({}));
                                        showToastify(data.message || 'Failed to delete photo',
                                            'error');
                                    }
                                } catch (err) {
                                    console.error(err);
                                    showToastify('Something went wrong deleting the photo',
                                        'error');
                                }
                            },
                            'bg-red-400 hover:bg-red-500',
                            'Delete'
                    );
                });
            });

            // Photo modal functionality
            const photoModal = document.getElementById('photoModal');
            const modalPhoto = document.getElementById('modalPhoto');
            const modalPhotoUser = document.getElementById('modalPhotoUser');
            const modalPhotoTime = document.getElementById('modalPhotoTime');
            const modalPhotoCaption = document.getElementById('modalPhotoCaption');
            const prevButton = document.getElementById('prevPhoto');
            const nextButton = document.getElementById('nextPhoto');
            let currentPhotoId = null;

            function showPhoto(photoCard) {
                const photoId = photoCard.dataset.photoId;
                const photoUrl = photoCard.dataset.photoUrl;
                const photoCaption = photoCard.dataset.photoCaption;
                const photoUser = photoCard.dataset.photoUser;
                const photoTime = photoCard.dataset.photoTime;
                // small helper to escape user-provided text when inserting as HTML
                function escapeHtml(unsafe) {
                    if (!unsafe) return '';
                    return unsafe
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                modalPhoto.src = photoUrl;
                // show username and timestamp inline with a dot separator
                modalPhotoUser.innerHTML =
                    `${escapeHtml(photoUser)} <span class="ml-2 text-xs font-normal text-gray-300">• ${escapeHtml(photoTime)}</span>`;
                // set caption with quotes around it
                modalPhotoCaption.textContent = photoCaption ? `“${photoCaption}”` : '';
                currentPhotoId = photoId;

                // Update navigation buttons
                const allPhotos = Array.from(document.querySelectorAll('.photo-card'));
                const currentIndex = allPhotos.findIndex(card => card.dataset.photoId === photoId);
                prevButton.disabled = currentIndex === 0;
                nextButton.disabled = currentIndex === allPhotos.length - 1;

                photoModal.classList.remove('hidden');
            }

            // Handle photo clicks
            document.querySelectorAll('.photo-card').forEach(card => {
                card.addEventListener('click', () => showPhoto(card));
            });

            // Close modal
            document.getElementById('closePhotoModal').addEventListener('click', () => {
                photoModal.classList.add('hidden');
            });

            // Navigation
            prevButton.addEventListener('click', () => {
                const allPhotos = Array.from(document.querySelectorAll('.photo-card'));
                const currentIndex = allPhotos.findIndex(card => card.dataset.photoId === currentPhotoId);
                if (currentIndex > 0) {
                    showPhoto(allPhotos[currentIndex - 1]);
                }
            });

            nextButton.addEventListener('click', () => {
                const allPhotos = Array.from(document.querySelectorAll('.photo-card'));
                const currentIndex = allPhotos.findIndex(card => card.dataset.photoId === currentPhotoId);
                if (currentIndex < allPhotos.length - 1) {
                    showPhoto(allPhotos[currentIndex + 1]);
                }
            });

            // Keyboard navigation
            window.addEventListener('keydown', (e) => {
                if (photoModal.classList.contains('hidden')) return;

                if (e.key === 'Escape') {
                    photoModal.classList.add('hidden');
                } else if (e.key === 'ArrowLeft' && !prevButton.disabled) {
                    prevButton.click();
                } else if (e.key === 'ArrowRight' && !nextButton.disabled) {
                    nextButton.click();
                }
            });

            // Auto-open photo from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const photoParam = urlParams.get('photo');
            if (photoParam) {
                const photoCard = document.querySelector(`.photo-card[data-photo-id="${photoParam}"]`);
                if (photoCard) {
                    showPhoto(photoCard);
                }
            }

            // Handle file input change
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', function(event) {
                    if (event.target.files && event.target.files[0]) {
                        if (event.target.files[0].size > 5 * 1024 * 1024) {
                            Toastify({
                                text: "File size must be less than 5MB",
                                duration: 3000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: "rgb(239, 68, 68)",
                                }
                            }).showToast();
                            event.target.value = '';
                        }
                    }
                });
            }
        });
    </script>
</x-layout>
