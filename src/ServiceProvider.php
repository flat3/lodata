<?php

namespace Flat3\OData;

use Flat3\OData\Controller\Metadata;
use Flat3\OData\Controller\OData;
use Flat3\OData\Controller\ODCFF;
use Flat3\OData\Controller\PBIDS;
use Flat3\OData\Controller\Service;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public static function restEndpoint(): string
    {
        return url(self::route()).'/';
    }

    public static function route(): string
    {
        return rtrim(config('odata.route') ?: 'odata', '/');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'odata');
    }

    public function boot(Router $router)
    {
        $this->app->singleton(DataModel::class, function () {
            return new DataModel();
        });

        $authMiddleware = config('odata.authmiddleware');
        $router->aliasMiddleware('odata.auth', Authentication::class);

        Route::middleware([$authMiddleware])->group(function () {
            $route = self::route();

            Route::get("{$route}/odata.pbids", [PBIDS::class, 'get']);
            Route::get("{$route}/{identifier}.odc", [ODCFF::class, 'get']);

            Route::get("{$route}/", [Service::class, 'get']);
            Route::get("{$route}/\$metadata", [Metadata::class, 'get']);

            Route::get("{$route}{path}", [OData::class, 'get'])->where('path', '(.*)');
        });
    }
}
