@php
    use App\Models\Community;

    // Load communities for sidebar
    $communities = collect();
    if (auth()->check()) {
        $communities = Community::whereHas('memberships', function ($q) {
            $q->where('user_id', auth()->id());
        })->get();
    }
@endphp

<x-layout :title="'Welcome - Gatherly'" :community="null" :communities="$communities">
    <x-community-welcome />
</x-layout>
