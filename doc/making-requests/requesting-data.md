# Requesting

OData services support requests for data via HTTP `GET` requests.

## Entity Collections

Requesting entity sets, and entities from the set, are the two most common OData requests:

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://127.0.0.1:8000/odata/$metadata#People",
  "value": [
    {
      "id": "michael-caine",
      "name": "Michael Caine"
    },
    {
      "id": "bob-hoskins",
      "name": "Bob Hoskins"
    }
  ]
}
```
</code-block>
</code-group>

OData URLs are case-sensitive, make sure you're using the right casing
for your entity sets!

## Individual Entity by ID

The requests below return an individual entity of type Person by the given ID 'michael-caine'.

The first uses the standard OData syntax where the key is provided inside parentheses.
The key must be provided as the correct type, in this case it is a string.

The second shows OData's key-as-segment convention, allowing the key to be provided as a path segment.
The provided key is type-coerced into the correct format when this syntax is used.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People('michael-caine')
GET http://localhost:8000/odata/People/michael-caine
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People/$entity",
  "id": "michael-caine",
  "name": "Michael Caine"
}
```
</code-block>
</code-group>

## Individual Property

To address an entity property clients append a path segment containing property name to the URL of the entity.
If the property has a complex type, properties of that value can be addressed by further property name composition.
First let's take a look at how to get a simple property. The request below returns the Name property of a Person.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/michael-caine/name
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People('michael-caine')/name",
  "value": "Michael Caine"
}
```
</code-block>
</code-group>

Then let's see how to get a property value of a complex type. The request below returns the Address of the complex type
Location of a Person.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/michael-caine/location/address
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People('michael-caine')/location/address",
  "value": "4 Hello Avenue"
}
```
</code-block>
</code-group>

## Individual Property Raw Value

To address the raw value of a primitive property, clients append a path segment containing the string `$value` to
the property URL. The request below returns the raw value of property Name of a Person.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/michael-caine/name/$value
```
</code-block>

<code-block title="Response">
```text
Michael Caine
```
</code-block>
</code-group>
