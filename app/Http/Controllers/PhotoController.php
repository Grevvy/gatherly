<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class PhotoController extends Controller
{
    /**
     * Display the photo gallery for a community
     */
    public function index(Request $request)
    {
        $community = null;
        $photos = collect();

        if ($slug = $request->query('community')) {
            $community = Community::where('slug', $slug)->firstOrFail();
            $photos = $community->photos()
                ->with(['user'])
                ->latest()
                ->paginate(12);
        }

        return view('photo-gallery', compact('community', 'photos'));
    }

    /**
     * Store a new photo
     */
    public function store(Request $request)
    {
        $community = Community::where('slug', $request->query('community'))->firstOrFail();
        
        // Check if user can upload photos to this community
        Gate::authorize('upload-photo', $community);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5MB max
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('photo')->store('community-photos', 'public');

        $photo = new Photo([
            'image_path' => $path,
            'caption' => $validated['caption'],
            'user_id' => $request->user()->id,
            'community_id' => $community->id,
        ]);

        $photo->save();

        return back()->with('success', 'Photo uploaded successfully!');
    }

    /**
     * Delete a photo
     */
    public function destroy(Photo $photo)
    {
        // Check if user can delete this photo
        Gate::authorize('delete', $photo);

        // Delete the file from storage
        Storage::disk('public')->delete($photo->image_path);

        // Delete the database record
        $photo->delete();

        return back()->with('success', 'Photo deleted successfully!');
    }
}