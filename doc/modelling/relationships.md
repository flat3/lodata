# Relationships

OData describes properties that allow navigation to related entities. These are powerful, but are more complicated to
set up than other modelling activities. If you're using Eloquent entity sets then most of the time this can be
automatically configured via discovery.

In this example we have two related entity types (and sets). A collection of people, and their pets. One person has
many pets

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /* Step 1 - Create the standard types and sets */
       
        // Create the source entity type
        $person = EntityType::factory('person')
            ->setKey(new DeclaredProperty('id', Type::int32())) // Primary key
            ->addDeclaredProperty('name', Type::string());

        // Create the related entity type
        $pet = EntityType::factory('pet')
            ->setKey(new DeclaredProperty('id', Type::int32())) // Primary key
            ->addDeclaredProperty('name', Type::string())
            ->addDeclaredProperty('owner_id', Type::int32()); // Foreign key

        // Add the entity types to the model
        Lodata::add($person);
        Lodata::add($pet);

        // Create the entity sets
        $pets = SQLEntitySet::factory('pets', $pet);
        $people = SQLEntitySet::factory('people', $person);

        // Add the entity sets to the model
        Lodata::add($people);
        Lodata::add($pets);

        /* Step 2: Create the navigation property on the source type */

        // Create the referential constraint (optional)
        $petPersonConstraint = new ReferentialConstraint(
            $person->getProperty('id'),   // Primary property
            $pet->getProperty('owner_id') // Referenced property
        );

        // Create the navigation property
        $toPet = new NavigationProperty(
            'pets', // Target entity set
            $pet          // Target entity type
        );
        $toPet->setCollection(true);        // Navigates to a collection of entities
        $toPet->addConstraint($petPersonConstraint); // Use this constraint

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

### Bindings
### Navigation properties
### Referential constraints
