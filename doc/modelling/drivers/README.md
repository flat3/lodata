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