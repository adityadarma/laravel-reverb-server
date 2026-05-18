<?php

use App\Models\User;
use Illuminate\Support\Str;

test('user model uses UUID', function () {
    $user = User::factory()->create();

    expect($user->id)->toBeString();
    expect(Str::isUuid($user->id))->toBeTrue();
});

test('initials returns correct initials for single name', function () {
    $user = User::factory()->make(['name' => 'Alice']);

    expect($user->initials())->toBe('A');
});

test('initials returns correct initials for full name', function () {
    $user = User::factory()->make(['name' => 'John Doe']);

    expect($user->initials())->toBe('JD');
});

test('initials returns max 2 characters', function () {
    $user = User::factory()->make(['name' => 'John Michael Doe']);

    $initials = $user->initials();

    expect(strlen($initials))->toBeLessThanOrEqual(2);
    expect($initials)->toBe('JM');
});

test('initials handles names with extra spaces gracefully', function () {
    $user = User::factory()->make(['name' => 'Jane Smith']);

    expect($user->initials())->toBe('JS');
});
