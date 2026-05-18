<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest is redirected to login', function () {
    $app = createApp();

    $this->get(route('apps.show', $app->id))
        ->assertRedirect(route('login'));
});

test('authenticated user can visit app detail page', function () {
    $app = createApp(['name' => 'Detail Test App']);

    $this->actingAs($this->user)
        ->get(route('apps.show', $app->id))
        ->assertOk()
        ->assertSee('Detail Test App');
});

test('returns 404 for non-existent app', function () {
    $this->actingAs($this->user)
        ->get(route('apps.show', 'non-existent-uuid'))
        ->assertNotFound();
});
