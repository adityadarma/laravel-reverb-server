<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('login page renders for guests', function () {
    $this->get(route('login'))->assertOk();
});

test('login page redirects authenticated users to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertAuthenticated();
});

test('login fails with wrong password', function () {
    $user = User::factory()->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('login is rate limited after 5 attempts', function () {
    $user = User::factory()->create();

    // Clear any existing rate limiter state
    RateLimiter::clear(
        Str::transliterate(
            Str::lower($user->email).'|127.0.0.1'
        )
    );

    // Make 5 failed attempts to trigger the rate limiter
    for ($i = 0; $i < 5; $i++) {
        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');
    }

    // The 6th attempt should be rate limited
    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('authenticated user can logout via POST /logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
