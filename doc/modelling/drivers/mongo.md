# MongoDB

Lodata supports read, update, create, delete, and query operations, and has extensive support for filters
on an attached MongoDB collection.

## Discovery

A MongoDB Collection can be discovered using `Lodata::discover($collection);`.

For example this code will generate a `MongoEntitySet` with an associated `MongoEntityType` and register them
with the model. The `EntityType` and `EntitySet` will be automatically created singular/plural respectively.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $client = new \MongoDB\Client;
        \Lodata::discover($client->db->users);
    }
}
```

## Type extension

The `MongoEntityType` provides an entity type starting point, with the key property `_id` set to an `Edm.String` key.

This entity type is an [open type](../types/README.md#open-types) by default, which means you do not have to
declare any additional properties on the type to use it with Lodata.
However, adding declared properties will make your data model more discoverable, so this is
recommended if possible. This can be performed either after discovery, or when manually defining your type.

This is an example of manually creating the type and set:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entityType = new \Flat3\Lodata\Drivers\MongoEntityType('User');
        $entityType->addDeclaredProperty('name', \Flat3\Lodata\Type::string());

        $entitySet = new \Flat3\Lodata\Drivers\MongoEntitySet('Users', $entityType);

        $client = new \MongoDB\Client;
        $entitySet->setCollection($client->db->users);

        \Lodata:add($entitySet);
    }
}
```