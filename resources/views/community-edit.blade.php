<x-layout title="Edit Community - Gatherly">
    @php
        $slug = request('community');
        $community = \App\Models\Community::where('slug', $slug)->firstOrFail();
    @endphp

    <div class="max-w-2xl mx-auto bg-white shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Edit {{ $community->name }}</h2>

        <form id="edit-community-form" method="POST" action="{{ url("/communities/{$community->slug}") }}"
            enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <!-- Name -->
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Name</label>
                <input name="name" class="w-full border p-2" value="{{ $community->name }}" required />
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border p-2">{{ $community->description }}</textarea>
            </div>

            <!-- Banner Image -->
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Banner Image</label>
                <img id="banner-preview" src="{{ $community->banner_image ? asset($community->banner_image) : '' }}"
                    alt="Banner Preview" class="w-full h-40 object-cover mb-2">
                <input id="banner-input" type="file" name="banner_image" accept="image/*" class="w-full p-2 border">
            </div>

            <!-- Visibility -->
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Visibility</label>
                <select name="visibility" class="w-full border p-2">
                    <option value="public" {{ $community->visibility == 'public' ? 'selected' : '' }}>Public</option>
                    <option value="private" {{ $community->visibility == 'private' ? 'selected' : '' }}>Private</option>
                    <option value="hidden" {{ $community->visibility == 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>

            <!-- Join Policy -->
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Join Policy</label>
                <select name="join_policy" class="w-full border p-2">
                    <option value="open" {{ $community->join_policy == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="request" {{ $community->join_policy == 'request' ? 'selected' : '' }}>Request
                    </option>
                    <option value="invite" {{ $community->join_policy == 'invite' ? 'selected' : '' }}>Invite Only
                    </option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end mt-4 gap-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-1 hover:bg-blue-600">
                    Save Changes
                </button>
                <a href="{{ url('/dashboard?community=' . $community->slug) }}"
                    class="bg-gray-300 text-gray-800 px-2 py-1 hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('edit-community-form');
            const bannerInput = document.getElementById('banner-input');
            const bannerPreview = document.getElementById('banner-preview');

            // Live banner preview
            bannerInput?.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        bannerPreview.src = event.target.result; // Update preview
                    };
                    reader.readAsDataURL(file);
                } else {
                    bannerPreview.src =
                        "{{ $community->banner_image ? asset($community->banner_image) : '' }}"; // fallback
                }
            });

            // Form submit
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const data = new FormData(form);

                try {
                    const res = await fetch(form.action, {
                        method: 'POST', // Laravel PATCH via _method
                        headers: {
                            'X-CSRF-TOKEN': data.get('_token'),
                            'Accept': 'application/json'
                        },
                        body: data
                    });

                    if (res.ok) {
                        const json = await res.json();
                        // redirect after success
                        window.location.href = `/dashboard?community=${json.slug}`;
                    } else {
                        const err = await res.json().catch(() => ({}));
                        alert(err.message || 'Failed to update community.');
                    }
                } catch (error) {
                    console.error(error);
                    alert('Something went wrong.');
                }
            });
        });
    </script>

</x-layout>
