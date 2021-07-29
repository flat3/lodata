# Search

The OData specification describes that the behaviour of the `$search` system query parameter is application-specific.
In Lodata, this means that different entity set drivers implement search in different ways.

Drivers that support searching within specific properties (such as Eloquent and SQL) can have properties marked as
supporting search.

In this example the entity type 'airport' is retrieved,
which may have been generated via autodiscovery. The 'name' property is retrieved, and its 'searchable' property is updated.

```php
$airportType = Lodata::getEntityType('airport');
$airportType->getProperty('name')->setSearchable();
```