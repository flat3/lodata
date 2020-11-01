# Flat3 OData 4.01 Producer for Laravel

<a href="https://github.com/flat3/lodata/actions"><img src="https://github.com/flat3/lodata/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/v/flat3/lodata" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/l/flat3/lodata" alt="License"></a>

<!--ts-->
   * [What is OData](#what-is-odata)
   * [Why OData for Laravel](#why-odata-for-laravel)
   * [Getting started](#getting-started)
   * [Usage](#usage)
   * [Q&A](#qa)
   * [Specification](#specification)
   * [License](#license)
<!--te-->

## What is OData?

The [OData Protocol](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_Overview)
is an application-level protocol for interacting with data via RESTful interfaces. The protocol supports the
description of data models and the editing and querying of data according to those models. It provides facilities for:

- Metadata: a machine-readable description of the data model exposed by a particular service.
- Data: sets of data entities and the relationships between them.
- Querying: requesting that the service perform a set of filtering and other transformations to its data, then return the results.
- Editing: creating, updating, and deleting data.
- Operations: invoking custom logic
- Vocabularies: attaching custom semantics

## Why OData for Laravel?

Many Laravel applications are used in a business context that has these requirements:
- Enabling other services to query the database and run operations via a REST API
- Enabling the customer to pull data into their environment using applications such as Excel, PowerBI, Tableau and
other business intelligence tools that support OData natively.

Lodata is ideal for both these use cases.

## Getting started

First require lodata inside your existing Laravel application:

```
composer require flat3/lodata
```

Now start your app. The OData API endpoint will now be available at: http://127.0.0.1:8000/odata/ (or whichever port your application normally runs on).
Accessing that endpoint should show you the [Service Document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServiceDocumentRequest).
The [Metadata Document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_MetadataDocumentRequest) will be available at http://127.0.0.1:8000/odata/$metadata

So far there's no data exposed in the service, the next step is to create a Laravel service provider to handle this.

## Usage

### Discovery

### Authentication

### Authorization

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
* Strict type model for primitive types
* Returning data according to the [OData-JSON](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html) specification
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
* All database backends that Laravel supports (MySQL, PostgreSQL, SQLite and MSSQL) including all possible $filter expressions
* Automatic discovery of OData feeds by PowerBI (using PBIDS) and Excel (using ODCFF)
* Custom entity type, primitive type and entity set support
* Extensible driver model enabling the use of data stores such as Redis and third party REST APIs

## License

Copyright © Chris Lloyd

Flat3 OData is open-sourced software licensed under the [MIT license](LICENSE.md).