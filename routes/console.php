<?php

use App\Models\App;
use App\Models\ReverbMetric;
use App\Services\ReverbMetricsService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $service = app(ReverbMetricsService::class);

    App::active()->each(function ($app) use ($service) {
        ReverbMetric::create([
            'app_id' => $app->id,
            'connections' => $service->connections($app),
            'channels' => count($service->channels($app)),
            'recorded_at' => now(),
        ]);
    });

    // Keep only last 24 hours
    ReverbMetric::prune();
})->everyMinute()->name('reverb:record-metrics');
