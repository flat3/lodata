# Lodata - The OData 4.01 Producer for Laravel

<a href="https://github.com/flat3/lodata/actions"><img src="https://github.com/flat3/lodata/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/v/flat3/lodata" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/l/flat3/lodata" alt="License"></a>

## Contents

1. Introduction
   1. [What is OData?](#what-is-odata)
   1. [Why OData for Laravel?](#why-odata-for-laravel)
1. Basic usage
   1. [Getting started](#getting-started)
   1. [Authentication](#authentication)
   1. [Authorization](#authorization)
   1. [Discovery](#discovery)
   1. [Applications](#applications)
1. Advanced usage
   1. [Database](#database)
   1. [Annotations](#annotations)
   1. [Generated properties](#generated-properties)
   1. [Asynchronous Requests](#asynchronous-requests)
   1. [Filter expressions](#filter-expressions)
   1. [Alternative keys](#alternative-keys)
   1. [Function composition](#function-composition)
   1. [Operations](#operations)
1. Internals
   1. [Transactions](#transactions)
   1. [Streaming JSON](#streaming-json)
   1. [Drivers](#drivers)
   1. [Types](#types)
1. [Specification compliance](#specification-compliance)
1. [License](#license)

## Introduction

### What is OData?

The [OData Protocol](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_Overview)
is an application-level protocol for interacting with data via RESTful interfaces. The protocol supports the
description of data models and the editing and querying of data according to those models. It provides facilities for:

- Metadata: a machine-readable description of the data model exposed by a particular service.
- Data: sets of data entities and the relationships between them.
- Querying: requesting that the service perform a set of filtering and other transformations to its data, then return the results.
- Editing: creating, updating, and deleting data.
- Operations: invoking custom logic
- Vocabularies: attaching custom semantics

OData consumer support exists in a wide variety of applications, particularly those from Microsoft, SAP and SalesForce.

If you're new to OData it is recommended to refer to the description of the
[Data Model](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_DataModel)
as the terminology used here is OData-specific.

### Why OData for Laravel?

Many Laravel applications are used in an agency/customer context that have these kinds of requirements:
- Our customer wants to access our data using applications such as Excel, PowerBI and Tableau to generate reports
and dashboards, but doesn't like the complexity of logging in and performing manual, error-prone CSV/XLSX downloads to
fetch their data
- Our customer requires authorized third party developers to query our application's database, possibly modifying the data
and running internal functions and we want to manage how these processes work in Laravel
- Our customer has internal stakeholders (non-expert data users) that need access to different sets of data that we hold,
based on their role, but do not need or desire administrative access to our application

Lodata is easy to integrate into existing Laravel projects, provides an out-of-the-box discoverable API for third-party
developers and a straightforward data workflow for business users.

## Basic usage

### Getting started

**Step 1: Install Lodata into your Laravel app using [Composer](https://getcomposer.org)**

```
composer require flat3/lodata
```

Now start your app. The OData API endpoint will now be available at: `http://127.0.0.1:8000/odata/`
(or whichever URL prefix your application normally runs on).
Accessing that endpoint in an API client such as [Postman](https://www.postman.com/product/api-client/) will show you
the [Service Document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServiceDocumentRequest).

**Step 2: Discover your first model**

Edit a service provider such as `app/Providers/AppServiceProvider.php` to add the
following to the `boot()` method: (using your own Model instead of `Example`)

```
\Lodata::discoverEloquentModel(\App\Models\Example::class)
```

You can now access `http://127.0.0.1:8000/odata/Example` and see the data in your database stored by that model.

**Step 3: Load your data into an application**

Lodata has specific support for Excel and PowerBI service discovery. Use one of the following URLs in
a browser to prompt Windows to open the feed in the relevant application:

- To load the `Example` model in Excel use `http://127.0.0.1:8000/odata/_lodata/Example.odc`
- For PowerBI use `http://127.0.0.1:8000/odata/_lodata/odata.pbids`.

Both Excel and PowerBI can now refresh the data source themselves using the Refresh buttons in those interfaces.

Any other consumer service requesting your "OData Endpoint" should accept the service document at
`http://127.0.0.1:8000/odata/`

To make changes from this point, you should install Lodata's configuration into your
Laravel application:

```
php artisan vendor:publish --provider="Flat3\Lodata\ServiceProvider" --tag="config"
```

### Authentication

Out of the box Lodata does not wrap the API in authentication. The only authentication type in the OData
standard is HTTP Basic, but many consumers support additional types. To add basic authentication to all Lodata
endpoints modify `config/lodata.php` to include `auth.basic` in the array of middleware.

### Authorization

Lodata supports authorization via [Laravel gates](https://laravel.com/docs/8.x/authorization#gates). Each
API request will be checked via a gate tagged `lodata`. The
gate will receive the standard user argument, and a `Flat3\Lodata\Helper\Gate` object. This object contains
the type of request being made, the Lodata object it is being made against, the Lodata 'transaction' and in the
case of an operation the arguments array will be provided. This is all the information needed for a gate
policy to decide whether to allow the request.

At install time, Lodata runs in a readonly mode. To enable updates, change the value of the `readonly` property
in `config/lodata.php`.

### Discovery

Lodata can 'discover' Eloquent models, and the relationships between the models. This metadata is presented to
the client, so it can understand how the entity sets are related and navigate between them.

Discovery is
performed first using [DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.12/index.html) to
introspect the database table, then [Eloquent casts](https://laravel.com/docs/8.x/eloquent-mutators#custom-casts)
are used for further type specification. During requests, the Eloquent model getter/setter functions are used
to refer to the properties, so any additional field processing being performed by the model will be preserved.

To discover a model the `Lodata` facade that exists in the root namespace can be used. For example to discover
two models:

```
\Lodata::discoverEloquentModel(\App\Models\Flight::class);
\Lodata::discoverEloquentModel(\App\Models\Passenger::class);
```

If model `Flight` has a method `passengers` that returns a [relationship](https://laravel.com/docs/8.x/eloquent-relationships)
to `Passenger` such as hasOne, hasMany, hasManyThrough, this can be discovered by Lodata as a navigation property on the `Flights` entity set. Note that
similar to Laravel itself, Lodata typically refers to 'entity types' in the singular form and 'entity sets' in
the plural form. An entity set and its related entity set must both be defined through discovery before a
relationship can be created.

```
\Lodata::getEntitySet('Flights')
  ->discoverRelationship('passengers')
```

A navigation property now exists in the Flight entity set for Passengers. This enables the client to
navigate by using the navigation property in a URL similar to `http://127.0.0.1:8000/odata/Flights(1)/passengers`
to choose the flight with ID 1, and to get the passengers related to this flight. This navigation property can
also be used in `$expand` requests.

If Lodata is able to determine the relationship cardinality it will be represented in the service metadata
document.

### Applications

#### Using Lodata with Excel

[Excel 2019](https://www.microsoft.com/en-gb/microsoft-365/excel) (and some earlier versions) support [OData Feeds](https://docs.microsoft.com/en-us/power-query/connectors/odatafeed)
natively using Power Query.

As well as being able to create a connection in Excel using the UI, Lodata provides an easy to add an
"Open in Excel" button in your application. The URL provided for this button will be for a specific entity
set, for example for the `Flights` entity set:

```
\Lodata::getOdcUrl('Flights')
```

#### Using Lodata with PowerBI

Microsoft [PowerBI](https://powerbi.microsoft.com) supports the autoloading of a data source via a [PBIDS](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data)
URL. This can be used in a "Open in PowerBI" feature button. Unlike Excel which works on a single
entity set, this button provides PowerBI with access to the whole model:

```
\Lodata::getPbidsUrl()
```

#### Using Lodata with PowerApps

Microsoft [PowerApps](https://powerapps.microsoft.com) also support importing OData Feeds. Using a [dataflow](https://docs.microsoft.com/en-us/powerapps/maker/common-data-service/create-and-use-dataflows)
the data exposed by Lodata can be imported into the Common Data Service. When creating an OData data source
the UI will request the 'OData Endpoint', which can be programatically generated and presented by your app
using:

```
\Lodata::getEndpoint()
```

## Advanced usage

### Database

In addition to Eloquent models, Lodata can discover database tables directly. This can be used to expose tables through OData that are
not used in Eloquent sets, such as through tables for many-to-many relationships. This is required for applications that treat OData
Feeds as relational database models such as PowerBI and Tableau. It can also be used to expose databases that are not even used in the
Laravel application, or to use Lodata as simply an OData endpoint for an existing database.

SQL database tables can be discovered using this syntax:

```
$passengerType = \Flat3\Lodata\EntityType::factory('passenger');
$passengerSet = \Flat3\Lodata\Drivers\SQLEntitySet::factory('passengers', $passengerType)
    ->setTable('passengers')
    ->discoverProperties();
\Lodata::add($passengerSet);
```

First an empty entity type is defined with the name `passenger`, and used to generate the entity set.
Then a table `passengers` is assigned. When `discoverProperties` is run, `passengerType` will be filled with field
types discovered by the entity set.

### Annotations

OData allows the creation of annotations on the schema. Annotations are classes that extend `\Flat3\Lodata\Annotation`
and are added to the model with `Lodata::add($annotation)`. Examples are in the `\Flat3\Lodata\Annotation` namespace.

### Generated properties

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

### Asynchronous requests

The OData specification defines [asynchronous requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359016)
where the client indicates that it prefers the server to respond asynchronously via the `respond-async` Prefer header. This is helpful
for long-running [operations](#operations).

Lodata handles this by generating a Laravel [job](https://laravel.com/docs/8.x/queues#creating-jobs) which is then processed by
Laravel in the same way it handles any other queued job. For this to work your Laravel installation must have a working job queue.

When the client sends a request in this way, the server dispatches the job and returns to the client a monitoring URL. The client
can use this URL to retrieve the job output, or its status if not completed or failed.

The job runner will execute the OData request in the normal way, but will write the output to a Laravel [disk](https://laravel.com/docs/8.x/filesystem#obtaining-disk-instances)
for it to be picked up later. The name of this disk is set in the `disk` option in `config/lodata.php`. In a multi-server environment
this should be some type of shared storage such as NFS or AWS S3. The storage does not need to be client-facing, when the job output
is retrieved it is streamed to the client by the Laravel application.

### Filter expressions

Lodata contains an expression parser in `\Flat3\Lodata\Expression` that handles both `$search` and `$filter` expressions. The
parser decodes the incoming expression into an [abstract syntax tree](https://en.m.wikipedia.org/wiki/Abstract_syntax_tree). During
entity set query processing the entity set driver will be passed every element of the tree in the correct parsing order, enabling it
to convert the OData query into a native query such as an SQL query.

Because not every possible OData function or operation is supported by every Laravel database driver, or the internal semantics of the
underlying database do not support the required data types, then a "Not Supported" exception may be thrown by some database drivers
and not others.

The OData specification describes that the behaviour of the `$search` system query parameter is application-specific. Simple support
for converting `$search` to a series of `field LIKE %param%` requests is available.

The properties that should be used in search queries can be "tagged" using this example. Here the entity type 'airport' is retrieved,
which may have been generated via autodiscovery. The 'name' property is also retrieved, and its 'searchable' property is updated.

```
$airportType = Lodata::getEntityType('airport');
$airportType->getProperty('name')->setSearchable();
```

Any property marked in this way is added to the query by the SQL driver.

The behaviour of both the `$search` and `$filter` parameters can be overridden by extending the driver class, and the relevant methods.

### Alternative keys

In addition to the standard 'id' key that is typical in a database table, any other unique field can be added as an
[alternative key](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#_Toc31360936). This can then
be used to reference an entity.

The properties that should be used as alternative keys can be "tagged" using this example. Here the entity type 'airport' is retrieved,
which may have been generated via autodiscovery. The 'name' property is also retrieved, and its 'alternativeKey' property is updated.

```
$airportType = Lodata::getEntityType('airport');
$airportType->getProperty('code')->setAlternativeKey();
```

With this in place, an airport can be queried with its code using the request style `http://localhost/odata/Airports(code='elo')`

### Operations

Lodata supports both [Functions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359009)
and [Actions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359013]). By OData
convention operations that define themselves as Functions MUST return data and MUST have no observable side effects, and Actions
MAY have side effects when invoked and MAY return data. Lodata does not enforce the side-effect restriction, but does enforce the return
data requirement.

Operations extend the `\Flat3\Lodata\Operation` class, and implement one of the `\Flat3\Lodata\Interfaces\Operation\ActionInterface` or
`\Flat3\Lodata\Interfaces\Operation\FunctionInterface` interfaces. The class must also implement an `invoke()` method, which takes
primitive type parameters. These parameter types and names will be read through [PHP reflection](https://www.php.net/manual/en/book.reflection.php)
and added to the metadata document.

The class can optionally define the name to use for the binding parameter using the `bindingParameterName` property
and the returnType using the `returnType` property during construction. A primitive return type will be resolved through reflection
on the invoke() method. When returning an entity the entity type must be attached using the setReturnType method, and the invoke method
should return Entity.

This Function defined as an anonymous class instance does not receive any parameters, and has a primitive return type of Edm.String
resolved through reflection. This function can be invoked via `http://localhost/odata/helloworld()`

```
Lodata::add((new class('helloworld') extends Operation implements FunctionInterface {
    public function invoke(): String_
    {
        return new String_('Hello world!');
    }
});
```

This Function receives two Edm.String parameters, and returns an Edm.String that concatenates them. The names of the parameters
and their types are resolved through reflection. This function can be invoked via `http://localhost/odata/helloworld(one='hello',two='world)`

```
Lodata::add((new class('concat') extends Operation implements FunctionInterface {
    public function invoke(String_ $one, String_ $two): String_
    {
        return new String_($one->get().$two->get());
    }
})
```

This Function requests that the bound parameter be provided as the 'code' parameter to the method, and sends it back unmodified.
This can be invoked via a URL for example `http://localhost/odata/Airports(1)/code/identity()`.

```
Lodata::add((new class('identity') extends Operation implements FunctionInterface {
    public function invoke(String_ $code): String_
    {
      return $code;
    }
})->setBoundParameter('code');
```

This Function requests the bound parameter be provided as the 'entity' parameter to the method, and additionally defines a provided
parameter 'prefix' and then returns an Edm.String.
This can be invoked via a URL for example `http://localhost/odata/Airports(1)/codeprefix(prefix='example')`.

```
Lodata::add((new class('code') extends Operation implements FunctionInterface {
    public function invoke(Entity $entity, String_ $prefix): String_
    {
      return $prefix->get() . $entity->code->get();
    }
})->setBoundParameter('entity');
```

Finally, entities can themselves be generated and returned. This Function requests the bound parameter be provided as the `text`s
parameter, and indicates that it returns an Entity. Because the entity type cannot be determined through reflection, it must be
explicitly pulled from the model and provided to the operation.
This can be invoked using a URL for example `http://localhost/odata/Airports/egen()` which would provide the `Airports` entity set
to the `egen` function as the bound parameter.

```
Lodata::add((new class('egen') extends Operation implements FunctionInterface {
    public function invoke(EntitySet $texts): Entity
    {
        $entity = $texts->makeEntity();
        $entity['code'] = new String_('example');
        return $entity;
    }
})->setBindingParameterName('texts')->setReturnType(Lodata::getEntityType('text')));
```

To provide additional context to a Function that may require it, the Function can ask for the current Transaction by adding that
argument to the invoke method. In this example the invoke method would receive the Transaction on the `$transaction` method
parameter. The transaction contains all of the available context for the request, and can provide items such as the current system
query options.

```
Lodata::add((new class('hello') extends Operation implements FunctionInterface {
    public function invoke(Transaction $transaction): String_
    {
      return new String_('hello');
    }
});
```

All of the above techniques also apply to Action operations.

### Function composition

OData URLs are parsed using composition, with each path segment being piped to the next using a static `pipe()` method on path
segment classes, with the final segment in the chain being responsible for handling the system query options and generating
the response via the `response()` method.

Operations can therefore act on path segments that precede them as [bound parameters](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_BindinganOperationtoaResource), and the output of one operation can be piped
into the next. The output can therefore pass through several functions before being output.

## Internals

### Transactions

A `\Flat3\Lodata\Controller\Transaction` object is a representation of both the request (`\Flat3\Lodata\Controller\Request`) and
response (`\Flat3\Lodata\Controller\Response`) objects, handles piping the request from one path segment to the next, and provides
a variety of helper methods to generate context and get aspects of the request. Transaction also implements the streaming JSON encoder.

The OData `$expand` system query option, which can itself take system query parameters, creates a new transaction that represents
a sub-request within the main request, with a subclass of the Request object as a NavigationRequest. These can be further nested in
subrequests of `$expand` requests.

Transactions are also serializable for the purposes of async requests, and can therefore be handled offline, replayed, retried etc.

### Streaming JSON

Responses to OData requests can be of unlimited size. The request for an entity set without server-side pagination, of a database
table of many gigabytes, would generate a JSON document of at least that size. In order to process this efficiently, and without
running out of memory, Lodata implements a streaming JSON encoder. Through this method the memory usage of the responding PHP process
will stay very low.

Even if the request for the entity set is made with no pagination parameters, internally `\Flat3\Lodata\EntitySet` will implement
pagination against the database or other storage system so that that system is not overloaded. This process is invisible to the client.

When a path segment refers to an entity set, the initialization of that path segment sets up the query including all the filtering
options, but it is not executed to receive data from the data source until the content is actually emitted or an operation requests
data from it. For example in the SQL driver, the path segment generates the query, prepares and executes the query, but not until
the data is emitted does PDO start drawing data from the server and outputting it.

### Drivers

A Lodata 'driver' represents any storage system that could implement one or more of the `\Flat3\Lodata\Interfaces\EntitySet` interfaces
including `QueryInterface`, `ReadInterface`, `UpdateInterface`, `DeleteInterface`, and `CreateInterface`. In addition to the query
interface the driver may implement `SearchInterface` and `FilterInterface` to support `$search` and `$filter`, and other system
query parameters can be supported through `ExpandInterface`, `PaginationInterface` and `OrderByInterface`. Implementation of any
of these interfaces is optional, and Lodata will detect support and return a 'Not Implemented' exception to a client trying to use
an interface that is not available.

A wide variety of different services can support these interfaces in whatever way makes sense to that service. Services could be
other databases, NoSQL services, other REST APIs or simple on-disk text files.

### Types

OData specifies many [primitive types](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PrimitiveValue)
that can be used in Lodata. PHP's type system is less specific than OData, so type conversion and coercion is implemented by each type
to marshal between PHP and OData types. Lodata will force PHP data into the specified type, for example converting a PHP `int` to 
an OData `Edm.Int16` may cause truncation or overflow, but will ensure the type is in the correct format when a client receives it.

PHP supports higher precision floating point types than JSON itself, so Lodata implements IEEE754 compatibility in OData by returning
Edm.Double (and similar) types as strings if requested to do so by the client.

Lodata implements Edm.Date, Edm.DateTimeOffset and Edm.TimeOfDay using [DateTime](https://www.php.net/manual/en/book.datetime.php)
objects, and retrieving the value of (eg) a `\Flat3\Lodata\Type\DateTimeOffset` using its get() method will return a DateTime.

## Specification compliance

The relevant parts of the specification used for Lodata are:

* https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html
* https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html
* https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html
* https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html
* https://docs.oasis-open.org/odata/odata-vocabularies/v4.0/csprd01/odata-vocabularies-v4.0-csprd01.html

Lodata supports many sections of the OData specification, these are the major areas of support:

* Publishing a [service document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358840) at the service root
* Adding custom annotations
* Strict type model for primitive types, supporting Eloquent casts and getter/setters
* Returning data according to the [OData-JSON](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html) specification
* Streaming JSON support
* Using [server-driven-pagination](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServerDrivenPaging) when returning partial results
* The [$expand](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_SystemQueryOptionexpand) system query option
* The [$select](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358942) system query option
* The [$orderby](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358952) system query option, including multiple orders on individual properties
* The [$top](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358953) system query option
* The [$skip](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358954) system query option
* The [$count](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358955) system query option
* The [$search](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358956) system query option
* The $value path segment
* The [$filter](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358948) system query option, with all expressions, functions, operators, and supports query parameter aliases
* [Asynchronous requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_AsynchronousRequests) using Laravel jobs, with monitoring, cancellation and callbacks
* Edit links, and POST/PATCH/DELETE requests for new or existing entities
* Composable URLs
* Declared and navigation properties
* Referential constraints
* Entity singletons
* IEEE754 number-as-string support
* Full, minimal and no metadata requests
* Function and Action operations, including bound operations and inline parameters
* Automatic discovery of PDO or Eloquent model tables, and relationships between Eloquent models
* All database backends that Laravel supports (MySQL, PostgreSQL, SQLite and Microsoft SQL Server) including all possible $filter expressions
* Automatic discovery of OData feeds by PowerBI (using [PBIDS](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data)) and Excel (using [ODCFF](https://docs.microsoft.com/en-us/openspecs/office_file_formats/ms-odcff/09a237b3-a761-4847-a54c-eb665f5b0a6e))
* Custom entity type, primitive type and entity set support
* Extensible driver model enabling the integration of data stores such as Redis, local files and third party REST APIs

## License

Copyright © Chris Lloyd

Flat3 Lodata is open-sourced software licensed under the [MIT license](LICENSE.md).