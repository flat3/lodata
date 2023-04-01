# Entity Data Model

This section provides a high-level description of the Entity Data Model (EDM): the abstract data model that is used to
describe the data exposed by an OData service. An OData Metadata Document is a representation of a service's data model
exposed for client consumption.

You can request the Metadata Document as:
- CSDL JSON:
  [`http://127.0.0.1:8000/odata/$metadata?$format=json`](http://127.0.0.1:8000/odata/$metadata?$format=json)
- CSDL XML:
  [`http://127.0.0.1:8000/odata/$metadata?$format=xml`](http://127.0.0.1:8000/odata/$metadata?$format=xml)
- OpenAPI v3:
  [`http://127.0.0.1:8000/odata/openapi.json`](http://127.0.0.1:8000/odata/openapi.json)

The central concepts in the EDM are **entities, relationships, entity sets, actions, and functions**.

::: tip
All of these concepts exist as PHP classes in the root of the `\Flat3\Lodata` namespace. For example,
`EntitySet`, `EntityType` and `Operation`. Create instances of these
classes and use `\Lodata::add()` to construct your model.
:::

**Entities** are instances of entity types (eg Customer, Employee, etc.). Similar to Eloquent model instances.

**Entity types** are named structured types with a key. They define the named properties and relationships of an entity.
Entity types may derive by single inheritance from other entity types. Similar to an Eloquent model class definition,
combined with a Blueprint migration.

A single primitive property of the entity type specifies the key that uniquely identifies it
(eg CustomerId, OrderId, LineId, etc.).

**Complex** types are keyless named structured types consisting of a set of properties. These are value types whose instances
cannot be referenced outside of their containing entity. Complex types are commonly used as property values in an entity
or as parameters to operations.

**Properties** declared as part of a structured type's definition are called **declared properties**. Instances of structured
types may contain additional undeclared **dynamic properties**. A dynamic property cannot have the same name as a declared
property. Entity or complex types which allow clients to persist additional undeclared properties are called open types.

Relationships from one entity to another are represented as **navigation properties**. Navigation properties are generally
defined as part of an entity type, but can also appear on entity instances as undeclared dynamic navigation properties.
Each relationship has a cardinality.

Enumeration types are named primitive types whose values are named constants with underlying integer values.

[Type definitions](./types/README.md) are named primitive types with fixed facet values such as maximum length or precision. Type definitions
can be used in place of primitive typed properties, for example, within property definitions.

[Entity sets](./drivers/README.md) are named collections of entities (eg Customers is an entity set containing Customer entities, following
the Laravel naming convention). An
entity's key uniquely identifies the entity within an entity set. 
Entity sets provide
entry points into the data model. Lodata refers to specific implementations of backend data sources such as Eloquent and Redis as
[entity set drivers](./drivers/README.md).

[Operations](./operations.md) allow the execution of custom logic on parts of a data model. Functions are operations that do not have
side effects and may support further composition, for example, with additional filter operations, functions or an
action. Actions are operations that allow side effects, such as data modification, and cannot be further composed in
order to avoid non-deterministic behavior. Actions and functions are either bound to a type, enabling them to be
called as members of an instance of that type, or unbound, in which case they are called as static operations.
Action imports and function imports enable unbound actions and functions to be called from the service root.

[Singletons](./singletons.md) are named entities which can be accessed as direct children of the entity container. A singleton may also
be a member of an entity set.

An OData resource is anything in the model that can be addressed (an entity set, entity, property, or operation).