<?php

// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;

Route::middleware('guest')->group(function () {
    // 👇 redirect root to /login
    Route::redirect('/', '/login');

    // 👇 GET login is named 'login' so route('login') => /login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LogoutController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/events', fn() => view('events'))->name('events');
    Route::get('/communities', fn() => view('communities'))->name('communities');
});
