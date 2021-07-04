# Expressions

Lodata contains an expression parser in `\Flat3\Lodata\Expression` that handles both `$search` and `$filter` expressions. The
parser decodes the incoming expression into an [abstract syntax tree](https://en.m.wikipedia.org/wiki/Abstract_syntax_tree). During
entity set query processing the entity set driver will be passed every element of the tree in the correct parsing order, enabling it
to convert the OData query into a native query such as an SQL query.

Because not every possible OData function or operation is supported by every Laravel database driver, or the internal semantics of the
underlying database do not support the required data types, then a "Not Supported" exception may be thrown by some database drivers
and not others.

The OData specification describes that the behaviour of the `$search` system query parameter is application-specific. Simple support
for converting `$search` to a series of `field LIKE %param%` requests is available.

The properties that should be used in search queries can be "tagged" using this example. Here the entity type 'airport' is retrieved,
which may have been generated via autodiscovery. The 'name' property is also retrieved, and its 'searchable' property is updated.

```php
$airportType = Lodata::getEntityType('airport');
$airportType->getProperty('name')->setSearchable();
```

Any property marked in this way is added to the query by the SQL driver.

The behaviour of both the `$search` and `$filter` parameters can be overridden by extending the driver class, and the relevant methods.