# Generated properties

As well as the "static" data retrieved from the database, Lodata can add properties to an entity that are generated at
runtime.
Lodata provides the `\Flat3\Lodata\GeneratedProperty` class which must be extended and provided with an `invoke()` method
which will receive the `\Flat3\Lodata\Entity` currently being generated. The generated property must return an instance
of a primitive type. The resulting instance of the custom generated property can then be added to the entity type.

This example creates and attaches a generated property named `cp` with the type `int32` on the `airport` entity type
as an anonymous class. This property will be represented in the metadata alongside the other declared properties.

```
$airport = Lodata::getEntityType('airport');

$property = new class('cp', Type::int32()) extends GeneratedProperty {
    public function invoke(Entity $entity)
    {
        return new Int32(4);
    }
};

$airport->addProperty($property);
```
