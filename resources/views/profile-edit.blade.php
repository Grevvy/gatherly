<x-layout :communities="$communities">
    <div class="bg-gradient-to-b from-white to-gray-50/40 min-h-screen">
        <div class="max-w-3xl mx-auto">

            <!-- Back -->
            <a href="{{ route('profile.show') }}"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800 underline transition mb-6">
                ← Back to Profile
            </a>

            <!-- Card -->
            <div class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-lg border border-sky-100 p-8">
                <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">
                    Edit Your Profile
                </h2>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Avatar Upload -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative">
                            @if (Auth::user()->avatar)
                                <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="Avatar"
                                    class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                            @else
                                <div
                                    class="w-24 h-24 rounded-full border-4 border-white shadow-lg flex items-center justify-center bg-gradient-to-br from-sky-300 to-indigo-300 text-white text-3xl font-bold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <label for="avatar"
                                class="absolute bottom-0 right-0 bg-sky-500 hover:bg-sky-600 text-white text-xs px-2 py-1 rounded-full cursor-pointer shadow-sm transition">
                                Change
                            </label>
                        </div>
                        <input id="avatar" type="file" name="avatar" accept="image/*" class="hidden">
                        @error('avatar')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input Fields -->
                    @foreach ([['id' => 'name', 'label' => 'Full Name', 'type' => 'text', 'value' => old('name', Auth::user()->name)], ['id' => 'username', 'label' => 'Username', 'type' => 'text', 'value' => old('username', Auth::user()->username)], ['id' => 'phone', 'label' => 'Phone Number', 'type' => 'tel', 'value' => old('phone', Auth::user()->phone)], ['id' => 'location', 'label' => 'Location', 'type' => 'text', 'value' => old('location', Auth::user()->location)], ['id' => 'website', 'label' => 'Website', 'type' => 'url', 'value' => old('website', Auth::user()->website)]] as $field)
                        <div class="relative">
                            <input type="{{ $field['type'] }}" name="{{ $field['id'] }}" id="{{ $field['id'] }}"
                                value="{{ $field['value'] }}"
                                class="peer w-full rounded-xl border border-gray-300 bg-white/70 px-4 pt-6 pb-2 text-sm shadow-sm focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:outline-none transition">
                            <label for="{{ $field['id'] }}"
                                class="absolute left-4 top-2.5 text-xs text-gray-500 peer-focus:text-sky-500 transition">
                                {{ $field['label'] }}
                            </label>
                            @error($field['id'])
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <!-- Bio -->
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                        <textarea name="bio" id="bio" rows="4"
                            class="w-full rounded-xl border border-gray-300 bg-white/70 px-4 py-2 text-sm shadow-sm focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:outline-none transition">{{ old('bio', Auth::user()->bio) }}</textarea>
                        @error('bio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Button -->
                    <div class="flex justify-center">
                        <button type="submit"
                            class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Avatar preview functionality
        document.getElementById('avatar')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.w-24.h-24');
                    if (preview) {
                        // If it's an img tag, update src
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } 
                        // If it's the div with initials, replace it with an img
                        else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'w-24 h-24 rounded-full object-cover border-4 border-white shadow-md';
                            img.alt = 'Avatar Preview';
                            preview.parentNode.replaceChild(img, preview);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</x-layout>
