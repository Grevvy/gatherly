@php
    use App\Models\Community;

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

    $tags = config('tags.list', []);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Gatherly 🎉</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-white via-gray-50 to-white flex items-center justify-center">

    <div
        class="flex flex-col items-center justify-center min-h-[80vh] bg-gradient-to-br from-white via-gray-50 to-white py-12">
        <div
            class="bg-white/70 backdrop-blur-xl border border-gray-200 shadow-[0_8px_30px_rgba(0,0,0,0.05)] rounded-3xl w-full max-w-3xl p-10 text-center relative">

            <h1
                class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 mb-4">
                Welcome to Gatherly 👋
            </h1>
            <p class="text-gray-600 mb-10 text-base max-w-lg mx-auto">
                Let’s personalize your experience! Select a few topics you’re into so we can recommend communities
                you’ll love.
            </p>

            <form method="POST" action="{{ route('onboarding.save') }}" class="space-y-8">
                @csrf

                <!-- Tags Grid -->
                <div class="space-y-6">
                    <div id="tagsContainer"
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 
               min-h-[220px] w-full min-w-[700px] max-w-[720px] mx-auto 
               place-content-start transition-all duration-300">
                        @foreach ($tags as $tag)
                            <label class="relative group cursor-pointer tag-item hidden">
                                <input type="checkbox" name="interests[]" value="{{ strtolower($tag) }}"
                                    class="hidden peer">
                                <div
                                    class="p-4 border border-gray-200 rounded-xl text-sm font-medium transition-all 
                           peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-500 
                           peer-checked:text-white peer-checked:shadow-lg hover:scale-[1.03] hover:border-blue-300">
                                    {{ $tag }}
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center justify-center space-x-4">
                        <button type="button" id="prevBtn"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium 
                   hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed">
                            ← Back
                        </button>
                        <span id="pageIndicator" class="text-sm text-gray-500"></span>
                        <button type="button" id="nextBtn"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium 
                   hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed">
                            Next →
                        </button>
                    </div>
                </div>


                <!-- Continue Button -->
                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-8 py-3 rounded-xl 
                           shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 font-semibold">
                    Continue →
                </button>
            </form>

            <!-- Logout Button -->
            <div class="absolute top-6 right-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-sm text-gray-500 hover:text-red-600 transition-all underline underline-offset-2">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const itemsPerPage = 12;
            const tags = document.querySelectorAll('.tag-item');
            const totalPages = Math.ceil(tags.length / itemsPerPage);
            let currentPage = 1;

            const pageIndicator = document.getElementById('pageIndicator');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            function showPage(page) {
                tags.forEach((tag, i) => {
                    const start = (page - 1) * itemsPerPage;
                    const end = page * itemsPerPage;
                    tag.classList.toggle('hidden', i < start || i >= end);
                });

                pageIndicator.textContent = `Page ${page} of ${totalPages}`;
                prevBtn.disabled = page === 1;
                nextBtn.disabled = page === totalPages;
            }

            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    showPage(currentPage);
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    showPage(currentPage);
                }
            });

            showPage(currentPage);
        });
    </script>
</body>

</html>
