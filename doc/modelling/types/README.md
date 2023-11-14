# Types

OData specifies many [primitive types](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PrimitiveValue)
that can be used in Lodata:

| Type               | Meaning                                                        |
| ------------------ |----------------------------------------------------------------|
| Edm.Binary         | Binary data                                                    |
| Edm.Boolean        | Binary-valued logic                                            |
| Edm.Byte           | Unsigned 8-bit integer                                         |
| Edm.Date           | Date without a time-zone offset                                |
| Edm.DateTimeOffset | Date and time with a time-zone offset, no leap seconds         |
| Edm.Decimal        | Numeric values with decimal representation                     |
| Edm.Double         | IEEE 754 binary64 floating-point number (15-17 decimal digits) |
| Edm.Duration       | Signed duration in days, hours, minutes, and (sub)seconds      |
| Edm.Guid           | 16-byte (128-bit) unique identifier                            |
| Edm.Int16          | Signed 16-bit integer                                          |
| Edm.Int32          | Signed 32-bit integer                                          |
| Edm.Int64          | Signed 64-bit integer                                          |
| Edm.SByte          | Signed 8-bit integer                                           |
| Edm.Single         | IEEE 754 binary32 floating-point number (6-9 decimal digits)   |
| Edm.Stream         | Binary data stream                                             |
| Edm.String         | Sequence of characters                                         |
| Edm.TimeOfDay      | Clock time 00:00-23:59:59.999999999999                         |

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

Lodata supports [Collection](./collections) and [Enumeration](./enumerations) types.

## Type extensions

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

## Type discovery

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

## Open Types

Open entity types and open complex types allow properties to be added dynamically to instances of the open type. This
is useful when working with entity set types such as Redis or Mongo.

An entity type may indicate that it is open and allow clients to add properties dynamically to instances of the type
by specifying uniquely named property values in the payload used to insert or update an instance of the type.

Some entity types are open by default, such as `RedisEntityType` and `MongoEntityType`. Lodata supports changing
a property's open flag:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $type = new RedisEntityType('example');
        $type->setOpen(false);
        $set = new RedisEntitySet('examples', $type);
        Lodata::add($set);
    }
}
```

## Immutable properties

Lodata supports annotating a property as `Immutable` meaning that it can be provided as part of a create request, but
will be ignored during an update request.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entityType = new \Flat3\Lodata\EntityType('example');
        $entityType->setKey(new \Flat3\Lodata\DeclaredProperty('id', \Flat3\Lodata\Type::string()));
        $entityType->addDeclaredProperty('name', \Flat3\Lodata\Type::string());
        $dob = new \Flat3\Lodata\DeclaredProperty('dob', \Flat3\Lodata\Type::date());
        $dob->addAnnotation(new \Flat3\Lodata\Annotation\Core\V1\Immutable);
        $entityType->addProperty($dob);
        $entitySet = new \Flat3\Lodata\Drivers\CollectionEntitySet('examples', $entityType);
        $entitySet->setCollection($collection);
        \Lodata::add($entitySet);
    }
}
```