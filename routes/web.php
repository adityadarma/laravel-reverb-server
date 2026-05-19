<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

// Guest only
Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('logout', LogoutController::class)->name('logout');
    Route::livewire('/', 'pages::dashboard')->name('dashboard');

    Route::livewire('apps', 'pages::apps.index')->name('apps.index');
    Route::livewire('apps/{id}', 'pages::apps.show')->name('apps.show');
    Route::livewire('audit-logs', 'pages::audit-logs')->name('audit-logs');

    Route::redirect('settings', 'settings/profile');
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});
