<?php

use App\Services\ReverbMetricsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new ReverbMetricsService;
});

// ── connections() ─────────────────────────────────────────────────────────────

test('connections returns 0 when Reverb server is unreachable', function () {
    $app = createApp();

    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $result = $this->service->connections($app);

    expect($result)->toBe(0);
});

test('connections returns count from API response', function () {
    $app = createApp();

    Http::fake([
        '*' => Http::response(['connections' => 5], 200),
    ]);

    $result = $this->service->connections($app);

    expect($result)->toBe(5);
});

test('connections returns 0 when API response is not successful', function () {
    $app = createApp();

    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $result = $this->service->connections($app);

    expect($result)->toBe(0);
});

// ── channels() ────────────────────────────────────────────────────────────────

test('channels returns empty array when server is unreachable', function () {
    $app = createApp();

    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $result = $this->service->channels($app);

    expect($result)->toBe([]);
});

test('channels returns channels from API response', function () {
    $app = createApp();

    Http::fake([
        '*' => Http::response([
            'channels' => [
                'my-channel' => ['occupied' => true],
                'private-channel' => ['occupied' => true],
            ],
        ], 200),
    ]);

    $result = $this->service->channels($app);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('my-channel');
    expect($result)->toHaveKey('private-channel');
});

// ── totalConnections() ────────────────────────────────────────────────────────

test('totalConnections sums connections across all active apps', function () {
    createApp(['name' => 'App 1', 'is_active' => true]);
    createApp(['name' => 'App 2', 'is_active' => true]);
    createApp(['name' => 'Inactive App', 'is_active' => false]);

    Http::fake([
        '*' => Http::response(['connections' => 3], 200),
    ]);

    $total = $this->service->totalConnections();

    // 2 active apps × 3 connections each = 6
    expect($total)->toBe(6);
});

test('totalConnections returns 0 when no active apps exist', function () {
    createApp(['is_active' => false]);

    $total = $this->service->totalConnections();

    expect($total)->toBe(0);
});

// ── channelsWithInfo() ────────────────────────────────────────────────────────

test('channelsWithInfo returns empty array when channels is empty', function () {
    $app = createApp();

    Http::fake([
        '*' => Http::response(['channels' => []], 200),
    ]);

    $result = $this->service->channelsWithInfo($app);

    expect($result)->toBe([]);
});

test('channelsWithInfo merges channel list with detail info', function () {
    $app = createApp();

    Http::fake(function (Request $request) use ($app) {
        $url = $request->url();

        // Channel list endpoint
        if (str_contains($url, "/apps/{$app->id}/channels") && ! str_contains($url, '/channels/')) {
            return Http::response([
                'channels' => [
                    'my-channel' => ['occupied' => true],
                ],
            ], 200);
        }

        // Channel detail endpoint
        if (str_contains($url, '/channels/my-channel')) {
            return Http::response([
                'subscription_count' => 4,
            ], 200);
        }

        return Http::response([], 404);
    });

    $result = $this->service->channelsWithInfo($app);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('my-channel');
    expect($result['my-channel'])->toHaveKey('subscription_count');
    expect($result['my-channel']['subscription_count'])->toBe(4);
    expect($result['my-channel']['occupied'])->toBeTrue();
});
