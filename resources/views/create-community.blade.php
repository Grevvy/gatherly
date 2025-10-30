@php
    use App\Models\Community;

    // Load communities the current user belongs to (for sidebar)
    $communities = auth()->check()
        ? Community::whereHas('memberships', fn ($q) => $q->where('user_id', auth()->id()))->get()
        : collect();

    $availableTags = config('tags.list', []);
@endphp

<x-layout :title="'Create Community - Gatherly'" :communities="$communities">
    <div class="w-full bg-white shadow-lg p-6 mt-2 px-4 lg:px-8 rounded-2xl">
        <form id="create-community-form" method="POST" action="/communities" enctype="multipart/form-data">
            @csrf

            <!-- Name -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Community Name</span>
                <input name="name" class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl" required />
            </div>

            <!-- Description -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Community
                    Description</span>
                <textarea name="description" rows="3" class="w-full border p-2 pt-6 text-gray-800 bg-transparent rounded-xl "></textarea>
            </div>

            <!-- Banner Image -->
            <div class="relative mb-4">
                <img id="banner-preview" src="" alt="Banner Preview"
                    class="w-full h-40 object-cover  mb-2 hidden" />
                <input id="banner-input" type="file" name="banner_image" accept="image/*"
                    class="w-full p-2 pt-6 border text-gray-800 bg-transparent rounded-xl" />
            </div>

            <!-- Visibility -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Select
                    Visibility</span>
                <select name="visibility"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none rounded-xl" required>
                    <option value="" disabled selected></option>
                    <option value="public">Public</option>
                    <option value="private">Private</option>

                </select>
            </div>


            <!-- Join Policy -->
            <div class="relative mb-4">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">Select Join
                    Policy</span>
                <select name="join_policy"
                    class="w-full border p-2 pt-6 text-gray-800 bg-transparent appearance-none rounded-xl" required>
                    <option value="" disabled selected></option>
                    <option value="open">Open</option>
                    <option value="request">Request</option>
                    <option value="invite">Invite Only</option>
                </select>
            </div>
            <!-- Tags -->
            <div class="relative mb-6">
                <span class="absolute top-2 left-3 text-sm text-gray-400 pointer-events-none z-10">
                    Select Tags
                </span>
                <div class="border rounded-xl bg-white pt-10 pb-4 px-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($availableTags as $tag)
                            <label class="group cursor-pointer">
                                <input type="checkbox" name="tags[]" value="{{ strtolower($tag) }}"
                                    class="hidden peer">
                                <div
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-all
                                        peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-500
                                        peer-checked:text-white peer-checked:border-transparent peer-checked:shadow-md
                                        group-hover:border-blue-300 group-hover:shadow-sm">
                                    {{ $tag }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Select all topics that match this community.
                </p>
            </div>
            <div class="flex justify-between mt-8 pt-4 border-t border-gray-200">
                <a href="{{ route('dashboard') }}" class="text-gray-600 underline text-sm">Cancel</a>
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                    Create a New Community
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('create-community-form');
            const bannerInput = document.getElementById('banner-input');
            const bannerPreview = document.getElementById('banner-preview');

            // Live banner preview
            bannerInput?.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        bannerPreview.src = event.target.result;
                        bannerPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    bannerPreview.src = '';
                    bannerPreview.classList.add('hidden');
                }
            });

            // Form submit
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const data = new FormData(form);

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': data.get('_token'),
                            'Accept': 'application/json'
                        },
                        body: data
                    });

                    if (res.ok) {
                        const json = await res.json();
                        window.location.href = `/dashboard?community=${json.slug}`;
                    } else {
                        const err = await res.json().catch(() => ({}));
                        showToastify(err.message || 'Failed to create community.', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showToastify('Something went wrong.', 'error');
                }
            });

        });
    </script>
</x-layout>
