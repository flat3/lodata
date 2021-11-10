# Relationships

## Navigation properties

OData describes [Navigation Properties](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530365)
that allow URL navigation from one entity to its related entities.

The metadata document contains representations of navigation properties, and the relationships between types and sets, so that
clients can discover these capabilities. These are powerful features, but are a little more complicated to set up than
other modelling activities.

::: tip
If you're using [Eloquent entity sets](./drivers/eloquent.md) then most of the time these relationships can be automatically configured via discovery.
:::

In the following example we have two related entity types (and sets).
A collection of people and their pets. In this case one person has many pets.

This allows you to make a request similar to:
[`http://localhost:8000/odata/people/4/pets`](http://localhost:8000/odata/people/4/pets),
which will return the collection of pets owned by the person with ID 4.
In this case `pets` is the name of the navigation property on the `person` entity type.

::: tip
As you implement this example in your own application it is
useful to observe changes to the model in the CSDL [metadata documents](./README.md).
:::

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /* Step 1 - Create the standard types and sets */
       
        // Create the source entity type
        $person = new EntityType('person')
            ->setKey(new DeclaredProperty('id', Type::int32())) // Primary key
            ->addDeclaredProperty('name', Type::string());

        // Create the related entity type
        $pet = new EntityType('pet')
            ->setKey(new DeclaredProperty('id', Type::int32())) // Primary key
            ->addDeclaredProperty('name', Type::string())
            ->addDeclaredProperty('owner_id', Type::int32());   // Foreign key

        // Add the entity types to the model
        Lodata::add($person);
        Lodata::add($pet);

        // Create the entity sets
        $pets = new SQLEntitySet('pets', $pet);
        $people = new SQLEntitySet('people', $person);

        // Add the entity sets to the model
        Lodata::add($people);
        Lodata::add($pets);

        /* Step 2: Create the navigation property on the source type */

        // Create the navigation property
        $toPet = new NavigationProperty(
            'pets', // Navigation property name
            $pet    // Target entity type
        );
        $toPet->setCollection(true); // Navigates to a collection of entities

        // Add the navigation property to the source entity type
        $person->addProperty($toPet);

        /* Step 3: Create a binding between the two entity sets */

        // Create a binding between the navigation property and its target entity set
        $binding = new NavigationBinding(
            $toPet, // Navigation property
            $pets   // Target entity set
        );

        // Attach the binding to the source entity set
        $people->addNavigationBinding($binding);
    }
}
```

## Constraints

OData supports advertising the referential constraint between two properties, as a type of annotation of the navigation property.
This is commonly needed for tools that interpret the OData feeds as relational models.

```php
$petPersonConstraint = new ReferentialConstraint(
    $person->getProperty('id'),   // Primary property
    $pet->getProperty('owner_id') // Referenced property
);
$toPet->addConstraint($petPersonConstraint); // Use this constraint
```

## Partners

OData also supports combining the definition of two bindings in a "partner" configuration. This is used by the
client to navigate in both directions on a single relationship.

```php
// Create one navigation property
$toPet = new NavigationProperty(
    'pets', // Navigation property name
    $pet    // Target entity type
);

$toPet->setCollection(true);

// Create the reverse navigation property
$toPerson = new NavigationProperty(
    'person', // Navigation property name
    $person   // Target entity type
);

// Set the properties as partners
$toPet->setPartner($toPerson);
$toPerson->setPartner($toPet);
```