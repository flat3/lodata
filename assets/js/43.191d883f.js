(window.webpackJsonp=window.webpackJsonp||[]).push([[43],{361:function(e,t,n){"use strict";n.r(t);var a=n(7),o=Object(a.a)({},(function(){var e=this,t=e._self._c;return t("ContentSlotsDistributor",{attrs:{"slot-key":e.$parent.slotKey}},[t("h1",{attrs:{id:"entity-data-model"}},[t("a",{staticClass:"header-anchor",attrs:{href:"#entity-data-model"}},[e._v("#")]),e._v(" Entity Data Model")]),e._v(" "),t("p",[e._v("This section provides a high-level description of the Entity Data Model (EDM): the abstract data model that is used to\ndescribe the data exposed by an OData service. An OData Metadata Document is a representation of a service's data model\nexposed for client consumption.")]),e._v(" "),t("p",[e._v("You can request the Metadata Document as:")]),e._v(" "),t("ul",[t("li",[e._v("CSDL JSON:\n"),t("a",{attrs:{href:"http://127.0.0.1:8000/odata/$metadata?$format=json",target:"_blank",rel:"noopener noreferrer"}},[t("code",[e._v("http://127.0.0.1:8000/odata/$metadata?$format=json")]),t("OutboundLink")],1)]),e._v(" "),t("li",[e._v("CSDL XML:\n"),t("a",{attrs:{href:"http://127.0.0.1:8000/odata/$metadata?$format=xml",target:"_blank",rel:"noopener noreferrer"}},[t("code",[e._v("http://127.0.0.1:8000/odata/$metadata?$format=xml")]),t("OutboundLink")],1)]),e._v(" "),t("li",[e._v("OpenAPI v3:\n"),t("a",{attrs:{href:"http://127.0.0.1:8000/odata/openapi.json",target:"_blank",rel:"noopener noreferrer"}},[t("code",[e._v("http://127.0.0.1:8000/odata/openapi.json")]),t("OutboundLink")],1)])]),e._v(" "),t("p",[e._v("The central concepts in the EDM are "),t("strong",[e._v("entities, relationships, entity sets, actions, and functions")]),e._v(".")]),e._v(" "),t("div",{staticClass:"custom-block tip"},[t("p",{staticClass:"custom-block-title"},[e._v("TIP")]),e._v(" "),t("p",[e._v("All of these concepts exist as PHP classes in the root of the "),t("code",[e._v("\\Flat3\\Lodata")]),e._v(" namespace. For example,\n"),t("code",[e._v("EntitySet")]),e._v(", "),t("code",[e._v("EntityType")]),e._v(" and "),t("code",[e._v("Operation")]),e._v(". Create instances of these\nclasses and use "),t("code",[e._v("\\Lodata::add()")]),e._v(" to construct your model.")])]),e._v(" "),t("p",[t("strong",[e._v("Entities")]),e._v(" are instances of entity types (eg Customer, Employee, etc.). Similar to Eloquent model instances.")]),e._v(" "),t("p",[t("strong",[e._v("Entity types")]),e._v(" are named structured types with a key. They define the named properties and relationships of an entity.\nEntity types may derive by single inheritance from other entity types. Similar to an Eloquent model class definition,\ncombined with a Blueprint migration.")]),e._v(" "),t("p",[e._v("A single primitive property of the entity type specifies the key that uniquely identifies it\n(eg CustomerId, OrderId, LineId, etc.).")]),e._v(" "),t("p",[t("strong",[e._v("Complex")]),e._v(" types are keyless named structured types consisting of a set of properties. These are value types whose instances\ncannot be referenced outside of their containing entity. Complex types are commonly used as property values in an entity\nor as parameters to operations.")]),e._v(" "),t("p",[t("strong",[e._v("Properties")]),e._v(" declared as part of a structured type's definition are called "),t("strong",[e._v("declared properties")]),e._v(". Instances of structured\ntypes may contain additional undeclared "),t("strong",[e._v("dynamic properties")]),e._v(". A dynamic property cannot have the same name as a declared\nproperty. Entity or complex types which allow clients to persist additional undeclared properties are called open types.")]),e._v(" "),t("p",[e._v("Relationships from one entity to another are represented as "),t("strong",[e._v("navigation properties")]),e._v(". Navigation properties are generally\ndefined as part of an entity type, but can also appear on entity instances as undeclared dynamic navigation properties.\nEach relationship has a cardinality.")]),e._v(" "),t("p",[e._v("Enumeration types are named primitive types whose values are named constants with underlying integer values.")]),e._v(" "),t("p",[t("RouterLink",{attrs:{to:"/modelling/types.html"}},[e._v("Type definitions")]),e._v(" are named primitive types with fixed facet values such as maximum length or precision. Type definitions\ncan be used in place of primitive typed properties, for example, within property definitions.")],1),e._v(" "),t("p",[t("RouterLink",{attrs:{to:"/modelling/drivers/"}},[e._v("Entity sets")]),e._v(" are named collections of entities (eg Customers is an entity set containing Customer entities, following\nthe Laravel naming convention). An\nentity's key uniquely identifies the entity within an entity set.\nEntity sets provide\nentry points into the data model. Lodata refers to specific implementations of backend data sources such as Eloquent and Redis as\n"),t("RouterLink",{attrs:{to:"/modelling/drivers/"}},[e._v("entity set drivers")]),e._v(".")],1),e._v(" "),t("p",[t("RouterLink",{attrs:{to:"/modelling/operations.html"}},[e._v("Operations")]),e._v(" allow the execution of custom logic on parts of a data model. Functions are operations that do not have\nside effects and may support further composition, for example, with additional filter operations, functions or an\naction. Actions are operations that allow side effects, such as data modification, and cannot be further composed in\norder to avoid non-deterministic behavior. Actions and functions are either bound to a type, enabling them to be\ncalled as members of an instance of that type, or unbound, in which case they are called as static operations.\nAction imports and function imports enable unbound actions and functions to be called from the service root.")],1),e._v(" "),t("p",[t("RouterLink",{attrs:{to:"/modelling/singletons.html"}},[e._v("Singletons")]),e._v(" are named entities which can be accessed as direct children of the entity container. A singleton may also\nbe a member of an entity set.")],1),e._v(" "),t("p",[e._v("An OData resource is anything in the model that can be addressed (an entity set, entity, property, or operation).")])])}),[],!1,null,null,null);t.default=o.exports}}]);