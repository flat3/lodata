# Flat3 OData 4.01 Producer for Laravel

<a href="https://github.com/flat3/lodata/actions"><img src="https://github.com/flat3/lodata/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/v/flat3/lodata" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/l/flat3/lodata" alt="License"></a>

<!--ts-->
   * [What is OData](#what-is-odata)
   * [Getting started](#getting-started)
   * [Specification](#specification)
   * [License](#license)
<!--te-->

## What is OData? (from the OData spec)

The OData Protocol is an application-level protocol for interacting with data via RESTful interfaces. The protocol supports the
description of data models and the editing and querying of data according to those models. It provides facilities for:

- Metadata: a machine-readable description of the data model exposed by a particular service.
- Data: sets of data entities and the relationships between them.
- Querying: requesting that the service perform a set of filtering and other transformations to its data, then return the results.
- Editing: creating, updating, and deleting data.
- Operations: invoking custom logic
- Vocabularies: attaching custom semantics

The OData Protocol is different from other REST-based web service approaches in that it provides a uniform way to describe
both the data and the data model. This improves semantic interoperability between systems and allows an ecosystem to emerge.
Towards that end, the OData Protocol follows these design principles:
- Prefer mechanisms that work on a variety of data sources. In particular, do not assume a relational data model.
- Extensibility is important. Services should be able to support extended functionality without breaking clients unaware of those extensions.
- Follow REST principles.
- OData should build incrementally. A very basic, compliant service should be easy to build, with additional work necessary only to support additional capabilities.
- Keep it simple. Address the common cases and provide extensibility where necessary.

## Getting started

First require lodata inside your existing Laravel application:

```
composer require flat3/lodata
```

Now start your app, the OData API endpoint will now be available at: http://127.0.0.1:8000/odata (or whichever port your application normally runs on).

If you access that URL you'll see an "unauthorized" message. By default the endpoint is wrapped in HTTP Basic Authentication.
You can either provide basic auth credentials of an existing user, or you can temporarily disable authentication by including this in your
Laravel .env file:

```
LODATA_DISABLE_AUTH=1
```

Access the URL again, you'll see the Service Document. The Metadata Document will also be available at: http://127.0.0.1:8000/odata/$metadata

So far there's no data exposed in the service, the next step is to create a Laravel service provider to handle this.

## Specification

* https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html
* https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html
* https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html
* https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html
* https://docs.oasis-open.org/odata/odata-vocabularies/v4.0/csprd01/odata-vocabularies-v4.0-csprd01.html

## License

Copyright © Chris Lloyd

Flat3 OData is open-sourced software licensed under the [MIT license](LICENSE.md).