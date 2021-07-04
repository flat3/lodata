# Alternative keys

In addition to the standard 'id' key that is typical in a database table, any other unique field can be added as an
[alternative key](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#_Toc31360936). This can then
be used to reference an entity.

The properties that should be used as alternative keys can be "tagged" using this example. Here the entity type `airport` is retrieved.
The `name` property then has its `alternativeKey` property set.

```php
$airportType = Lodata::getEntityType('airport');
$airportType->getProperty('code')->setAlternativeKey();
```

With this in place, an airport can be queried with its code using the request style:

```
http://localhost:8000/odata/Airports(code='elo')
```
