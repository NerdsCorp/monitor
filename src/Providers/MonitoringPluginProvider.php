<?php

namespace Pelican\Monitoring\Providers;

use Illuminate\Support\ServiceProvider;
use Pelican\Monitoring\Services\MonitoringDataService;

class MonitoringPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MonitoringDataService::class);
    }

    public function boot(): void
    {
        //
    }
}
