@php
    use App\Models\Community;

    $slug = request('community');
    $community = $slug
        ? Community::with(['owner', 'memberships.user', 'photos.user'])
            ->where('slug', $slug)
            ->first()
        : null;

    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();

    $photos = $community ? $community->photos : collect();

    // Check if user is moderator/owner
    $isModeratorOrOwner = false;
    if ($community && auth()->check()) {
        $membership = $community
            ->memberships()
            ->where('user_id', auth()->id())
            ->first();
        $isModeratorOrOwner = $membership && in_array($membership->role, ['owner', 'admin']);
    }
@endphp

<x-layout :title="'Dashboard - Gatherly'" :community="$community" :communities="$communities">
    <div class="space-y-8">
        <!-- Header Section -->
        @if ($community)
            <div class="flex justify-between items-center">
                <div class="text-center mb-3">
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-2">
                        {{ $community->name }} Photo Gallery
                    </h1>
                    <div class="h-1 w-24 mx-auto rounded-full bg-gradient-to-r from-blue-300 to-cyan-300"></div>
                </div>

                <a href="{{ route('photos.create', ['community' => $community->slug]) }}"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Photo
                </a>

            </div>
        @else
            <div class="text-center py-12">
                <div class="max-w-md mx-auto">
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Select a Community</h3>
                    <p class="text-gray-500">Choose a community from the sidebar to view and share photos.</p>
                </div>
            </div>
        @endif

        <!-- 📸 Photo Grid -->
        <div id="photoGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($photos ?? [] as $photo)
                <div class="photo-item relative group w-full max-w-[40rem] h-60 overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white transition-transform transform duration-300 ease-out hover:scale-105 hover:z-50 hover:shadow-2xl cursor-pointer"
                    data-photo-id="{{ $photo->id }}"
                    data-photo-url="{{ $photo->image_url ?? 'https://via.placeholder.com/400x300?text=Photo' }}"
                    data-photo-caption="{{ $photo->caption ?? '' }}"
                    data-photo-user="{{ $photo->user->name ?? 'Anonymous' }}"
                    data-photo-date="{{ $photo->created_at?->diffForHumans() ?? 'Just now' }}">

                    <img src="{{ $photo->image_url ?? 'https://via.placeholder.com/400x300?text=Photo' }}"
                        alt="Community photo"
                        class="w-full h-full object-cover transition-transform duration-300 ease-out photo-trigger">

                    <!-- Overlay info -->
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                        <div class="p-4 text-white text-sm w-full">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-semibold truncate">{{ $photo->user->name ?? 'Anonymous' }}</p>
                                    <p class="text-xs text-gray-200">
                                        {{ $photo->created_at?->diffForHumans() ?? 'Just now' }}</p>
                                    @if (!empty($photo->caption))
                                        <p class="mt-1 text-xs italic truncate">"{{ $photo->caption }}"</p>
                                    @endif
                                </div>
                                <form id="csrf-form" class="hidden">
                                    @csrf
                                </form>
                                @auth
                                    <div class="flex gap-2">
                                        @if ($photo->isPending() && $isModeratorOrOwner)
                                            <form action="{{ route('photos.approve', $photo) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-green-500 hover:bg-green-600 text-white rounded px-2 py-1 text-xs">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('photos.reject', $photo) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Are you sure you want to reject this photo? This will delete it permanently.')">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white rounded px-2 py-1 text-xs">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif

                                        @if (($photo->user_id === auth()->user()->id && $photo->isPending()) || ($isModeratorOrOwner && $photo->isApproved()))
                                            <button type="button" data-photo-id="{{ $photo->id }}"
                                                class="delete-photo-btn bg-gray-500 hover:bg-gray-600 text-white rounded px-2 py-1 text-xs">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                @endauth
                            </div>
                            @if ($photo->isPending())
                                <span
                                    class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                    Pending Approval
                                </span>
                            @endif
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

    <!-- Photo Lightbox Modal -->
    <div id="photo-lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden"
        style="display: none; align-items: center; justify-content: center;">
        <div class="relative w-full h-full flex items-center justify-center p-4">
            <!-- Close Button -->
            <button id="close-lightbox"
                class="absolute top-4 right-4 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Previous Button -->
            <button id="prev-photo"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full w-12 h-12 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Next Button -->
            <button id="next-photo"
                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full w-12 h-12 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Image Container -->
            <div class="flex items-center justify-center max-w-full max-h-full">
                <img id="lightbox-image" src="" alt=""
                    class="max-w-full max-h-[calc(100vh-120px)] object-contain rounded-lg shadow-2xl">
            </div>

            <!-- Photo Info - Fixed at bottom -->
            <div id="lightbox-info"
                class="absolute bottom-0 left-0 right-0 text-center text-white bg-black bg-opacity-75 p-4 h-24 flex flex-col justify-center">
                <p id="lightbox-user" class="font-semibold"></p>
                <p id="lightbox-date" class="text-sm text-gray-300"></p>
                <p id="lightbox-caption" class="mt-2 text-sm italic"></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete photo functionality
            document.querySelectorAll('.delete-photo-btn').forEach(button => {
                button.addEventListener('click', function() {
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
                                            '.relative.group');
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



            // Modal functionality
            const modal = document.getElementById('uploadModal');
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });

            // Escape key to close modal
            window.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                }
            });

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

            // Photo Lightbox Functionality
            const lightbox = document.getElementById('photo-lightbox');
            const lightboxImage = document.getElementById('lightbox-image');
            const lightboxUser = document.getElementById('lightbox-user');
            const lightboxDate = document.getElementById('lightbox-date');
            const lightboxCaption = document.getElementById('lightbox-caption');
            const closeBtn = document.getElementById('close-lightbox');
            const prevBtn = document.getElementById('prev-photo');
            const nextBtn = document.getElementById('next-photo');

            let currentPhotoIndex = 0;
            let photos = [];

            // Collect all photos data
            document.querySelectorAll('.photo-item').forEach((item, index) => {
                photos.push({
                    id: item.dataset.photoId,
                    url: item.dataset.photoUrl,
                    caption: item.dataset.photoCaption,
                    user: item.dataset.photoUser,
                    date: item.dataset.photoDate,
                    element: item
                });

                // Add click handler to open lightbox
                item.addEventListener('click', function(e) {
                    // Don't open lightbox if clicking on action buttons
                    if (e.target.closest('button') || e.target.closest('form')) {
                        return;
                    }

                    currentPhotoIndex = index;
                    openLightbox();
                });
            });

            function openLightbox() {
                if (photos.length === 0) return;

                const photo = photos[currentPhotoIndex];
                lightboxImage.src = photo.url;
                lightboxUser.textContent = photo.user;
                lightboxDate.textContent = photo.date;

                // Add quotes around caption if it exists
                if (photo.caption) {
                    lightboxCaption.textContent = `"${photo.caption}"`;
                    lightboxCaption.style.display = 'block';
                } else {
                    lightboxCaption.textContent = '';
                    lightboxCaption.style.display = 'none';
                }

                // Show/hide navigation buttons
                prevBtn.style.display = photos.length > 1 ? 'flex' : 'none';
                nextBtn.style.display = photos.length > 1 ? 'flex' : 'none';

                lightbox.style.display = 'flex';
                lightbox.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }

            function closeLightbox() {
                lightbox.style.display = 'none';
                lightbox.classList.add('hidden');
                document.body.style.overflow = ''; // Restore scrolling
            }

            function showPrevPhoto() {
                if (photos.length > 1) {
                    currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
                    openLightbox();
                }
            }

            function showNextPhoto() {
                if (photos.length > 1) {
                    currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
                    openLightbox();
                }
            }

            // Event listeners
            closeBtn.addEventListener('click', closeLightbox);
            prevBtn.addEventListener('click', showPrevPhoto);
            nextBtn.addEventListener('click', showNextPhoto);

            // Close on background click
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (lightbox.style.display === 'flex') {
                    switch (e.key) {
                        case 'Escape':
                            closeLightbox();
                            break;
                        case 'ArrowLeft':
                            showPrevPhoto();
                            break;
                        case 'ArrowRight':
                            showNextPhoto();
                            break;
                    }
                }
            });
        });
    </script>
</x-layout>
