# SQL

In addition to the Eloquent model driver, Lodata can discover database tables directly. This can be used to expose
tables through OData that are not used in Eloquent models, such as through tables for many-to-many relationships. This is required
for applications that treat OData Feeds as relational database models such as PowerBI and Tableau. It can also be used to expose
databases that are not even used in the Laravel application, or to use Lodata as simply an OData endpoint for an existing database.

SQL database tables can be discovered using this syntax:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $passengerType = new \Flat3\Lodata\EntityType('passenger');
        $passengerSet = (new \Flat3\Lodata\Drivers\SQLEntitySet('passengers', $passengerType))
            ->setTable('passengers')
            ->discoverProperties();
        \Lodata::add($passengerSet);
    }
}
```

First define an empty entity type with the name `passenger`, and use it to generate the entity set.
Then assign the table name `passengers`. When `discoverProperties` is executed, `passengerType` will be filled with field
types discovered by the entity set.

Almost all the available `$filter` capabilities are mapped into SQL expressions, supporting all the same database
backends that Laravel does.

This driver supports `$search` by passing the provided search term into `field LIKE %param%` requests.
