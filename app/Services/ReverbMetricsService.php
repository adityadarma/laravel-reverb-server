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
     * Get total connections across all active apps.
     */
    public function totalConnections(): int
    {
        return App::active()->get()->sum(fn ($app) => $this->connections($app));
    }
}
