<?php

use App\Models\App;
use Illuminate\Support\Str;

test('app model uses UUID', function () {
    $app = createApp();

    expect($app->id)->toBeString();
    expect(Str::isUuid($app->id))->toBeTrue();
});

test('scopeActive only returns active apps', function () {
    createApp(['name' => 'Active App',   'is_active' => true]);
    createApp(['name' => 'Inactive App', 'is_active' => false]);

    $activeApps = App::active()->get();

    expect($activeApps)->toHaveCount(1);
    expect($activeApps->first()->name)->toBe('Active App');
});

test('reverb_config attribute returns correct structure', function () {
    $app = createApp([
        'key' => 'test-key-12345',
        'secret' => 'test-secret-67890',
        'host' => 'example.com',
        'port' => 443,
        'scheme' => 'https',
        'allowed_origins' => ['https://example.com'],
        'ping_interval' => 60,
        'activity_timeout' => 30,
        'max_connections' => 100,
        'max_message_size' => 10000,
        'accept_client_events_from' => 'members',
        'rate_limiting_enabled' => true,
        'rate_limit_max_attempts' => 60,
        'rate_limit_decay_seconds' => 60,
        'rate_limit_terminate_on_limit' => false,
    ]);

    $config = $app->reverb_config;

    expect($config)->toBeArray();
    expect($config['key'])->toBe('test-key-12345');
    expect($config['secret'])->toBe('test-secret-67890');
    expect($config['app_id'])->toBe($app->id);
    expect($config['options'])->toBeArray();
    expect($config['options']['host'])->toBe('example.com');
    expect($config['options']['port'])->toBe(443);
    expect($config['options']['scheme'])->toBe('https');
    expect($config['options']['useTLS'])->toBeTrue();
    expect($config['allowed_origins'])->toBe(['https://example.com']);
    expect($config['ping_interval'])->toBe(60);
    expect($config['activity_timeout'])->toBe(30);
    expect($config['max_connections'])->toBe(100);
    expect($config['max_message_size'])->toBe(10000);
    expect($config['accept_client_events_from'])->toBe('members');
    expect($config['rate_limiting'])->toBeArray();
});

test('rate_limiting_config attribute returns correct structure', function () {
    $app = createApp([
        'rate_limiting_enabled' => true,
        'rate_limit_max_attempts' => 120,
        'rate_limit_decay_seconds' => 30,
        'rate_limit_terminate_on_limit' => true,
    ]);

    $config = $app->rate_limiting_config;

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeTrue();
    expect($config['max_attempts'])->toBe(120);
    expect($config['decay_seconds'])->toBe(30);
    expect($config['terminate_on_limit'])->toBeTrue();
});

test('connection_options attribute returns correct structure', function () {
    $app = createApp([
        'host' => 'ws.example.com',
        'port' => 6001,
        'scheme' => 'http',
    ]);

    $options = $app->connection_options;

    expect($options)->toBeArray();
    expect($options['host'])->toBe('ws.example.com');
    expect($options['port'])->toBe(6001);
    expect($options['scheme'])->toBe('http');
    expect($options['useTLS'])->toBeFalse();
});

test('connection_options useTLS is true for https scheme', function () {
    $app = createApp(['scheme' => 'https']);

    expect($app->connection_options['useTLS'])->toBeTrue();
});
