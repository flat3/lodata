# Authorization

Lodata supports controlling access to OData endoints via [Laravel gates](https://laravel.com/docs/8.x/authorization#gates),
and by subclassing `EntitySet` and overriding the relevant methods.

## Gates

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

## Overrides

For more fine-grained control over the behaviour of the `EntitySet` you can subclass it and override methods.

This example overrides the `create` method to only allow "admins" to create entities. Each of `query`, `read`,
`update` and `delete` can also be overridden in this way.

```php
class ProtectedEntitySet extends EloquentEntitySet
{
    public function create(PropertyValues $propertyValues): Entity
    {
        if (!Auth::user()->isAdmin) {
            throw new ForbiddenException('user_not_admin', 'Only an admin can create in this entity set');
        }

        return parent::create($propertyValues);
    }
}
```

## EloquentEntitySet

The `EloquentEntitySet` uses the model's `Builder` to generate queries. The builder can be modified to provide
additional scopes or clauses at runtime.

This entity set adds the `active` scope to any builder.

```php
class FilteredUserEntitySet extends EloquentEntitySet
{
    public function getBuilder(): Builder
    {
        return parent::getBuilder()->active();
    }
}
```