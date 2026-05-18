<?php

use App\Models\User;
use Livewire\Livewire;

test('guest is redirected to login', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('authenticated user can visit profile settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('user can update their name and email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

test('email must be unique excluding current user', function () {
    $existingUser = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'Some Name')
        ->set('email', 'taken@example.com')
        ->call('updateProfileInformation')
        ->assertHasErrors(['email']);
});

test('user can keep their own email without validation error', function () {
    $user = User::factory()->create(['email' => 'myemail@example.com']);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('name', 'New Name')
        ->set('email', 'myemail@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();
});
