<!-- Photo Modal -->
<div id="photoModal" class="fixed inset-0 bg-black/90 z-50 hidden" aria-modal="true">
    <!-- Close button -->
    <button id="closePhotoModal" class="absolute top-4 right-4 text-white hover:text-gray-300 z-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Previous button -->
    <button id="prevPhoto" class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    <!-- Next button -->
    <button id="nextPhoto" class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>

    <!-- Photo container -->
    <div class="flex items-center justify-center h-full">
        <img id="modalPhoto" src="" alt="Full size photo" class="max-h-[90vh] max-w-[90vw] object-contain">
    </div>

    <!-- Photo info -->
    <div id="modalPhotoInfo" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent text-white p-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-start justify-between">
                <div>
                    <p id="modalPhotoUser" class="font-semibold text-lg"></p>
                    <p id="modalPhotoTime" class="text-sm text-gray-300"></p>
                    <p id="modalPhotoCaption" class="mt-2 text-sm italic"></p>
                </div>
                <div id="modalPhotoActions" class="flex gap-2">
                    <!-- Action buttons will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>