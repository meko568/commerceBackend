<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TokenGuard;

class TokenAuthProvider extends ServiceProvider
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
        $this->app['auth']->extend('token', function ($app, $name, array $config) {
            return new TokenGuard($app['auth']->createUserProvider($config['provider']));
        });
    }
}
