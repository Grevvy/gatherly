@php
    use App\Models\Community;

    $slug = request('community');
    $community = $slug
        ? Community::with(['owner', 'memberships.user'])
            ->where('slug', $slug)
            ->first()
        : null;

    $communities = auth()->check()
        ? Community::whereHas('memberships', fn($q) => $q->where('user_id', auth()->id()))->get()
        : collect();
@endphp

<x-layout :title="'Dashboard - Gatherly'" :community="$community" :communities="$communities">
    <div class="space-y-8">

        <!-- 📸 Photo Grid -->
        <div id="photoGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($photos ?? [] as $photo)
                <div
                    class="relative group w-full max-w-[40rem] h-60 overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white transition-transform transform duration-300 ease-out hover:scale-105 hover:z-50 hover:shadow-2xl cursor-pointer">

                    <img src="{{ $photo->image_path ?? 'https://via.placeholder.com/400x300?text=Photo' }}"
                        alt="Community photo"
                        class="w-full h-full object-cover transition-transform duration-300 ease-out">

                    <!-- Overlay info -->
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                        <div class="p-4 text-white text-sm w-full">
                            <p class="font-semibold truncate">{{ $photo->user->name ?? 'Anonymous' }}</p>
                            <p class="text-xs text-gray-200">{{ $photo->created_at?->diffForHumans() ?? 'Just now' }}
                            </p>
                            @if (!empty($photo->caption))
                                <p class="mt-1 text-xs italic truncate">“{{ $photo->caption }}”</p>
                            @endif
                        </div>
                    </div>
                </div>

            @empty
                @for ($i = 1; $i <= 6; $i++)
                    <div
                        class="relative group w-full max-w-[40rem] h-60 overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white transition-transform transform duration-300 ease-out hover:scale-105 hover:z-50 hover:shadow-2xl cursor-pointer">

                        <img src="https://source.unsplash.com/random/400x30{{ $i }}?community,nature"
                            alt="Placeholder photo"
                            class="w-full h-full object-cover transition-transform duration-300 ease-out">

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                            <div class="p-4 text-white text-sm w-full">
                                <p class="font-semibold truncate">User {{ $i }}</p>
                                <p class="text-xs text-gray-200">{{ now()->subDays($i)->diffForHumans() }}</p>
                                <p class="mt-1 text-xs italic truncate">“Sample caption for photo {{ $i }}”
                                </p>
                            </div>
                        </div>
                    </div>
                @endfor
            @endforelse
        </div>

    </div>
</x-layout>
