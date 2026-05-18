<?php

namespace App\Providers;

use App\Reverb\DatabaseApplicationProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ApplicationManager;

class ReverbAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->resolving(ApplicationManager::class, function (ApplicationManager $manager) {
            $manager->extend('database', fn () => new DatabaseApplicationProvider);
        });

        config(['reverb.apps.provider' => 'database']);
    }
}
