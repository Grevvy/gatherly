<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityMembershipController;

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
    Route::get('/community-edit', function () {
        return view('community-edit');
    })->name('community.edit');

    // Communities
    Route::get('/communities/search', [CommunityController::class, 'search']);
    Route::get('/communities', [CommunityController::class, 'index']);
    Route::post('/communities', [CommunityController::class, 'store']);
    Route::get('/communities/{community:slug}', [CommunityController::class, 'show']);
    Route::patch('/communities/{community:slug}', [CommunityController::class, 'update']);
    Route::delete('/communities/{community:slug}', [CommunityController::class, 'destroy']);

    // Memberships + moderation
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

    // RSVP
    Route::post('/events/{event}/rsvp', [\App\Http\Controllers\EventController::class, 'rsvp']);
    // Check-in
    Route::post('/events/{event}/attendees/{attendee}/checkin', [\App\Http\Controllers\EventController::class, 'checkin']);
});

