# Collection

Lodata can expose any object that implements the Laravel
[Enumerable](https://laravel.com/docs/8.x/collections#the-enumerable-contract) contract,
including [Collection](https://laravel.com/docs/8.x/collections)
and [LazyCollection](https://laravel.com/docs/8.x/collections#lazy-collections).
Lodata supports both numeric and string keyed collections.

Any array of data can be easily converted to a Collection using the
[`collect()`](https://laravel.com/docs/8.x/collections#creating-collections) method.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entityType = new \Flat3\Lodata\EntityType('example');
        $entityType->setKey(new \Flat3\Lodata\DeclaredProperty('id', \Flat3\Lodata\Type::string()));
        $entityType->addDeclaredProperty('name', \Flat3\Lodata\Type::string());
        $entityType->addDeclaredProperty('dob', \Flat3\Lodata\Type::date());
        $entitySet = new \Flat3\Lodata\Drivers\CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);
        \Lodata::add($entitySet);
    }
}
```

First define an empty entity type with the name `example`, and configure the key property as either numeric (eg `Edm.Int64`)
or string (`Edm.String`).
Then add the declared properties used in the collection values to complete the type.
Finally, create the `CollectionEntitySet` using the entity type, and call `setCollection()` to connect your collection.

There is a PHP implementation for almost all the available `$filter` expressions and functions.