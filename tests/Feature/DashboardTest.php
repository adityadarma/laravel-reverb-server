<?php

use App\Models\User;
use App\Services\ReverbMetricsService;

test('guest is redirected to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated user can visit dashboard', function () {
    $user = User::factory()->create();

    $this->mock(ReverbMetricsService::class, function ($mock) {
        $mock->shouldReceive('totalConnections')->andReturn(0);
        $mock->shouldReceive('connections')->andReturn(0);
        $mock->shouldReceive('channelsWithInfo')->andReturn([]);
    });

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard shows correct app counts', function () {
    $user = User::factory()->create();

    // Create 3 apps: 2 active, 1 inactive
    createApp(['name' => 'Active App 1', 'is_active' => true]);
    createApp(['name' => 'Active App 2', 'is_active' => true]);
    createApp(['name' => 'Inactive App', 'is_active' => false]);

    $this->mock(ReverbMetricsService::class, function ($mock) {
        $mock->shouldReceive('totalConnections')->andReturn(5);
        $mock->shouldReceive('connections')->andReturn(0);
        $mock->shouldReceive('channelsWithInfo')->andReturn([]);
    });

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('3')   // total apps
        ->assertSee('2');  // active apps
});
