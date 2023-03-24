<?php

namespace CloudMonitor\APIFlow;

use Illuminate\Support\ServiceProvider;
use CloudMonitor\APIFlow\Commands\APIEndpoint;

class APIFlowServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.makeapi', function () {
            return new APIEndpoint;
        });

        $this->commands(['command.makeapi']);

        \Illuminate\Routing\Router::macro('api', function($path, $controller) {
            return \Illuminate\Support\Facades\Route::resource($path, $controller);
        });
    }
}
