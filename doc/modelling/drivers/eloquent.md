# Eloquent

Lodata can 'discover' Eloquent models, and the relationships between the models.

::: tip
The Eloquent driver extends the SQL driver, so the same `$filter` capabilities exist in both.
:::

Discovery is
performed first using [DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.12/index.html) to
introspect the database table, then [Eloquent casts](https://laravel.com/docs/8.x/eloquent-mutators#custom-casts)
are used for further type specification. During requests, the Eloquent model getter/setter functions are used
to refer to the properties, so any additional field processing being performed by the model will be preserved.

To discover a model the `Lodata` facade that exists in the root namespace can be used. For example to discover
two models:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::discoverEloquentModel(\App\Models\Flight::class);
        \Lodata::discoverEloquentModel(\App\Models\Passenger::class);
    }
}
```

If model `Flight` has a method `passengers` that returns a [relationship](https://laravel.com/docs/8.x/eloquent-relationships)
to `Passenger` such as hasOne, hasMany, hasManyThrough, this can be discovered by Lodata as a navigation property on the `Flights` entity set.

::: tip
The same as Laravel itself, Lodata typically refers to 'entity types' in the singular form and 'entity sets' in
the plural form. An entity set and its related entity set must both be defined through discovery before a
relationship can be created.
:::

```php
\Lodata::getEntitySet('Flights')
  ->discoverRelationship('passengers')
```

A navigation property now exists in the `Flight` entity set for `Passengers`. This enables the client to
navigate by using the navigation property in a URL similar to
[`http://127.0.0.1:8000/odata/Flights(1)/passengers`](http://127.0.0.1:8000/odata/Flights(1)/passengers)
to choose the flight with ID 1, and to get the passengers related to this flight. This navigation property can
also be used in `$expand` requests.

If Lodata is able to determine the relationship cardinality it will be represented in the service metadata
document.
