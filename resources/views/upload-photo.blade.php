<!-- CropperJS CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet" />

<x-layout :title="'Upload Photo - Gatherly'" :community="$community" :communities="$communities">
    <div class="max-w-2xl mx-auto py-8">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Upload Photo to {{ $community?->name }}</h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>Choose a photo to share with your community. Maximum file size is 5MB.</p>
                </div>

                <form action="{{ route('photos.store', ['community' => $community?->slug]) }}" method="POST"
                    enctype="multipart/form-data" class="mt-5 space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Photo</label>
                        <div class="mt-1">
                            <input type="file" name="photo" id="photo-input" accept="image/*" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Image Preview Container -->
                        <div id="image-preview-container" class="mt-4 hidden">
                            <div class="relative inline-block">
                                <img id="image-preview"
                                    class="max-w-full max-h-64 rounded-lg border border-gray-300 shadow-sm"
                                    alt="Image preview">
                                <button type="button" id="crop-button" title="Crop image"
                                    class="absolute top-2 left-2 bg-white text-blue-600 border border-blue-200 w-8 h-8 flex items-center justify-center shadow hover:bg-blue-50 transition rounded-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 2v14a2 2 0 0 0 2 2h14" />
                                        <path d="M2 6h14a2 2 0 0 1 2 2v14" />
                                    </svg>
                                </button>
                                <button type="button" id="remove-preview" title="Remove image"
                                    class="absolute top-2 right-2 bg-white text-red-600 border border-red-200 w-8 h-8 flex items-center justify-center shadow hover:bg-red-50 transition rounded-full">
                                    ×
                                </button>
                            </div>

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

                    <div class="flex justify-end items-center gap-1.5 mt-4">
                        <a href="{{ url()->previous() }}" class="text-gray-600 underline text-sm px-3 py-2">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-all duration-300">
                            Upload Photo
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- CropperJS JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('photo-input');
            const previewContainer = document.getElementById('image-preview-container');
            const previewImage = document.getElementById('image-preview');
            const removeButton = document.getElementById('remove-preview');
            const cropButton = document.getElementById('crop-button');

            let cropper = null;
            let originalImageSrc = null;
            let currentFile = null;
            let lastCropBoxData = null;
            let lastCroppedImageUrl = null;

            if (fileInput) {
                fileInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];

                    if (file) {
                        // Check file size
                        if (file.size > 5 * 1024 * 1024) {
                            alert('File size must be less than 5MB');
                            event.target.value = '';
                            hidePreview();
                            return;
                        }

                        // Check file type
                        if (!file.type.startsWith('image/')) {
                            alert('Please select a valid image file');
                            event.target.value = '';
                            hidePreview();
                            return;
                        }

                        currentFile = file;

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            originalImageSrc = e.target.result;
                            previewImage.src = originalImageSrc;
                            showPreview();
                        };
                        reader.readAsDataURL(file);
                    } else {
                        hidePreview();
                    }
                });
            }

            // Crop functionality
            if (cropButton) {
                cropButton.addEventListener('click', function() {
                    startCrop();
                });
            }

            // Remove preview functionality
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    fileInput.value = '';
                    hidePreview();
                    destroyCropper();
                });
            }

            function showPreview() {
                if (previewContainer) {
                    previewContainer.classList.remove('hidden');
                }
            }

            function hidePreview() {
                if (previewContainer) {
                    previewContainer.classList.add('hidden');
                }
                if (previewImage) {
                    previewImage.src = '';
                }
                destroyCropper();
                resetCropState();
            }

            function startCrop() {
                if (!previewImage || !originalImageSrc) return;

                // Always start from original image
                previewImage.src = originalImageSrc;

                if (cropper) cropper.destroy();

                cropper = new Cropper(previewImage, {
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

                // Add Apply and Cancel buttons
                showCropControls();
            }

            function showCropControls() {
                // Remove existing controls if any
                removeCropControls();

                const img = previewImage;

                const applyBtn = document.createElement('button');
                applyBtn.id = 'apply-crop-btn';
                applyBtn.textContent = 'Crop';
                applyBtn.className =
                    'absolute bottom-2 right-2 bg-blue-600 text-white px-3 py-1 rounded text-sm shadow-md hover:bg-blue-700';
                applyBtn.type = 'button';
                applyBtn.onclick = () => {
                    applyCrop();
                };
                img.parentElement.appendChild(applyBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.id = 'cancel-crop-btn';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.className =
                    'absolute bottom-2 right-20 bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm shadow-md hover:bg-gray-400';
                cancelBtn.type = 'button';
                cancelBtn.onclick = () => {
                    cancelCrop();
                };
                img.parentElement.appendChild(cancelBtn);
            }

            function removeCropControls() {
                const applyBtn = document.getElementById('apply-crop-btn');
                const cancelBtn = document.getElementById('cancel-crop-btn');
                if (applyBtn) applyBtn.remove();
                if (cancelBtn) cancelBtn.remove();
            }

            function applyCrop() {
                if (!cropper) return;

                lastCropBoxData = cropper.getData();

                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 1200,
                    maxHeight: 1200
                });

                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    lastCroppedImageUrl = url;

                    previewImage.src = url;

                    destroyCropper();
                    removeCropControls();

                    // Update the file input with cropped image
                    const dt = new DataTransfer();
                    const croppedFile = new File([blob], currentFile?.name || 'cropped-image.png', {
                        type: 'image/png'
                    });
                    dt.items.add(croppedFile);
                    fileInput.files = dt.files;
                }, 'image/png');
            }

            function cancelCrop() {
                if (cropper) {
                    destroyCropper();
                }
                // Restore image to last cropped version or original
                previewImage.src = lastCroppedImageUrl || originalImageSrc;
                removeCropControls();
            }

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            function resetCropState() {
                originalImageSrc = null;
                lastCropBoxData = null;
                lastCroppedImageUrl = null;
                currentFile = null;
            }
        });
    </script>
</x-layout>
