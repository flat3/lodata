# Eloquent

Lodata can 'discover' Eloquent model properties, the relationships between the models, and expose methods as operations
on the models.

::: tip
The Eloquent driver extends the SQL driver, so the same `$filter` capabilities exist in both.
:::

## Data model

Discovery of the data model is
performed first using [DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.12/index.html) to
introspect the database table, then [Eloquent casts](https://laravel.com/docs/8.x/eloquent-mutators#custom-casts)
are used for further type specification. During requests, the Eloquent model getter/setter functions are used
to refer to the properties, so any additional field processing being performed by the model will be preserved.

To discover a model the `Lodata` facade can be used. For example to discover the `Flight` model:

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::discover(\App\Models\Flight::class);
    }
}
```

## Attributes

When using discovery Lodata will introspect the database schema to discover basic properties and types.
For more control over exactly how entity sets, types and properties are discovered you can use PHP attributes on the
model class. When you attach the first _property_ attribute, the automatic discovery of attributes is disabled.

Attributes all use the `Flat3\Lodata\Attributes` namespace.
Property attributes optionally support providing a `source` to map an OData property name to a different
underlying field name.

Available attributes include:

* Primitive [types](../types/README.md) such as `LodataBinary`, `LodataString`.
* Collections (`LodataCollection`), that support attaching an underlying type.
* Enumerations (`LodataEnum`), that support an attached enum type such as a PHP backed enum.
* `LodataIdentifier` to customize the entity set name.
* `LodataTypeIdentifier` to customize the entity type name.

```php
use App\Enums\Colours;
use Flat3\Lodata\Attributes\LodataCollection;
use Flat3\Lodata\Attributes\LodataDate;
use Flat3\Lodata\Attributes\LodataDuration;
use Flat3\Lodata\Attributes\LodataEnum;
use Flat3\Lodata\Attributes\LodataIdentifier;
use Flat3\Lodata\Attributes\LodataTypeIdentifier;
use Flat3\Lodata\Type\SByte;
use Illuminate\Database\Eloquent\Model;

#[
    LodataIdentifier('flights'),
    LodataTypeIdentifier('flight'),
    LodataCollection(name: 'Items', underlyingType: SByte::class, source: 'items'),
    LodataDate(name: 'OpenTime', source: 'open_time'),
    LodataEnum(name: 'Colours', enum: Colours::class),
    LodataDuration(name: 'Duration', source: 'timeDuration'),
]
class Flight extends Model
{
}
```

## Relationships

If model `Flight` has a method `passengers` that returns a
[relationship](https://laravel.com/docs/8.x/eloquent-relationships) to `Passenger` such as hasOne, hasMany,
hasManyThrough, this can be discovered by Lodata as a navigation property on the `Flights` entity set. This method
can be tagged using an attribute that will be picked up during discovery.

::: tip
The same as Laravel itself, Lodata typically refers to 'entity types' in the singular form and 'entity sets' in
the plural form.
:::

```php
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    #[LodataRelationship]
    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }
}
```

Alternatively for PHP versions that do not support attributes:

```php
\Lodata::getEntitySet('Flights')
  ->discoverRelationship('passengers')
```

A navigation property now exists in the `Flight` entity set for `Passengers`. This enables the client to
navigate by using the navigation property in a URL similar to
[`http://127.0.0.1:8000/odata/Flights(1)/passengers`](http://127.0.0.1:8000/odata/Flights(1)/passengers)
to choose the flight with ID 1, and to get the passengers related to this flight. This navigation property can
also be used in `$expand` requests.

If Lodata is able to determine the relationship cardinality it will be represented in the service metadata
document.

You can customize the property name, description and whether it can be made null using attribute arguments:

```php
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    #[LodataRelationship(name: 'Travelers', description: 'Navigate to travelers': nullable: true)]
    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }
}
```

The `discoverRelationship` method supports the same arguments as the attribute:

```php
\Lodata::getEntitySet('Flights')
  ->discoverRelationship('passengers', 'Travelers', 'Navigate to travelers', true)
```

## Operations

Lodata can also expose model methods as operations. The method will be called on the specific instance of the entity
referenced in the URL.

<code-group>
<code-block title="Code">
```php
use Carbon\Carbon;
use Flat3\Lodata\Attributes\LodataFunction;

class Person extends Model {
    #[LodataFunction]
    public function age(): float {
        return Carbon::parse($this->birthdate)->age;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Person::class);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/Person(1)/age()
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Double",
    "value": 52.2
}
```
</code-block>
</code-group>

## Enumerated types

Since Laravel 9 and PHP 8.1 there exists support for `BackedEnum` casts in Eloquent models. For these to work
in Lodata, they must be integer backed enums and if using flags the integers must be powers of two (1,2,4,8 etc.) so
that the bitwise logic will work correctly.

## Collections

Eloquent supports using `array` as a cast. Attributes with this cast will be automatically interpreted as collections
using the `Edm.String` underlying type. The underlying type can be overridden after discovery.

## Repository

Some Laravel applications implement a [repository](https://www.twilio.com/blog/repository-pattern-in-laravel-application)
pattern for handling data access. Lodata can support this technique if the repository implements the
`RepositoryInterface` so that it can retrieve the correct model class name using the `getClass` method. For
invocation Lodata will pass the model instance as the bound parameter.

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Interfaces\RepositoryInterface;

class Repository implements RepositoryInterface
{
    public function getClass(): string
    {
        return Airport::class;
    }

    #[LodataFunction (bind: "airport")]
    public function code(Airport $airport, ?string $suffix): string
    {
        return $airport->code.($suffix ?: '');
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Repository::class);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/Airports(1)/code(suffix='abc')
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.String",
    "value": "lhrabc"
}
```
</code-block>
</code-group>
