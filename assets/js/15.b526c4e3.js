(window.webpackJsonp=window.webpackJsonp||[]).push([[15],{330:function(e,t,a){"use strict";a.r(t);var n=a(7),s=Object(n.a)({},(function(){var e=this,t=e._self._c;return t("ContentSlotsDistributor",{attrs:{"slot-key":e.$parent.slotKey}},[t("h1",{attrs:{id:"openapi-swagger"}},[t("a",{staticClass:"header-anchor",attrs:{href:"#openapi-swagger"}},[e._v("#")]),e._v(" OpenAPI / Swagger")]),e._v(" "),t("p",[e._v("Lodata can render an OpenAPI Specification Document modelling the entity sets, entity types and operations available\nin the service. The URL to the document is available at "),t("a",{attrs:{href:"http://127.0.0.1:8000/odata/openapi.json",target:"_blank",rel:"noopener noreferrer"}},[t("code",[e._v("http://127.0.0.1:8000/odata/openapi.json")]),t("OutboundLink")],1),e._v(".")]),e._v(" "),t("p",[e._v("The OpenAPI Specification (OAS, formerly known as Swagger RESTful API Documentation Specification) defines a standard,\nlanguage-agnostic interface to RESTful APIs which allows both humans and computers to discover and understand the\ncapabilities of the service without access to source code, documentation, or through network traffic inspection.")]),e._v(" "),t("p",[e._v("Lodata implements the mapping of OData service descriptions to OAS documents as described in\n"),t("a",{attrs:{href:"https://docs.oasis-open.org/odata/odata-openapi/v1.0/cn01/odata-openapi-v1.0-cn01.html",target:"_blank",rel:"noopener noreferrer"}},[e._v("OData to OpenAPI Mapping Version 1.0"),t("OutboundLink")],1),e._v(".\nThis mapping only translates the basic features of an OData service into OpenAPI terms to allow an easy “first contact”\nby exploring it e.g. with the "),t("a",{attrs:{href:"https://github.com/swagger-api/swagger-ui",target:"_blank",rel:"noopener noreferrer"}},[e._v("Swagger UI"),t("OutboundLink")],1),e._v(", rather than trying to capture\nall features of an OData service in an unmanageably long OAS document.")]),e._v(" "),t("p",[e._v("Given the different goals of and levels of abstractions used by OData and OpenAPI, this mapping of OData metadata\ndocuments into OAS documents is intentionally lossy and only tries to preserve the main features of an OData service:")]),e._v(" "),t("ul",[t("li",[e._v("The entity container is translated into an OpenAPI Paths Object with path templates and operation objects\nfor all top-level resources described by the entity container")]),e._v(" "),t("li",[e._v("Structure-describing CSDL elements (structured types, type definitions, enumerations) are translated\ninto OpenAPI Schema Objects within the OpenAPI Components Object")]),e._v(" "),t("li",[e._v("CSDL constructs that don’t have an OpenAPI counterpart are omitted")])]),e._v(" "),t("p",[e._v("Lodata provides an easy way to reference the OAS document URL in your application:")]),e._v(" "),t("div",{staticClass:"language-php line-numbers-mode"},[t("pre",{pre:!0,attrs:{class:"language-php"}},[t("code",[t("span",{pre:!0,attrs:{class:"token class-name class-name-fully-qualified static-context"}},[t("span",{pre:!0,attrs:{class:"token punctuation"}},[e._v("\\")]),e._v("Lodata")]),t("span",{pre:!0,attrs:{class:"token operator"}},[e._v("::")]),t("span",{pre:!0,attrs:{class:"token function"}},[e._v("getOpenApiUrl")]),t("span",{pre:!0,attrs:{class:"token punctuation"}},[e._v("(")]),t("span",{pre:!0,attrs:{class:"token punctuation"}},[e._v(")")]),e._v("\n")])]),e._v(" "),t("div",{staticClass:"line-numbers-wrapper"},[t("span",{staticClass:"line-number"},[e._v("1")]),t("br")])])])}),[],!1,null,null,null);t.default=s.exports}}]);