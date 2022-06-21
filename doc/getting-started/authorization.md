# Authorization

Lodata supports authorization via [Laravel gates](https://laravel.com/docs/8.x/authorization#gates).

Each API request will be checked via an ability named `lodata`.
The gate will receive the standard `$user` argument, and a `Flat3\Lodata\Helper\Gate` object.

This object contains the type of request being made, the Lodata object it is being made against, the
Lodata [Transaction](/internals/transactions.md) and in the
case of an operation the arguments array will be provided.

::: tip
When working with Lodata requests you should always get request information via the [Transaction](/internals/transactions.md) object,
in case it's a [batch](/making-requests/batch.md) request that has its own context.
:::

This should be all the information needed for a gate policy to decide whether to allow the request.

At install time, Lodata runs in a readonly mode. Change the value of the `readonly` property in `config/lodata.php` to
enable data modification operations.

This example shows how you could allow access to the `Users` entity set only if the user is an administrator.

```php
<?php

namespace App\Providers;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Helper\Gate as LodataGate;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class LodataServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('lodata', function (User $user, LodataGate $gate) {
            $resource = $gate->getResource();

            if (!$resource instanceof EntitySet) {
                return true;
            }

            if ($resource->getIdentifier()->getName() === 'Users' && !$user->isAdministrator()) {
               return false;
            }

            return true;
        });
    }
}
```
