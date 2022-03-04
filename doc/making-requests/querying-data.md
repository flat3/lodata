# Querying

OData supports various kinds of query options for querying data. This section will help you go through the common
scenarios for these query options.

## $filter

The `$filter` system query option allows clients to filter a collection of resources that are addressed by a request URL.
The expression specified with `$filter` is evaluated for each resource in the collection, and only items where the
expression evaluates to true are included in the response.

There are several kinds of basic predicates and built-in functions for `$filter`, including logical operators and
arithmetic operators. For more detailed information, please refer to the [OData protocol](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_SystemQueryOptionfilter) document.
The request below uses `$filter` to get people with FirstName "Scott".

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$filter=FirstName eq 'Scott'
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com"
    },
    {
      "id": 2,
      "FirstName": "Scott",
      "LastName": "Yammo",
      "Email": "scott.yammo@gmail.com"
    }
  ]
}
```
</code-block>
</code-group>

`$filter` can also be used as a path segment, and used multiple times to sequentially add filter parameters.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/$filter(@a)/$filter(@b)?@a=DOB gt 2000-01-01&@b=endswith(FirstName, 'tt')
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com",
      "DOB": "2001-01-01"
    }
  ]
}
```
</code-block>
</code-group>

## $orderby

The `$orderby` system query option allows clients to request resources in either ascending order using asc or descending
order using desc. If asc or desc not specified, then the resources will be ordered in ascending order. The request
below orders People on property LastName in descending order.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$orderby=LastName
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com"
    },
    {
      "id": 3,
      "FirstName": "Michael",
      "LastName": "Scott",
      "Email": "michael.scott@hotmail.com"
    },
    {
      "id": 2,
      "FirstName": "Scott",
      "LastName": "Yammo",
      "Email": "scott.yammo@gmail.com"
    }
  ]
}
```
</code-block>
</code-group>

## $top and $skip

The `$top` system query option requests the number of items in the queried collection to be included in the result.
The `$skip` query option requests the number of items in the queried collection that are to be skipped and not included
in the result.
The request below returns the first two people of the People entity set.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$top=2
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com"
    },
    {
      "id": 2,
      "FirstName": "Scott",
      "LastName": "Yammo",
      "Email": "scott.yammo@gmail.com"
    }
  ],
  "@nextLink": "http://localhost:8000/odata/People?top=2&skip=2"
}
```
</code-block>
</code-group>

The request below returns people starting with the 2nd person of the entity set People

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$skip=1
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 2,
      "FirstName": "Scott",
      "LastName": "Yammo",
      "Email": "scott.yammo@gmail.com"
    },
    {
      "id": 3,
      "FirstName": "Michael",
      "LastName": "Scott",
      "Email": "michael.scott@hotmail.com"
    }
  ]
}
```
</code-block>
</code-group>

## $count

The `$count` system query option allows clients to request a count of the matching resources included with the
resources in the response.
The request below returns the total number of people in the collection.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$count=true
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com"
    },
    {
      "id": 2,
      "FirstName": "Scott",
      "LastName": "Yammo",
      "Email": "scott.yammo@gmail.com"
    },
    {
      "id": 3,
      "FirstName": "Michael",
      "LastName": "Scott",
      "Email": "michael.scott@hotmail.com"
    }
  ],
  "@count": 3
}
```
</code-block>
</code-group>

## $expand

The `$expand` system query option specifies the related resources to be included in line with retrieved resources.
The request below returns people with navigation property Passengers of a Flight

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/flights?$expand=passengers
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#flights(passengers())",
  "value": [
    {
      "id": 1,
      "origin": "lhr",
      "destination": "lax",
      "gate": null,
      "duration": "PT11H25M0S",
      "passengers": [
        {
          "id": 1,
          "name": "Anne Arbor",
          "flight_id": 1
        },
        {
          "id": 2,
          "name": "Bob Barry",
          "flight_id": 1
        },
        {
          "id": 3,
          "name": "Charlie Carrot",
          "flight_id": 1
        }
      ]
    },
    {
      "id": 2,
      "origin": "sam",
      "destination": "rgr",
      "gate": null,
      "duration": "PT39M44S",
      "passengers": [
        {
          "id": 4,
          "name": "Fox Flipper",
          "flight_id": 2
        }
      ]
    },
    {
      "id": 3,
      "origin": "sfo",
      "destination": "lax",
      "gate": null,
      "duration": "PT35M33S",
      "passengers": [
        {
          "id": 5,
          "name": "Grace Gumbo",
          "flight_id": 3
        }
      ]
    }
  ]
}
```
</code-block>
</code-group>

## $select

The `$select` system query option allows the clients to requests a limited set of properties for each entity or
complex type. The request below returns origin and destination of all flights:

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/flights?$select=origin,destination
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#flights(origin,destination)",
    "value": [
        {
            "origin": "lhr",
            "destination": "lax"
        },
        {
            "origin": "sam",
            "destination": "rgr"
        },
        {
            "origin": "sfo",
            "destination": "lax"
        }
    ]
}
```
</code-block>
</code-group>

## $search

The `$search` system query option restricts the result to include only those entities matching the specified search
expression. The definition of what it means to match is dependent upon the implementation. The request below gets all
airports that have 'fo' or 'lh' in their code.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/flights?$search=fo OR lh
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#airports",
    "value": [
        {
            "id": 1,
            "name": "Heathrow",
            "code": "lhr",
        },
        {
            "id": 3,
            "name": "San Francisco",
            "code": "sfo",
        }
    ]
}
```
</code-block>
</code-group>

## $index

The value of the `$index` system query option is the zero-based ordinal position where an
item is to be inserted. This option is supported on entity sets that can be numerically indexed.

The ordinal positions of items within the collection greater than or equal to the inserted
position are increased by one. A negative ordinal number indexes from the end of the collection, with -1 representing
an insert as the last item in the collection.

<code-group>
<code-block title="Request">
```uri
POST http://localhost:8000/odata/examples?$index=1
{
  "name": "Zeta"
}
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost/odata/$metadata#examples/$entity",
    "name": "Zeta",
    "id": 1
}
```
</code-block>
</code-group>

## $compute

The $compute system query option allows clients to define computed properties that can be used in a `$select` or within a
`$filter` or `$orderby` expression. `$compute` uses the same common expression syntax as `$filter`. Computed properties
appear as dynamic properties in the result. Some entity set drivers may support including computed properties in the
result but without supporting their use in `$orderby` or `$filter` statements.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/examples?$compute=year(dob) as yob&$orderby=yob desc&$select=name,dob,yob
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost/odata/$metadata#examples(name,dob,yob)",
    "value": [
        {
            "name": "Epsilon",
            "dob": "2003-04-04T07:07:07+00:00",
            "yob": 2003
        },
        {
            "name": "Gamma",
            "dob": "2002-03-03T06:06:06+00:00",
            "yob": 2002
        },
        {
            "name": "Beta",
            "dob": "2001-02-02T05:05:05+00:00",
            "yob": 2001
        },
        {
            "name": "Alpha",
            "dob": "2000-01-01T04:04:04+00:00",
            "yob": 2000
        },
        {
            "name": "Delta",
            "yob": null
        }
    ]
}
```
</code-block>
</code-group>

## $any and $all

OData defines two lambda operators `any` and `all` that evaluate a Boolean expression on a collection.
They can work on either collection properties or collections of entities.

The request below returns the People that have Pets with property 'Name' ending with 'The Dog'.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People?$filter=pets/any(s:endswith(s/Name, 'The Dog'))
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Email": "scott.bumble@gmail.com"
    },
    {
      "id": 3,
      "FirstName": "Michael",
      "LastName": "Scott",
      "Email": "michael.scott@hotmail.com"
    }
  ]
}
```
</code-block>
</code-group>

## $each

The `$each` path segment enables actions and odata operations such as deletes and updates to be run on sequences of
entities server-side. To filter the sequence the `$filter` path segment must be used.

Members of a collection can be updated by submitting a PATCH request to the URL constructed by appending `/$each` to the
resource path of the collection. The additional path segment expresses that the request body describes an update to
each member of the collection, not an update to the collection itself.

<code-group>
<code-block title="Request">
```uri
PATCH http://localhost/odata/People/$filter(@bar)/$each?@bar=Color eq 'beige-brown'
{
  "Color": "taupe"
}
```
</code-block>

<code-block title="Response">
```json
{
  "@context": "http://localhost:8000/odata/$metadata#People",
  "value": [
    {
      "id": 1,
      "FirstName": "Scott",
      "LastName": "Bumble",
      "Color": "taupe"
    },
    {
      "id": 3,
      "FirstName": "Michael",
      "LastName": "Scott",
      "Color": "taupe"
    }
  ]
}
```
</code-block>
</code-group>

Members of a collection can be deleted by submitting a DELETE request to the URL constructed by appending `/$each`
to the resource path of the collection. The additional path segment expresses that the collection itself is not
deleted.

```uri
DELETE http://localhost/odata/People/$filter(@bar)/$each?@bar=Color eq 'beige-brown'
```

A bound operation with a single-valued binding parameter can be applied to each member of a collection by appending
the path segment `$each` to the resource path of the collection, followed by a forward slash and the namespace- or
alias-qualified name of the bound operation.

```uri
GET http://localhost/odata/People/$each/SampleModel.MostRecentOrder()
```
