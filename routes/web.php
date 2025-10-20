<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityMembershipController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageThreadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;

// routes/web.php
// ------------------
// Guest routes
// ------------------
Route::middleware('guest')->group(function () {
    // Redirect root to /login
    Route::redirect('/', '/login');

    // Login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Register
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// ------------------
// Logout
// ------------------
Route::post('/logout', [LogoutController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ------------------
// Authenticated routes
// ------------------
Route::middleware('auth')->group(function () {
    // Dashboard + Events
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/events', fn() => view('events'))->name('events');
    
    // Messages & Channels
    Route::get('/messages', function () {
        return view('messages');
    })->name('messages');
    
    Route::post('/channels/{community}', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show');
    Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');
    
    Route::post('/threads/{community}', [MessageThreadController::class, 'store'])->name('threads.store');
    Route::get('/threads/{thread}', [MessageThreadController::class, 'show'])->name('threads.show');
    Route::delete('/threads/{thread}', [MessageThreadController::class, 'destroy'])->name('threads.destroy');
    
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Community routes
    Route::get('/community-edit', function () {
        return view('community-edit');
    })->name('community.edit');

    Route::get('/create-event', function () {
        return view('create-event');
    })->name(name: 'create-event');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/events/{event}/edit', function (\App\Models\Event $event) {
        return view('edit-event');
    })->name('edit-event');


    Route::get('/events/{event}/details', function () {
        return view('event-details');
    })->name('event.details');

    Route::get('/create-community', action: function () {
        return view('create-community');
    })->name('create-community');

    // Communities
    Route::get('/communities/search', [CommunityController::class, 'search']);
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::post('/communities', [CommunityController::class, 'store']);
    Route::get('/communities/{community:slug}', [CommunityController::class, 'show']);
    Route::patch('/communities/{community:slug}', [CommunityController::class, 'update']);
    Route::delete('/communities/{community:slug}', [CommunityController::class, 'destroy']);

    // Memberships + moderation
    Route::get('/members', [CommunityMembershipController::class, 'showMembers'])->name('members');
    Route::get('/communities/{community:slug}/members', [CommunityMembershipController::class, 'index']);
    Route::post('/communities/{community:slug}/join', [CommunityMembershipController::class, 'join']);
    Route::post('/communities/{community:slug}/leave', [CommunityMembershipController::class, 'leave']);
    Route::post('/communities/{community:slug}/approve', [CommunityMembershipController::class, 'approve']);
    Route::post('/communities/{community:slug}/reject', [CommunityMembershipController::class, 'reject']);
    Route::post('/communities/{community:slug}/invite', [CommunityMembershipController::class, 'invite']);
    Route::post('/communities/{community:slug}/role', [CommunityMembershipController::class, 'setRole']);
    Route::post('/communities/{community:slug}/ban', [CommunityMembershipController::class, 'ban']);
    Route::delete('/communities/{community:slug}/members/{userId}', [CommunityMembershipController::class, 'remove']);

    // Events
    Route::get('/events/list', [\App\Http\Controllers\EventController::class, 'index']);
    Route::get('/events/calendar', [\App\Http\Controllers\EventController::class, 'calendar']);
    Route::post('/events', [\App\Http\Controllers\EventController::class, 'store']);
    Route::get('/events/{event}', [\App\Http\Controllers\EventController::class, 'show']);
    Route::patch('/events/{event}', [\App\Http\Controllers\EventController::class, 'update']);
    Route::delete('/events/{event}', [\App\Http\Controllers\EventController::class, 'destroy']);

    // Approve draft events (community owner/admin/moderator, event owner/host)
    Route::post('/events/{event}/approve', [\App\Http\Controllers\EventController::class, 'approve']);

    // RSVP
    Route::post('/events/{event}/rsvp', [\App\Http\Controllers\EventController::class, 'rsvp']);
    // Check-in
    Route::post('/events/{event}/attendees/{attendee}/checkin', [\App\Http\Controllers\EventController::class, 'checkin']);

    // Posts
    Route::get('/communities/{community:slug}/posts', [PostController::class, 'index'])->name('posts.index');
    Route::post('/communities/{community:slug}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/communities/{community:slug}/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::patch('/communities/{community:slug}/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/communities/{community:slug}/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/communities/{community:slug}/posts/{post}/moderate', [PostController::class, 'moderate'])->name('posts.moderate');
    Route::get('/communities/{community:slug}/posts', [PostController::class, 'index'])->name('posts.index');
    Route::post('/communities/{community:slug}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/communities/{community:slug}/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::put('/communities/{community:slug}/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/communities/{community:slug}/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/communities/{community:slug}/posts/{post}/moderate', [PostController::class, 'moderate'])->name('posts.moderate');
});

