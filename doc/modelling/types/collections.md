# Collections

Lodata implements [Collections](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_CollectionofPrimitiveValues)
of primitive and complex types.

Collections are defined through the `Type::collection()` definition. The underlying type is `Edm.Untyped` by default,
allowing any primitive or complex entries. To set a more specific underlying type, the type definition can be supplied
as an argument to `Type::collection()`.

```php
$person = (new EntityType( 'person' ))
  ->addDeclaredProperty( 'name', Type::string() );
  ->addDeclaredProperty( 'emails', Type::collection() );
  ->addDeclaredProperty( 'scores', Type::collection(Type::decimal()) );
```

Collection instances implement the `Arrayable` interface, so they can be easily built at runtime in singletons or
operations.

<code-group>
<code-block title="Code">
```php
$type = new EntityType('person');
$type->addDeclaredProperty('emails', Type::collection(Type::string()));
$entity = new Singleton('Person', $type);
$entity['emails'] = [
  'test@example.com',
  'test@gmail.com',
];
Lodata::add($entity);
```
</code-block>

<code-block title="Request">
```uri
GET http://localhost:8000/odata/Person
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost/odata/$metadata#person",
    "emails": [
        "test@example.com",
        "test@gmail.com"
    ]
}
```
</code-block>
</code-group>
