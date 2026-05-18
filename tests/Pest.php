<?php

use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createApp(array $overrides = []): App
{
    return App::create(array_merge([
        'name' => 'Test App',
        'key' => Str::random(20),
        'secret' => Str::random(40),
        'host' => 'localhost',
        'port' => 443,
        'scheme' => 'https',
        'allowed_origins' => ['*'],
        'ping_interval' => 60,
        'activity_timeout' => 30,
        'max_connections' => null,
        'max_message_size' => 10000,
        'accept_client_events_from' => 'members',
        'rate_limiting_enabled' => false,
        'rate_limit_max_attempts' => 60,
        'rate_limit_decay_seconds' => 60,
        'rate_limit_terminate_on_limit' => false,
        'is_active' => true,
    ], $overrides));
}
