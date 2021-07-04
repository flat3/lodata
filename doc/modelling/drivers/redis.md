# Redis

Lodata supports read, update, create, delete and query options on an attached Redis database.

The `RedisEntityType` provides an entity type starting point, with the key property set to an `Edm.String` key. Then
attach the `RedisEntitySet` to expose the database.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entityType = new \Flat3\Lodata\Drivers\RedisEntityType('passenger');
        $entityType->addDeclaredProperty('name', \Flat3\Lodata\Type::string());
        \Lodata::add(new \Flat3\Lodata\Drivers\RedisEntitySet('passengers', $entityType));
    }
}
```

The driver expects all values in the database to be associative arrays encoded with PHP's `serialize()`.
To modify this behaviour, the `RedisEntitySet` can be subclassed and the `serialize` and `unserialize` methods can
be overridden.

The specific Redis database connection can be configured by calling `setConnectionName()` on the entity set.