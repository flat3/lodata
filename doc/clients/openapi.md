# OpenAPI / Swagger

Lodata can render an OpenAPI Specification Document modelling the entity sets, entity types and operations available
in the service. The URL to the document is available at [`http://127.0.0.1:8000/odata/openapi.json`](http://127.0.0.1:8000/odata/openapi.json).

The OpenAPI Specification (OAS, formerly known as Swagger RESTful API Documentation Specification) defines a standard,
language-agnostic interface to RESTful APIs which allows both humans and computers to discover and understand the
capabilities of the service without access to source code, documentation, or through network traffic inspection.

Lodata implements the mapping of OData service descriptions to OAS documents as described in
[OData to OpenAPI Mapping Version 1.0](https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html).
This mapping only translates the basic features of an OData service into OpenAPI terms to allow an easy “first contact”
by exploring it e.g. with the [Swagger UI](https://github.com/swagger-api/swagger-ui), rather than trying to capture
all features of an OData service in an unmanageably long OAS document.

Given the different goals of and levels of abstractions used by OData and OpenAPI, this mapping of OData metadata
documents into OAS documents is intentionally lossy and only tries to preserve the main features of an OData service:
- The entity container is translated into an OpenAPI Paths Object with path templates and operation objects
  for all top-level resources described by the entity container
- Structure-describing CSDL elements (structured types, type definitions, enumerations) are translated
  into OpenAPI Schema Objects within the OpenAPI Components Object
- CSDL constructs that don’t have an OpenAPI counterpart are omitted

Lodata provides an easy way to reference the OAS document URL in your application:

```php
\Lodata::getOpenApiUrl()
```
