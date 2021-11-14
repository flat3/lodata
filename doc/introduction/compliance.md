# Specification Compliance

The relevant parts of the specification used for Lodata are linked in the navigation bar under **OData Specification**.

Lodata supports many sections of the OData specification, these are the major areas of support:

* Publishing a [service document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358840) at the service root
* Publishing a [metadata document](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_MetadataRequests) in both [JSON](http://docs.oasis-open.org/odata/odata-csdl-json/v4.01/odata-csdl-json-v4.01.html) and [XML](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html) formats
* Publishing an [OpenAPI specification document](https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html) at the service root
* Adding custom [annotations](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Annotation)
* Strict type model for primitive types, supporting Eloquent casts and getter/setters
* Returning data according to the [OData-JSON](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html) specification
* [Streaming JSON](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PayloadOrderingConstraints) support
* Using [server-driven-pagination](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServerDrivenPaging) when returning partial results
* The [$expand](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_SystemQueryOptionexpand) system query option
* The [$select](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358942) system query option
* The [$orderby](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358952) system query option, including multiple orders on individual properties
* The [$top](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358953) system query option
* The [$skip](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358954) system query option
* The [$count](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358955) system query option
* The [$search](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358956) system query option
* The [$value](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358940) path segment
* The [$filter](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358948) system query option, with all expressions, functions, operators, and supports query parameter aliases
* The [$index](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_PositionalInserts) system query option
* [Asynchronous requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_AsynchronousRequests) using Laravel jobs, with monitoring, cancellation and callbacks
* [Batch requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_BatchRequests) in both multipart and JSON formats, including entity back-references and asynchronous batch requests
* [Deep insert](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_CreateRelatedEntitiesWhenCreatinganE) support at any depth
* [Deep update](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_UpdateRelatedEntitiesWhenUpdatinganE) support at any depth
* [Resolving](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ResolvinganEntityId) entity IDs into representations
* [Stream](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358992) properties in the payload and via read links
* Edit links, and POST/PATCH/DELETE requests for new or existing entities
* Use of [ETags](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358971) for avoiding update conflicts
* [Lambda](https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_LambdaOperators) operators `any` and `all`
* Composable URLs
* Declared and navigation properties
* Referential constraints
* Entity singletons
* Key as segment
* Passing query options in the [request body](https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_PassingQueryOptionsintheRequestBody)
* Database [transactions](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ClientErrorResponses)
* Requesting [entity references](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_EntityReference)
* [IEEE754](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheRepresentationofNumber) number-as-string support
* Primitive [literals](https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_PrimitiveLiterals) including duration and enumeration in URLs.
* Full, minimal and no [metadata](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#_Toc38457725) requests
* Function and Action [operations](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359005), including bound operations and inline parameters
* Addressing [All Entities](https://docs.oasis-open.org/odata/odata/v4.01/os/part2-url-conventions/odata-v4.01-os-part2-url-conventions.html#sec_AddressingAllEntitiesinaService) in a service
* Automatic discovery of PDO or Eloquent model tables, and relationships between Eloquent models
* All database backends that Laravel supports (MySQL, PostgreSQL, SQLite and Microsoft SQL Server) including all possible $filter expressions
* Automatic discovery of OData feeds by PowerBI (using [PBIDS](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data)) and Excel (using [ODCFF](https://docs.microsoft.com/en-us/openspecs/office_file_formats/ms-odcff/09a237b3-a761-4847-a54c-eb665f5b0a6e))
* Custom entity type, primitive type and entity set support
* Extensible driver model enabling the integration of data stores such as Redis, local files and third party REST APIs.
