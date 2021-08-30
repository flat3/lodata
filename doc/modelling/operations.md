# Operations

OData's operations allow for completely customized interactions with the service, outside the data-driven CRUD capabilities
seen so far. Through operations, you can perform remote procedure calls that operate like static methods, or class instance methods on
entities and entity sets.

Lodata supports both [Functions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359009)
and [Actions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359013]). By OData
convention operations that define themselves as Functions MUST return data and MUST have no observable side effects, and Actions
MAY have side effects when invoked and MAY return data. Lodata does not enforce the side effect restriction, but does enforce the return
data requirement.

::: tip
As you implement these examples in your own application it is
useful to observe changes to the model in the CSDL [metadata documents](./README.md).
:::

In the examples that follow each has the code, the request in cURL format, and the expected response. All of these
examples can be used with the same syntax for actions, but should implement the `ActionInterface`.

## Hello World!

Here is the 'Hello world!' example. It is a class that extends `Operation`, and defines as a `Function` by implementing the `FunctionInterface`.
It implements the required `invoke()` method. The class uses the identifier `hello` when being added to the model.

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;

class HelloWorld extends Operation implements FunctionInterface
{
    public function invoke()
    {
        return 'Hello world!';
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new HelloWorld('hello'));
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

If the return type of the `invoke()` method is void then Lodata assumes an `Edm.String` response.
To properly type a string response you can add the PHP return type `string`.

This method also works with `int` (converted to `Edm.Int64`), `float` (converted to `Edm.Double`) and `bool`
(converted to `Edm.Boolean`):

<code-group>
<code-block title="Code">
```php
use Carbon\Carbon;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;

class MeaningOfLife extends Operation implements FunctionInterface
{
    public function invoke(): int
    {
        return 42;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new MeaningOfLife('mol'));
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
<code-block title="Code">
```php
use Carbon\Carbon;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;

class CurrentTime extends Operation implements FunctionInterface
{
    public function invoke(): DateTimeOffset
    {
        return new DateTimeOffset( Carbon::now() );
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new CurrentTime('now'));
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

Arguments can be specified in the definition of `invoke()`, and then supplied as key/value pairs in the URL:

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;

class Add extends Operation implements FunctionInterface
{
    public function invoke(int $a, int $b): int
    {
        return $a + $b;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new Add('add'));
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
<code-block title="Code">
```php
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Duration;

class Add extends Operation implements FunctionInterface
{
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
<code-block title="Code">
```php
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;

class Add extends Operation implements FunctionInterface
{
    public function invoke(?int $a, ?int $b): int
    {
        return (null === $a || null === $b) ? 0 : $a + $b;
    }
}

class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Lodata::add(new Add('add'));
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
<code-block title="Code">
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
<code-block title="Code">
```php
use Carbon\Carbon;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Duration;
use Illuminate\Support\ServiceProvider;

class NextBirthday extends Operation implements FunctionInterface
{
    protected $bindingParameterName = 'person';

    public function invoke( Entity $person ): Duration
    {
        /** @var Carbon $dob */
        $dob = $person['dob']->getPrimitiveValue();

        $now = Carbon::now();

        $diff = $now->diffInSeconds( $dob->year( date( 'Y' ) ), false );

        if ( $diff > 0 ) {
            return new Duration( $diff );
        }

        return new Duration( $now->diffInSeconds( $dob->year( date( 'Y' ) + 1 ), false ) );
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
        Lodata::add( new NextBirthday( 'nb' ) );
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

Generated entities can also be returned from an operation. In this case the return type of `invoke()` is `Entity`.
The returned entity type must be configured for OData to present it in the metadata document. This can be done
in the constructor of the `Operation`. In this example a random person entity is created by the operation. This
example also shows the identifier of the operation being configured in the constructor.

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class PersonGenerator extends Operation implements FunctionInterface
{
    public function __construct() {
        parent::__construct( 'randomperson' );
        $this->setReturnType( \Lodata::getEntityType( 'person' ) );
    }

    public function invoke(): Entity
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
        \Lodata::add( new PersonGenerator() );
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

## Creating using artisan

Actions and Function classes can be generated through `artisan` commands, and can be added to Lodata in the `boot()`
method of a service provider:

```sh
php artisan lodata:function Add
```
```sh
php artisan lodata:action Subtract
```

## Transaction

To provide additional context to a Function that may require it, the Function can ask for the current Transaction by adding that
argument to the invoke method. In this example the invoke method would receive the Transaction on the `$transaction` method
parameter. The transaction contains all the available context for the request, and can provide items such as the current system
query options.

```php
public class MethodResponder extends Operation implements FunctionInterface {
    public function invoke(Transaction $transaction): string
    {
      return $transaction->getRequest()->getMethod();
    }
}
```