# Generated properties

As well as the data retrieved from the data source, Lodata can add properties to an entity that are generated at
runtime.

Lodata provides the `\Flat3\Lodata\GeneratedProperty` class which must be extended and provided with an `invoke()` method
which will receive the `\Flat3\Lodata\Entity` currently being generated.

The generated property must return a PHP type or an instance of a primitive type. The resulting instance of the custom generated property
is added to the entity type.

This example creates and attaches a generated property named `eman` with the type `Edm.String` on the `person` entity type.
This property will be represented in the metadata alongside the other declared properties.

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\GeneratedProperty;
use Flat3\Lodata\Type;
use Illuminate\Support\ServiceProvider;

class RandomisedName extends GeneratedProperty
{
    public function invoke( Entity $entity )
    {
        return str_shuffle( $entity['name']->getValue() );
    }
}

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $person = new EntityType( 'person' );
        $person->addDeclaredProperty( 'name', Type::string() );
        $person->addProperty( new RandomisedName( 'eman', Type::string() ) );

        $people = new CollectionEntitySet( 'people', $person );
        $people->setCollection( collect( [
            [
                'name' => 'Michael Caine',
            ],
            [
                'name' => 'Bob Hoskins',
            ],
        ] ) );

        \Lodata::add( $people );
    }
}
```
</code-block>

<code-block title="Request">
```
http://127.0.0.1:8000/odata/people
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://127.0.0.1:8000/odata/$metadata#people",
    "value": [
        {
            "name": "Michael Caine",
            "eman": "aheainlM icCe"
        },
        {
            "name": "Bob Hoskins",
            "eman": "okBHbins so"
        }
    ]
}
```
</code-block>
</code-group>