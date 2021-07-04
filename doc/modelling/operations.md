# Operations

Lodata supports both [Functions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359009)
and [Actions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359013]). By OData
convention operations that define themselves as Functions MUST return data and MUST have no observable side effects, and Actions
MAY have side effects when invoked and MAY return data. Lodata does not enforce the side-effect restriction, but does enforce the return
data requirement.

Actions and Function classes can be generated through `artisan` commands, and can then be added to Lodata in the `boot()` method of a service provider:

```sh
php artisan lodata:function Add
php artisan lodata:action Subtract
```

Operations extend the `\Flat3\Lodata\Operation` class, and implement one of the `\Flat3\Lodata\Interfaces\Operation\ActionInterface` or
`\Flat3\Lodata\Interfaces\Operation\FunctionInterface` interfaces. The class must also implement an `invoke()` method, which takes
primitive type parameters. These parameter types and names will be read through [PHP reflection](https://www.php.net/manual/en/book.reflection.php)
and added to the metadata document.

The class can optionally define the name to use for the binding parameter using the `bindingParameterName` property
and the returnType using the `returnType` property during construction. A primitive return type will be resolved through reflection
on the invoke() method. When returning an entity the entity type must be attached using the setReturnType method, and the invoke method
should return Entity.

This Function defined as an anonymous class instance does not receive any parameters, and has a primitive return type of Edm.String
resolved through reflection. This function can be invoked via `http://localhost/odata/helloworld()`

```php
class HelloWorld extends Operation implements FunctionInterface {
    public function invoke(): string
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

To simplify the above, the Function could also set its return type to the PHP type `string`, and return a basic string.
This would be internally converted into an OData `Edm.String`.

This method also works with `int` (converted to `Edm.Int64`), `float` (converted to `Edm.Double`) and `bool`
(converted to `Edm.Boolean`).

In these cases the returned value is coerced into the correct OData type if required.

```
Lodata::add((new class('helloworld') extends Operation implements FunctionInterface {
    public function invoke(): string
    {
        return 'hello world';
    }
}));
```

This Function receives two Edm.String parameters, and returns an Edm.String that concatenates them. The names of the parameters
and their types are resolved through reflection. This function can be invoked via `http://localhost/odata/concat(one='hello',two='world')`

```
Lodata::add((new class('concat') extends Operation implements FunctionInterface {
    public function invoke(String_ $one, String_ $two): String_
    {
        return new String_($one->get().$two->get());
    }
}));
```

As with return types, PHP typed arguments can also be used in place of the strict OData types:

```
Lodata::add((new class('concat') extends Operation implements FunctionInterface {
    public function invoke(string $one, string $two): string
    {
        return $one . $two;
    }
}));
```

This Function requests that the bound parameter be provided as the 'code' parameter to the method, and sends it back unmodified.
This can be invoked via a URL for example `http://localhost/odata/Airports(1)/code/identity()`.

```
Lodata::add((new class('identity') extends Operation implements FunctionInterface {
    public function invoke(String_ $code): String_
    {
      return $code;
    }
}))->setBindingParameterName('code');
```

This Function requests the bound parameter be provided as the 'entity' parameter to the method, and additionally defines a provided
parameter 'prefix' and then returns an Edm.String.
This can be invoked via a URL for example `http://localhost/odata/Airports(1)/codeprefix(prefix='example')`.

```
Lodata::add((new class('codeprefix') extends Operation implements FunctionInterface {
    public function invoke(Entity $entity, String_ $prefix): String_
    {
      return $prefix->get() . $entity->code->get();
    }
}))->setBindingParameterName('entity');
```

Finally, entities can themselves be generated and returned. This Function requests the bound parameter be provided as the `text`s
parameter, and indicates that it returns an Entity. Because the entity type cannot be determined through reflection, it must be
explicitly pulled from the model and provided to the operation.
This can be invoked using a URL for example `http://localhost/odata/Airports/egen()` which would provide the `Airports` entity set
to the `egen` function as the bound parameter.

```
Lodata::add((new class('egen') extends Operation implements FunctionInterface {
    public function invoke(EntitySet $texts): Entity
    {
        $entity = $texts->makeEntity();
        $entity['code'] = new String_('example');
        return $entity;
    }
})->setBindingParameterName('texts')->setReturnType(Lodata::getEntityType('text')));
```

To provide additional context to a Function that may require it, the Function can ask for the current Transaction by adding that
argument to the invoke method. In this example the invoke method would receive the Transaction on the `$transaction` method
parameter. The transaction contains all of the available context for the request, and can provide items such as the current system
query options.

```
Lodata::add((new class('hello') extends Operation implements FunctionInterface {
    public function invoke(Transaction $transaction): String_
    {
      return new String_('hello');
    }
});
```

All of the above techniques also apply to Action operations.

## Creating using console
## Argument types

## Return types


Binding an Operation to a Resource
Applying an Action to Members of a Collection
Advertising Available Operations within a Payload
Invoking a Function
Inline Parameter Syntax
Invoking an Action
