

<x-layout :title="'Upload Photo - Gatherly'" :community="$community" :communities="$communities">
    <div class="max-w-2xl mx-auto py-8">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Upload Photo to {{ $community?->name }}</h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>Choose a photo to share with your community. Maximum file size is 5MB.</p>
                </div>

                <form action="{{ route('photos.store', ['community' => $community?->slug]) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-6">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Photo</label>
                        <div class="mt-1 flex items-center">
                            <input type="file" name="photo" accept="image/*" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        @error('photo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Caption (optional)</label>
                        <div class="mt-1">
                            <textarea name="caption" rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Add a caption to your photo..."></textarea>
                        </div>
                        @error('caption')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ url()->previous() }}" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Upload Photo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.addEventListener('change', function(event) {
                    if (event.target.files && event.target.files[0]) {
                        if (event.target.files[0].size > 5 * 1024 * 1024) {
                            alert('File size must be less than 5MB');
                            event.target.value = '';
                        }
                    }
                });
            }
        });
    </script>
</x-layout>