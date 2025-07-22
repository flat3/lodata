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
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Service Provider
 *
 * https://<server>:<port>/<prefix>/<service-uri>/$metadata
 *
 * @link https://laravel.com/docs/8.x/providers
 * @package Flat3\Lodata
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
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
        $this->bootServices(new Endpoint(''));
    }

    private function bootServices(Endpoint $service): void
    {
        // register the $service, which is a singleton, with the container; this allows us
        // to fulfill all old ServiceProvider::route() and ServiceProvider::endpoint()
        // calls with app()->make(ODataService::class)->route() or
        // app()->make(ODataService::class)->endpoint()
        $this->app->instance(Endpoint::class, $service);

        $this->app->bind(DBAL::class, function (Application $app, array $args) {
            return version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '>=') ? new DBAL\DBAL4($args['connection']) : new DBAL\DBAL3($args['connection']);
        });

        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        // next instantiate and discover the global Model
        $model = $service->discover(new Model());
        assert($model instanceof Model);

        // and register it with the container
        $this->app->instance(Model::class, $model);

        // register alias
        $this->app->alias(Model::class, 'lodata.model');

        $this->app->bind(Response::class, function () {
            return Kernel::VERSION_ID < 60000 ? new Symfony\Response5() : new Symfony\Response6();
        });

        $this->app->bind(Filesystem::class, function () {
            return class_exists('League\Flysystem\Adapter\Local') ? new Flysystem\Flysystem1() : new Flysystem\Flysystem3();
        });

        $route = $service->route();
        $middleware = config('lodata.middleware', []);

        Route::get("{$route}/_lodata/odata.pbids", [PBIDS::class, 'get']);
        Route::get("{$route}/_lodata/{identifier}.odc", [ODCFF::class, 'get']);
        Route::resource("{$route}/_lodata/monitor", Monitor::class);
        Route::any("{$route}{path}", [OData::class, 'handle'])->where('path', '(.*)')->middleware($middleware);
    }
}
