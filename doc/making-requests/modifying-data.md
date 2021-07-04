# Modifying

Updatable OData services support Create, Update and Delete operation for some or all exposed entities.

## Create an Entity

To create an entity in a collection, the client sends a POST request to that collection's URL.
The POST body MUST contain a single valid entity representation. The request below creates a Person.

<code-group>
<code-block title="Request">
```uri
POST http://localhost:8000/odata/People
{
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com"
}
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#People/$entity",
    "id": 5,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com"
}
```
</code-block>
</code-group>

## Remove an Entity

The request below deletes the Person with ID 5.

<code-group>
<code-block title="Request">
```uri
DELETE http://localhost:8000/odata/People/5
```
</code-block>

<code-block title="Response">
```
HTTP/1.1 206 No Content
```
</code-block>
</code-group>

### Update an Entity

Lodata supports PATCH to update an entity. The request below updates the Email of a person.

<code-group>
<code-block title="Request">
```uri
PATCH http://localhost:8000/odata/People/6
{
    "Email": "jason.bourne@hotmail.com"
}
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://127.0.0.1:8000/odata/$metadata#People/$entity",
    "id": 6,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@hotmail.com"
}
```
</code-block>
</code-group>

## Create Related Entities

Related entities can be created at the same time as an entity. This is described as a deep insert.

<code-group>
<code-block title="Request">
```uri
POST http://localhost:8000/odata/People
{
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com",
    "pets": [
        {
            "Name": "Jack The Dog"
        }
    ]
}
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://127.0.0.1:8000/odata/$metadata#People/$entity",
    "id": 8,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com",
    "pets": [
        {
            "id": 5,
            "Name": "Jack The Dog",
            "owner_id": 8
        }
    ]
}
```
</code-block>
</code-group>
