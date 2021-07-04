# Annotations

OData allows the creation of annotations on the schema. Annotations are classes that extend `\Flat3\Lodata\Annotation`
and are added to the model with `Lodata::add($annotation)`, or entity set types with `EntitySet::addAnnotation($annotation)`,
or entity type properties with `Property::addAnnotation($annotation)`.
Examples are in the `\Flat3\Lodata\Annotation` namespace.