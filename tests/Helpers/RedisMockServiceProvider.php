<?php

/**
 * Copyright (c) 2018 Josias Montag
 * https://github.com/josiasmontag/laravel-redis-mock
 */

namespace Flat3\Lodata\Tests\Helpers;

use Illuminate\Support\ServiceProvider;

class RedisMockServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make('redis')->extend('mock', function () {
            return new MockPredisConnector();
        });
    }
}
