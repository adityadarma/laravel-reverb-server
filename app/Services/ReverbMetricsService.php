<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Support\Facades\Http;

class ReverbMetricsService
{
    /**
     * Get the Reverb server base URL.
     */
    private function baseUrl(): string
    {
        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $path = config('reverb.servers.reverb.path', '');
        $scheme = app()->isProduction() ? 'https' : 'http';

        if ($host === '0.0.0.0') {
            $host = '127.0.0.1';
        }

        return "{$scheme}://{$host}:{$port}{$path}";
    }

    /**
     * Build a signed URL for the Reverb HTTP API.
     */
    private function signedUrl(App $app, string $method, string $endpoint, array $extra = []): string
    {
        $params = array_merge([
            'auth_key' => $app->key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
        ], $extra);

        ksort($params);

        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', "{$method}\n{$endpoint}\n{$queryString}", $app->secret);

        return $this->baseUrl()."{$endpoint}?{$queryString}&auth_signature={$signature}";
    }

    /**
     * Get connection count for a single app.
     */
    public function connections(App $app): int
    {
        try {
            $response = Http::timeout(2)->get(
                $this->signedUrl($app, 'GET', "/apps/{$app->id}/connections")
            );

            return $response->successful() ? $response->json('connections', 0) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Get active channels for a single app.
     */
    public function channels(App $app): array
    {
        try {
            $response = Http::timeout(2)->get(
                $this->signedUrl($app, 'GET', "/apps/{$app->id}/channels")
            );

            return $response->successful() ? $response->json('channels', []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get active channels with subscriber info for a single app.
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
        $infoFields = str_starts_with($channelName, 'presence-')
            ? 'subscription_count,user_count'
            : 'subscription_count';

        try {
            $response = Http::timeout(2)->get(
                $this->signedUrl($app, 'GET', "/apps/{$app->id}/channels/{$channelName}", [
                    'info' => $infoFields,
                ])
            );

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get total connections across all active apps.
     */
    public function totalConnections(): int
    {
        return App::active()->get()->sum(fn ($app) => $this->connections($app));
    }
}
