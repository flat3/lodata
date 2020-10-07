<?php

namespace Flat3\OData;

use Flat3\OData\Controller\OData as ODataController;
use Flat3\OData\Controller\ODCFF;
use Flat3\OData\Controller\PBIDS;
use Flat3\OData\Middleware\Authentication;
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
        $this->app->singleton(Model::class, function () {
            return new Model();
        });

        $authMiddleware = config('odata.authmiddleware');
        $router->aliasMiddleware('odata.auth', Authentication::class);

        Route::middleware([$authMiddleware])->group(function () {
            $route = self::route();

            Route::get("{$route}/odata.pbids", [PBIDS::class, 'get']);
            Route::get("{$route}/{identifier}.odc", [ODCFF::class, 'get']);

            Route::any("{$route}{path}", [ODataController::class, 'get'])
                ->where('path', '(.*)');
        });
    }
}
