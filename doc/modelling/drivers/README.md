# Entity Sets

OData describes data stores as entity sets, with the records within as entities.
Lodata implements support for several kinds of services handled by Laravel, which are referred to as "drivers".

A Lodata 'driver' represents any data store that could implement one or more of the `\Flat3\Lodata\Interfaces\EntitySet` interfaces
including `QueryInterface`, `ReadInterface`, `UpdateInterface`, `DeleteInterface`, and `CreateInterface`.

A wide variety of different services can support these interfaces in whatever way makes sense to that service. Services could be
other databases, NoSQL services, other REST APIs or simple on-disk text files.

In addition to the query
interface the driver may implement `SearchInterface` and `FilterInterface` to support `$search` and `$filter`, and other system
query parameters can be supported through `ExpandInterface`, `TokenPaginationInterface`, `PaginationInterface` and `OrderByInterface`.

Implementation of these interfaces is optional, and Lodata will detect support and return a 'Not Implemented' exception
to a client trying to use an interface that is not available.

## Caching

Some entity set drivers support automatically discovering the schema of the connected data store. This discovery can
add unnecessary overhead in production, so Lodata provides [configuration options](/getting-started/configuration)
to add caching of schema data.

## Property renaming

Lodata supports having different property names used in the schema compared to the backend driver. For example you
may have an OData property named `CustomerAge` which is named `customer_age` in a database table. To create a mapping
from Lodata property to backend property use the `setPropertySourceName()` method on the entity set object.

```php
$entitySet = Lodata::getEntitySet('passengers');
$ageProperty = $entitySet->getType()->getProperty('CustomerAge');
$entitySet->setPropertySourceName($ageProperty, 'customer_age');
```

