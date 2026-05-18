<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Http;

class ReverbMetricsService
{
    /**
     * Get connection count for a single app from Reverb HTTP API.
     */
    public function connections(App $app): int
    {
        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $path = config('reverb.servers.reverb.path', '');

        if ($host === '0.0.0.0') {
            $host = '127.0.0.1';
        }

        $endpoint = "/apps/{$app->id}/connections";
        $timestamp = (string) time();

        $params = [
            'auth_key' => $app->key,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
        ];
        ksort($params);

        $queryString = http_build_query($params);
        $toSign = "GET\n{$endpoint}\n{$queryString}";
        $signature = hash_hmac('sha256', $toSign, $app->secret);

        $scheme = app()->isProduction() ? 'https' : 'http';
        $url = "{$scheme}://{$host}:{$port}{$path}{$endpoint}?{$queryString}&auth_signature={$signature}";

        try {
            $response = Http::timeout(2)->get($url);

            if ($response->successful()) {
                return $response->json('connections', 0);
            }
        } catch (\Throwable) {
            // Reverb server not running or unreachable
        }

        return 0;
    }

    /**
     * Get active channels for a single app from Reverb HTTP API.
     */
    public function channels(App $app): array
    {
        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $path = config('reverb.servers.reverb.path', '');

        if ($host === '0.0.0.0') {
            $host = '127.0.0.1';
        }

        $scheme = app()->isProduction() ? 'https' : 'http';
        $endpoint = "/apps/{$app->id}/channels";
        $timestamp = (string) time();

        $params = [
            'auth_key' => $app->key,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
        ];
        ksort($params);

        $queryString = http_build_query($params);
        $toSign = "GET\n{$endpoint}\n{$queryString}";
        $signature = hash_hmac('sha256', $toSign, $app->secret);

        $url = "{$scheme}://{$host}:{$port}{$path}{$endpoint}?{$queryString}&auth_signature={$signature}";

        try {
            $response = Http::timeout(2)->get($url);

            if ($response->successful()) {
                return $response->json('channels', []);
            }
        } catch (\Throwable) {
            // Reverb server not running or unreachable
        }

        return [];
    }

    /**
     * Get active channels with subscriber info for a single app.
     * Fetches channels list then enriches with per-channel subscription count.
     */
    public function channelsWithInfo(App $app): array
    {
        $channels = $this->channels($app);

        if (empty($channels)) {
            return [];
        }

        $result = [];

        foreach ($channels as $channelName => $data) {
            $info = $this->channelDetail($app, $channelName);
            $result[$channelName] = array_merge((array) $data, $info);
        }

        return $result;
    }

    /**
     * Get detail info for a specific channel (subscription_count, user_count).
     */
    public function channelDetail(App $app, string $channelName): array
    {
        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $path = config('reverb.servers.reverb.path', '');

        if ($host === '0.0.0.0') {
            $host = '127.0.0.1';
        }

        $scheme = app()->isProduction() ? 'https' : 'http';
        $endpoint = "/apps/{$app->id}/channels/{$channelName}";
        $timestamp = (string) time();

        // Request subscription_count for all channels, user_count only for presence
        $infoFields = str_starts_with($channelName, 'presence-')
            ? 'subscription_count,user_count'
            : 'subscription_count';

        $params = [
            'auth_key' => $app->key,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
            'info' => $infoFields,
        ];
        ksort($params);

        $queryString = http_build_query($params);
        $toSign = "GET\n{$endpoint}\n{$queryString}";
        $signature = hash_hmac('sha256', $toSign, $app->secret);

        $url = "{$scheme}://{$host}:{$port}{$path}{$endpoint}?{$queryString}&auth_signature={$signature}";

        try {
            $response = Http::timeout(2)->get($url);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            //
        }

        return [];
    }

    /**
     * Get total connections across all active apps.
     */
    public function totalConnections(): int
    {
        return App::active()->get()->sum(fn ($app) => $this->connections($app));
    }
}
