@php
    use App\Models\Community;
    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
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

    <div class="flex flex-col items-center justify-center min-h-[80vh] bg-gradient-to-br from-white via-gray-50 to-white py-12">
        <div class="bg-white/70 backdrop-blur-xl border border-gray-200 shadow-[0_8px_30px_rgba(0,0,0,0.05)] rounded-3xl w-full max-w-3xl p-10 text-center">
            
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 mb-4">
                Welcome to Gatherly 👋
            </h1>
            <p class="text-gray-600 mb-10 text-base max-w-lg mx-auto">
                Let’s personalize your experience! Select a few topics you’re into so we can recommend communities you’ll love.
            </p>

            <form method="POST" action="{{ route('onboarding.save') }}" class="space-y-8">
                @csrf

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @php
                        $tags = ['Music', 'Tech', 'Art', 'Gaming', 'Fitness', 'Food', 'Travel', 'Movies', 'Fashion', 'Sports', 'Education', 'Health'];
                    @endphp

                    @foreach ($tags as $tag)
                        <label class="relative group cursor-pointer">
                            <input type="checkbox" name="interests[]" value="{{ strtolower($tag) }}" class="hidden peer">
                            <div class="p-4 border border-gray-200 rounded-xl text-sm font-medium transition-all 
                                        peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-500 
                                        peer-checked:text-white peer-checked:shadow-lg hover:scale-[1.03] hover:border-blue-300">
                                {{ $tag }}
                            </div>
                        </label>
                    @endforeach
                </div>

                <button type="submit"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-8 py-3 rounded-xl 
                           shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 font-semibold">
                    Continue →
                </button>
            </form>
        </div>
    </div>
</body>
</html>

