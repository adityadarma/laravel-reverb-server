<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Guest only
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::view('/', 'dashboard')->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});
