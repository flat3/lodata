# Lodata - The OData 4.01 Producer for Laravel

<a href="https://github.com/flat3/lodata/actions"><img src="https://github.com/flat3/lodata/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/v/flat3/lodata" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/l/flat3/lodata" alt="License"></a>

   * [Background](#background)
   * [Getting started](#getting-started)
   * [Usage](#usage)
   * [Q&A](#qa)
   * [Specification](#specification)
   * [License](#license)

## Background

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

## Get started

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

Lodata has specific support for Excel and PowerBI service discovery.

To load the `Example` model in Excel use `http://127.0.0.1:8000/odata/_lodata/Example.odc` or for PowerBI
use `http://127.0.0.1:8000/odata/_lodata/odata.pbids`.

Both Excel and PowerBI can now refresh the data source themselves using the Refresh buttons in those interfaces.

Any other consumer service requesting your "OData Endpoint" should accept the service document at
`http://127.0.0.1:8000/odata/`

That's all you need to get started, for configuration such as authentication and authorization see the
guides below!

## Usage

### Authentication

### Authorization

### Discovery

### Using Lodata with Excel

### Using Lodata with PowerBI

## Q&A

## Specification

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
* Declared, dynamic and navigation properties
* Referential constraints
* Entity singletons
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