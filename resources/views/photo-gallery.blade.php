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
@endphp

<x-layout :title="'Dashboard - Gatherly'" :community="$community" :communities="$communities">
    <div class="space-y-8">
        <!-- Header Section -->
        @if($community)
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Photo Gallery</h2>
                <a href="{{ route('photos.create', ['community' => $community->slug]) }}" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                <div
                    class="relative group w-full max-w-[40rem] h-60 overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white transition-transform transform duration-300 ease-out hover:scale-105 hover:z-50 hover:shadow-2xl cursor-pointer">

                    <img src="{{ $photo->image_url ?? 'https://via.placeholder.com/400x300?text=Photo' }}"
                        alt="Community photo"
                        class="w-full h-full object-cover transition-transform duration-300 ease-out">

                    <!-- Overlay info -->
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                        <div class="p-4 text-white text-sm w-full">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-semibold truncate">{{ $photo->user->name ?? 'Anonymous' }}</p>
                                    <p class="text-xs text-gray-200">{{ $photo->created_at?->diffForHumans() ?? 'Just now' }}</p>
                                    @if (!empty($photo->caption))
                                        <p class="mt-1 text-xs italic truncate">"{{ $photo->caption }}"</p>
                                    @endif
                                </div>
                                <form id="csrf-form" class="hidden">
                                    @csrf
                                </form>
                                @auth
                                    <div class="flex gap-2">
                                        @if($photo->isPending() && $isModeratorOrOwner)
                                            <form action="{{ route('photos.approve', $photo) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white rounded px-2 py-1 text-xs">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('photos.reject', $photo) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to reject this photo? This will delete it permanently.')">
                                                @csrf
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white rounded px-2 py-1 text-xs">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if(($photo->user_id === auth()->user()->id && $photo->isPending()) || ($isModeratorOrOwner && $photo->isApproved()))
                                            <button type="button" 
                                                data-photo-id="{{ $photo->id }}"
                                                class="delete-photo-btn bg-gray-500 hover:bg-gray-600 text-white rounded px-2 py-1 text-xs">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                @endauth
                            </div>
                            @if($photo->isPending())
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
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
                                        'X-CSRF-TOKEN': document.querySelector('#csrf-form input[name="_token"]').value,
                                        'Accept': 'application/json'
                                    }
                                });

                                if (res.ok) {
                                    const photoCard = document.querySelector(`[data-photo-id="${photoId}"]`).closest('.relative.group');
                                    photoCard.remove();
                                    showToastify('Photo deleted successfully', 'success');
                                } else {
                                    const data = await res.json().catch(() => ({}));
                                    showToastify(data.message || 'Failed to delete photo', 'error');
                                }
                            } catch (err) {
                                console.error(err);
                                showToastify('Something went wrong deleting the photo', 'error');
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
        });
    </script>
</x-layout>
