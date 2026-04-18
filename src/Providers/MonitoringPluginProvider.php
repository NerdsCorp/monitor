<?php

namespace Pelican\Monitoring\Providers;

use Illuminate\Http\Client\ConnectionException;
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
        $this->callAfterResolving(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            function (\Illuminate\Contracts\Debug\ExceptionHandler $handler) {
                $handler->reportable(function (ConnectionException $e) {
                    return ! str_contains($e->getMessage(), 'cURL error 28');
                });
            }
        );
    }
}