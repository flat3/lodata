# Types

OData specifies many [primitive types](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PrimitiveValue)
that can be used in Lodata.

PHP's type system is less specific than OData. For example where PHP only has `int`, OData has `Edm.Byte`, `Edm.Int16`, `Edm.Int32` and `Edm.Int64`.

Marshalling between PHP and OData types is automatic via conversion and coercion.
Lodata will force PHP data into the type specified by the developer, for example converting a 64-bit PHP `int` to
an OData `Edm.Int16` may cause truncation or overflow. When a client receives the property it will be of the expected type. In general,
it's best to use `Edm.Int64` for PHP values and for backend services like databases the matching EDM type for the property can be specified in
the entity type.

PHP supports higher precision floating point values than JSON, so Lodata implements
[IEEE754 compatibility](https://datatracker.ietf.org/doc/html/rfc7493#section-2.2) in OData by returning
`Edm.Double` (and similar) types as strings if requested to do so by the client in the `Accept` header.

Lodata implements `Edm.Date`, `Edm.DateTimeOffset` and `Edm.TimeOfDay` using immutable [Carbon](https://carbon.nesbot.com)
objects, and retrieving the value of (eg) a `\Flat3\Lodata\Type\DateTimeOffset` using its `get()` method will return a `Carbon\CarbonImmutable`.

The `Edm.Duration` type is stored internally as a number of seconds in a PHP float.

Lodata includes type extensions to support unsigned integer types `UInt16`, `UInt32` and `UInt64` which are
extensions of the underlying canonical types `Edm.Int16`, `Edm.Int32` and `Edm.Int64`.

These type definitions are not in the default model as not all OData clients interpret them correctly.
If the application developer adds them they will be used during automatic entity type property detection.

To add one or more of these types, use `Lodata::add()` to add a new `PrimitiveType` passing in the class type name:
```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new \Flat3\Lodata\PrimitiveType(\Flat3\Lodata\Type\UInt16::class))
    }
}
```

Lodata supports changing a property's type after definition or discovery using a call such as:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::getEntityType('Flight')
          ->getProperty('duration')
          ->setType(\Flat3\Lodata\Type::uint32());
    }
}
```
