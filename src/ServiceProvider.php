<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Monitor;
use Flat3\Lodata\Controller\OData;
use Flat3\Lodata\Controller\ODCFF;
use Flat3\Lodata\Controller\PBIDS;
use Illuminate\Support\Facades\Route;

/**
 * Service Provider
 * @link https://laravel.com/docs/8.x/providers
 * @package Flat3\Lodata
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Get the endpoint of the OData service document
     * @return string
     */
    public static function endpoint(): string
    {
        return url(self::route()).'/';
    }

    /**
     * Get the configured route prefix
     * @return string
     */
    public static function route(): string
    {
        return rtrim(config('lodata.prefix'), '/');
    }

    /**
     * Service provider registration method
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'lodata');
    }

    /**
     * Service provider boot method
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config.php' => config_path('lodata.php')], 'config');
        }

        $this->app->singleton(Model::class, function () {
            return new Model();
        });

        $this->app->bind('lodata.model', function ($app) {
            return $app->make(Model::class);
        });

        $route = self::route();
        $middleware = config('lodata.middleware', []);

        Route::get("{$route}/_lodata/odata.pbids", [PBIDS::class, 'get']);
        Route::get("{$route}/_lodata/{identifier}.odc", [ODCFF::class, 'get']);
        Route::resource("${route}/_lodata/monitor", Monitor::class);
        Route::any("{$route}{path}", [OData::class, 'handle'])->where('path', '(.*)')->middleware($middleware);
    }
}
