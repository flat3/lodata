# Routing

## Service Provider

When Lodata is installed via composer it [registers](https://laravel.com/docs/8.x/providers#registering-providers) a Service Provider.

This provider sets up an instance of `Flat3\Lodata\Model` as a singleton in the service container, and configures all the routes
and middleware. There are hooks in the [configuration](/getting-started/configuration) to modify the behaviour of the service provider.

::: details View the service provider
<<< @../../src/ServiceProvider.php
:::

## Existing controllers

In addition to using the standard routing provided out of the box, Lodata supports mounting resources on routes
managed by existing controllers.
This can be useful if you are adding Lodata to an existing project that has middleware, authentication, authorization
and a routing structure already in place.

The first step is to define the OData resource route, somewhere inside an existing service provider. In this example
we have a `UserController` mounting the OData path as `query` within the `users` group.

```php
public function boot()
{   
    $this->configureRateLimiting();

    $this->routes(function () {
        Route::prefix('users')->group(function ($router) {
            $router->any("query{path}", [UserController::class, 'query'])->where('path', '(.*)');
        });
    });
}
```

Then the `query` method is added to the controller, rewriting the path to the original OData route for processing.
Note the use of Lodata's own `Request` object being injected into the method, not the standard Laravel object.

```php
use Flat3\Lodata\Controller\Request;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function query(Request $request, Transaction $transaction, ?string $path = '')
    {
        $request->setPath('/odata/airports'.$path);

        return $transaction->initialize($request)->execute();
    }
}
```

Now the airports entity set can be accessed using the URL `http://localhost:8000/users/query`. With the
controller function having early access to the `Request` object, you could also overwrite or set default
query parameters and headers if required to support your client application.

The canonical entity ID and read link metadata exposed on this customized route will still refer to the standard OData
URL such as `http://localhost:8000/odata/Users(1)`, making this route effectively an alias for the canonical route.