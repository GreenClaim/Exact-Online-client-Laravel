<?php

namespace Yource\ExactOnlineClient;

use Illuminate\Support\ServiceProvider;

class ExactOnlineClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');

        $this->loadViewsFrom(__DIR__ . '/views', 'exact-online-client');

        $this->publishes([
            __DIR__ . '/../config/exact-online-client-laravel.php' => config_path('exact-online-client-laravel.php'),
            __DIR__ . '/views'                                     => base_path('resources/views/vendor/yource/exact-online-client-laravel'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(ExactOnlineClient::class, function () {
            return new ExactOnlineClient('');
        });
    }
}
