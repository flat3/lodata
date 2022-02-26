# Operations

OData's operations allow for completely customized interactions with the service, outside the data-driven CRUD capabilities
seen so far. Through operations, you can perform remote procedure calls that operate like static methods, or class instance methods on
entities and entity sets.

Lodata supports both [Functions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359009)
and [Actions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359013]). By OData
convention operations that define themselves as Functions MUST return data and MUST have no observable side effects, and Actions
MAY have side effects when invoked and MAY return data. Lodata does not enforce the side effect restriction, but does enforce the return
data requirement.

Lodata can expose operations on uninstantiated classes, class instances and anonymous functions.
If a class name is provided, the class will be resolved to an instance via the
Laravel [service container](https://laravel.com/docs/8.x/container) for every operation call. If an instance is
provided, it will be re-used for each invocation of the operation.

The simplest way to expose a method on a resolvable class is to decorate it with the `LodataFunction` or `LodataAction`
attribute, and then to use auto-discovery on the class. If using a version of PHP that does not support attributes,
or operations containing anonymous functions then operations can be created manually.

::: tip
As you implement these examples in your own application it is
useful to observe changes to the model in the CSDL [metadata documents](./README.md).
:::

In the examples that follow each has the code provided by a class using attributes, an alternative using manual setup
or an anonymous function, the request in cURL format, and the expected response.
All of these examples can be used with the same syntax for actions.

## Hello World!

Here is the simple 'Hello world!' example. We create a class with a method, decorate the method with the
`LodataFunction` attribute, and pass the class into discovery. The OData identifier is taken from the method name. The
inline example shows the same functionality by creating an `Operation` and attaching an anonymous function.

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    #[LodataFunction]
    public function hello() {
        return 'Hello world!';
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\Operation;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $hello = new Operation\Function_('hello');
        $hello->setCallable(function () {
            return 'Hello world!';
        });
        Lodata::add($hello);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/hello
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.String",
    "value": "Hello world!"
}
```
</code-block>
</code-group>

## Return types

If the return type of the method is void then Lodata assumes an `Edm.String` response.
To properly type a string response you can add the PHP return type `string`.

This method also works with `int` (converted to `Edm.Int64`), `float` (converted to `Edm.Double`), `bool`
(converted to `Edm.Boolean`) and `array` (converted to a collection).

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    #[LodataFunction]
    public function mol(): int {
        return 42;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\Operation;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $mol = new Operation\Function_('mol');
        $mol->setCallable(function (): int {
            return 42;
        });
        Lodata::add($mol);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/mol
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Int64",
    "value": 42
}
```
</code-block>
</code-group>

---

To use more specific OData types such as `Edm.DateTimeOffset` you can use Lodata's `Type` objects as return types:

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    #[LodataFunction]
    public function now(): Type\DateTimeOffset {
        return new Type\DateTimeOffset( Carbon::now() );
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Carbon\Carbon;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $now = new Operation\Function_('now');
        $now->setCallable(function (): Type\DateTimeOffset {
            return new Type\DateTimeOffset( Carbon::now() );
        });
        Lodata::add($now);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/now
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.DateTimeOffset",
    "value": "2021-07-22T17:36:16+00:00"
}
```
</code-block>
</code-group>

## Arguments

Arguments can be specified for the callback, and then supplied as key/value pairs in the URL (or the body for actions).

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    #[LodataFunction]
    public function add(int $a, int $b): int {
        return $a + $b;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\Operation;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (int $a, int $b): int {
            return $a + $b;
        });
        Lodata::add($add);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/add(a=3,b=4)
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Int64",
    "value": 7
}
```
</code-block>
</code-group>

---

More complex arguments using OData types can also be passed. Here we have an `Edm.DateTimeOffset`
and an `Edm.Duration`, where the duration is added to the timestamp, and the new value returned.

::: tip
Here the `get()` method
is used to return the `Carbon` object contained within the `DateTimeOffset`, and the `float` number of seconds within the
`Duration`.
:::

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;

class Example {
    #[LodataFunction]
    public function add(DateTimeOffset $timestamp, Duration $increment): DateTimeOffset {
        return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (DateTimeOffset $timestamp, Duration $increment): DateTimeOffset {
            return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
        });
        Lodata::add($add);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/add(timestamp=2004-01-01T12:00:00+00:00,increment=P12D) 
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.DateTimeOffset",
    "value": "2004-01-13T12:00:00+00:00"
}
```
</code-block>
</code-group>

## Nullable types

In the previous examples none of the arguments or the return types supported being null. You can type-hint that
an argument can be null. For `Action` operations, the return type can be nullable.

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    #[LodataFunction]
    public function add(?int $a, ?int $b): int {
        return (null === $a || null === $b) ? 0 : $a + $b;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\Operation;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (?int $a, ?int $b): int {
            return (null === $a || null === $b) ? 0 : $a + $b;
        });
        Lodata::add($add);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/add(a=3)
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Int64",
    "value": 0
}
```
</code-block>
</code-group>

## Bound parameters

Internally, OData URLs are resolved through [function composition](../internals/function-composition.md). This enables
the output of one operation to be passed into the input of the next operation, as the second operation's
"bound parameter".

In this example, the `DateTimeOffset` generated by the first function call is automatically inserted as the bound
parameter of the second function call.

<code-group>
<code-block title="Class">
```php
use Carbon\Carbon;
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;

class Example {
    #[LodataFunction]
    public function now(): DateTimeOffset {
        return new DateTimeOffset(Carbon::now()); 
    }

    #[LodataFunction(bind: "timestamp")]
    public function add(DateTimeOffset $timestamp, Duration $increment): DateTimeOffset {
        return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Example::class);
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Carbon\Carbon;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (DateTimeOffset $timestamp, Duration $increment): DateTimeOffset {
            return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
        })->setBindingParameterName('timestamp');
        Lodata::add($add);

        $now = new Operation\Function_('now');
        $now->setCallable(function (): DateTimeOffset {
            return new DateTimeOffset(Carbon::now()); 
        });
        Lodata::add($now);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/now()/add(increment=P12D)
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.DateTimeOffset",
    "value": "2021-08-03T19:33:33+00:00"
}
```
</code-block>
</code-group>

A further use of path composition is to pick a property from an entity and pass it into a function. For example
a function that took an `Edm.Date` as its bound property and returned the age of the person could be called as:
`http://localhost:8000/odata/people/1/dob/age()`.

## Binding entities

In addition to primitive types, entities picked from a set can be passed into a function. In this example a simple
collection is defined, and the URL picks the first one by its entity ID and passes it into the function using
its binding parameter.

<code-group>
<code-block title="Class">
```php
use Carbon\Carbon;
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class NextBirthday
{
    #[LodataFunction(bind: "person")]
    public function nb( Entity $person ): Type\Duration
    {
        /** @var Carbon $dob */
        $dob = $person['dob']->getPrimitiveValue();

        $now = Carbon::now();

        $diff = $now->diffInSeconds( $dob->year( date( 'Y' ) ), false );

        if ( $diff > 0 ) {
            return new Type\Duration( $diff );
        }

        return new Type\Duration( $now->diffInSeconds( $dob->year( date( 'Y' ) + 1 ), false ) );
    }
}

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $person = new EntityType( 'person' );
        $person->setKey( new DeclaredProperty( 'id', Type::int64() ) );
        $person->addDeclaredProperty( 'name', Type::string() );
        $person->addDeclaredProperty( 'dob', Type::date() );

        $people = new CollectionEntitySet( 'people', $person );
        $people->setCollection( collect( [
            [
                'name' => 'Michael Caine',
                'dob'  => '1933-03-14',
            ]
        ] ) );

        Lodata::add( $people );
        Lodata::discover( NextBirthday::class );
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Carbon\Carbon;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $person = new EntityType( 'person' );
        $person->setKey( new DeclaredProperty( 'id', Type::int64() ) );
        $person->addDeclaredProperty( 'name', Type::string() );
        $person->addDeclaredProperty( 'dob', Type::date() );

        $people = new CollectionEntitySet( 'people', $person );
        $people->setCollection( collect( [
            [
                'name' => 'Michael Caine',
                'dob'  => '1933-03-14',
            ]
        ] ) );

        Lodata::add( $people );

        $nb = new Operation\Function_('nb');
        $nb->setCallback(function( Entity $person ): Type\Duration {
            /** @var Carbon $dob */
            $dob = $person['dob']->getPrimitiveValue();

            $now = Carbon::now();

            $diff = $now->diffInSeconds( $dob->year( date( 'Y' ) ), false );

            if ( $diff > 0 ) {
                return new Type\Duration( $diff );
            }

            return new Type\Duration( $now->diffInSeconds( $dob->year( date( 'Y' ) + 1 ), false ) );
        })->setBindingParameterName('person');

        Lodata::add( $nb );
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/people/0/nb
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Duration",
    "value": "P234DT3H21M51S"
}
```
</code-block>
</code-group>

## Returning entities

Generated entities can also be returned from an operation. In this case the return type of `randomperson()` is `Entity`.
The returned entity type must be configured for OData to present it in the metadata document. This can be specified
in the attribute's `name` parameter. In this example a random person entity is created by the operation.

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class PersonGenerator
{
    #[LodataFunction(return: "person")]
    public function randomperson(): Entity
    {
        $person = \Lodata::getEntityType( 'person' );
        $entity = new Entity();
        $entity->setType( $person );

        $faker          = \Faker\Factory::create();
        $entity['name'] = $faker->name();
        $entity['dob']  = $faker->date();

        return $entity;
    }
}

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $person = new EntityType( 'person' );
        $person->setKey( new DeclaredProperty( 'id', Type::int64() ) );
        $person->addDeclaredProperty( 'name', Type::string() );
        $person->addDeclaredProperty( 'dob', Type::date() );

        \Lodata::add( $person );
        \Lodata::discover( PersonGenerator::class );
    }
}
```
</code-block>

<code-block title="Inline">
```php
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $person = new EntityType( 'person' );
        $person->setKey( new DeclaredProperty( 'id', Type::int64() ) );
        $person->addDeclaredProperty( 'name', Type::string() );
        $person->addDeclaredProperty( 'dob', Type::date() );
        \Lodata::add( $person );

        $generator = new Operation\Function_('randomperson');
        $generator->setCallback(function(): Entity {
            $person = \Lodata::getEntityType( 'person' );
            $entity = new Entity();
            $entity->setType( $person );

            $faker          = \Faker\Factory::create();
            $entity['name'] = $faker->name();
            $entity['dob']  = $faker->date();

            return $entity;
        })->setReturnType( $person );

        \Lodata::add( $generator );
    }
}
```
</code-block>

<code-block title="Request">
```
http://127.0.0.1:8000/odata/randomperson()
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://127.0.0.1:8000/odata/$metadata#com.example.odata.person",
    "name": "Jannie Harber",
    "dob": "1970-02-14"
}
```
</code-block>
</code-group>

## Parameter aliases

A parameter alias can be used in place of an inline parameter value. The value for the alias is specified as a
separate query option using the name of the parameter alias.

For example via the function import EmployeesByManager, passing 3 for the ManagerID parameter:

```
http://localhost:8000/odata/EmployeesByManager(ManagerID=@p1)?@p1=3
```

## Class instances

A class instance can be provided for the operation, and the same instance will be called with each invocation.

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;

class Example {
    protected $value = 0;

    #[LodataFunction]
    public function incr(): int {
        return ++$this->value;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $example = new Example();
        Lodata::discover($example);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/incr()
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Int64",
    "value": 1
}
```
</code-block>
</code-group>

## Namespacing

Operations can be namespaced to provide more organization for unbound functions and actions on the model.

<code-group>
<code-block title="Class">
```php
use Flat3\Lodata\Attributes\LodataFunction;
use Flat3\Lodata\Attributes\LodataNamespace;

#[LodataNamespace(name: "com.example.math")]
class Math {
    #[LodataFunction]
    public function add(int $a, int $b): int {
        return $a + $b;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Lodata::discover(Math::class);
    }
}
```
</code-block>

<code-block title="Request">
```
http://localhost:8000/odata/com.example.math.add(a=1,b=2)
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#Edm.Int64",
    "value": 3
}
```
</code-block>
</code-group>

## Transaction

To provide additional context to a Function that may require it, the Function can ask for the current Transaction by
adding that argument to the method. In this example the `example` method would receive the Transaction on the
`$transaction` parameter. The transaction contains all the available context for the request, and can provide
information such as the current system query options.

```php
public class MethodResponder {
    #[LodataFunction]
    public function example(Transaction $transaction): string
    {
      return $transaction->getRequest()->getMethod();
    }
}
```

## Upgrading from 3.x

Lodata 3 had a completely different way of describing operations, this section briefly describes a previous example
and the more simplified layout of the new example.

<code-group>
<code-block title="Lodata 3">
```php
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;
use Carbon\Carbon;

class Now extends Operation implements FunctionInterface
{
    public function invoke(): DateTimeOffset
    {
        return new DateTimeOffset(Carbon::now());
    }
}

class Add extends Operation implements FunctionInterface
{
    protected $bindingParameterName = 'timestamp';

    public function invoke(DateTimeOffset $timestamp, Duration $increment): DateTimeOffset
    {   
        return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new Add('add'));
        \Lodata::add(new Now('now'));
    }
}
```
</code-block>

<code-block title="Lodata 4">
```php
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;
use Carbon\Carbon;

class TimeCalculation {
    #[LodataFunction]
    public function now(): DateTimeOffset
    {
        return new DateTimeOffset(Carbon::now());
    }
    
    #[LodataFunction(bind: "timestamp")]
    public function add(DateTimeOffset $timestamp, Duration $increment): DateTimeOffset
    {   
        return new DateTimeOffset($timestamp->get()->addSeconds($increment->get()));
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::discover(TimeCalculation::class);
    }
}
```
</code-block>
</code-group>