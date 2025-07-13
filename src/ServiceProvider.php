<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use RuntimeException;
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
use Flat3\Lodata\Interfaces\ServiceEndpointInterface;
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
            $this->bootServices(new Endpoint(''));
        }
        else {
            // Let’s examine the request path
            $segments = explode('/', request()->path());

            // we only kick off operation when path prefix is configured in lodata.php
            // and bypass all other routes for performance
            if ($segments[0] === config('lodata.prefix')) {

                // next look up the configured service endpoints
                $serviceUris = config('lodata.endpoints', []);

                if (0 === sizeof($serviceUris) || count($segments) === 1) {
                    // when no locators are defined, or the global locator ist requested,
                    // enter global mode; this will ensure compatibility with prior
                    // versions of this package
                    $service = new Endpoint('');
                }
                else if (array_key_exists($segments[1], $serviceUris)) {
                    $clazz = $serviceUris[$segments[1]];
                    if (!class_exists($clazz)) {
                        throw new RuntimeException(sprintf('Endpoint class `%s` does not exist', $clazz));
                    }
                    if (!is_subclass_of($clazz, ServiceEndpointInterface::class)) {
                        throw new RuntimeException(sprintf('Endpoint class `%s` must implement Flat3\\Lodata\\Interfaces\\ServiceEndpointInterface', $clazz));
                    }
                    $service = new $clazz($segments[1]);
                }
                else {
                    // when no service definition could be found for the path segment,
                    // we assume global scope
                    $service = new Endpoint('');
                }

                $this->bootServices($service);
            }
        }
    }

    private function bootServices($service): void
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
