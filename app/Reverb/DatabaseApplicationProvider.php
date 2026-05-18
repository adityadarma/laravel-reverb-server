<?php

namespace App\Reverb;

use App\Models\App;
use Illuminate\Support\Collection;
use Laravel\Reverb\Application;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Exceptions\InvalidApplication;

class DatabaseApplicationProvider implements ApplicationProvider
{
    /**
     * Get all active applications.
     *
     * @return Collection<Application>
     */
    public function all(): Collection
    {
        return App::active()->get()->map(fn ($app) => $this->toReverb($app));
    }

    /**
     * Find an application by ID.
     *
     * @throws InvalidApplication
     */
    public function findById(string $id): Application
    {
        $app = App::active()->find($id);

        if (! $app) {
            throw new InvalidApplication;
        }

        return $this->toReverb($app);
    }

    /**
     * Find an application by key.
     *
     * @throws InvalidApplication
     */
    public function findByKey(string $key): Application
    {
        $app = App::active()->where('key', $key)->first();

        if (! $app) {
            throw new InvalidApplication;
        }

        return $this->toReverb($app);
    }

    /**
     * Convert an App model to a Reverb Application instance.
     */
    private function toReverb(App $app): Application
    {
        return new Application(
            $app->id,
            $app->key,
            $app->secret,
            $app->ping_interval,
            $app->activity_timeout,
            $app->allowed_origins ?? ['*'],
            $app->max_message_size,
            $app->max_connections,
            $app->accept_client_events_from,
            $app->rate_limiting_config,
            [
                'host' => $app->host,
                'port' => $app->port,
                'scheme' => $app->scheme,
                'useTLS' => $app->scheme === 'https',
            ],
        );
    }
}
