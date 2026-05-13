<?php

namespace App\Providers;

use App\Models\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

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
        app()->resolving('reverb', function () {
            $apps = Cache::remember('reverb_apps', 60, fn () =>
                App::active()->get()->map->reverb_config->values()->all()
            );

            config(['reverb.apps.apps' => $apps]);
        });
    }
}
