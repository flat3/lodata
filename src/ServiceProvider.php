<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Composer\InstalledVersions;
use Flat3\Lodata\Controller\Monitor;
use Flat3\Lodata\Controller\OData;
use Flat3\Lodata\Controller\ODCFF;
use Flat3\Lodata\Controller\PBIDS;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Helper\Filesystem;
use Flat3\Lodata\Helper\Flysystem;
use Flat3\Lodata\Helper\DBAL;
use Flat3\Lodata\Helper\Symfony;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Kernel;

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

        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        $this->app->singleton(Model::class, function () {
            return new Model();
        });

        $this->app->bind('lodata.model', function ($app) {
            return $app->make(Model::class);
        });

        $this->app->bind(Response::class, function () {
            return Kernel::VERSION_ID < 60000 ? new Symfony\Response5() : new Symfony\Response6();
        });

        $this->app->bind(Filesystem::class, function () {
            return class_exists('League\Flysystem\Adapter\Local') ? new Flysystem\Flysystem1() : new Flysystem\Flysystem3();
        });

        $this->app->bind(DBAL::class, function (Application $app, array $args) {
            return version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? new DBAL\DBAL4($args['connection']) : new DBAL\DBAL3($args['connection']);
        });

        $route = self::route();
        $middleware = config('lodata.middleware', []);

        Route::get("{$route}/_lodata/odata.pbids", [PBIDS::class, 'get']);
        Route::get("{$route}/_lodata/{identifier}.odc", [ODCFF::class, 'get']);
        Route::resource("{$route}/_lodata/monitor", Monitor::class);
        Route::any("{$route}{path}", [OData::class, 'handle'])->where('path', '(.*)')->middleware($middleware);
    }
}
