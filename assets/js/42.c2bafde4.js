(window.webpackJsonp=window.webpackJsonp||[]).push([[42],{482:function(t,e,a){"use strict";a.r(e);var s=a(23),n=Object(s.a)({},(function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("ContentSlotsDistributor",{attrs:{"slot-key":t.$parent.slotKey}},[a("h1",{attrs:{id:"introduction"}},[a("a",{staticClass:"header-anchor",attrs:{href:"#introduction"}},[t._v("#")]),t._v(" Introduction")]),t._v(" "),a("p",[t._v('OData describes data stores as entity sets, with the records within as entities.\nLodata implements support for several kinds of services handled by Laravel, which are referred to as "drivers".')]),t._v(" "),a("p",[t._v("A Lodata 'driver' represents any data store that could implement one or more of the "),a("code",[t._v("\\Flat3\\Lodata\\Interfaces\\EntitySet")]),t._v(" interfaces\nincluding "),a("code",[t._v("QueryInterface")]),t._v(", "),a("code",[t._v("ReadInterface")]),t._v(", "),a("code",[t._v("UpdateInterface")]),t._v(", "),a("code",[t._v("DeleteInterface")]),t._v(", and "),a("code",[t._v("CreateInterface")]),t._v(".")]),t._v(" "),a("p",[t._v("A wide variety of different services can support these interfaces in whatever way makes sense to that service. Services could be\nother databases, NoSQL services, other REST APIs or simple on-disk text files.")]),t._v(" "),a("p",[t._v("In addition to the query\ninterface the driver may implement "),a("code",[t._v("SearchInterface")]),t._v(" and "),a("code",[t._v("FilterInterface")]),t._v(" to support "),a("code",[t._v("$search")]),t._v(" and "),a("code",[t._v("$filter")]),t._v(", and other system\nquery parameters can be supported through "),a("code",[t._v("ExpandInterface")]),t._v(", "),a("code",[t._v("TokenPaginationInterface")]),t._v(", "),a("code",[t._v("PaginationInterface")]),t._v(" and "),a("code",[t._v("OrderByInterface")]),t._v(".")]),t._v(" "),a("p",[t._v("Implementation of these interfaces is optional, and Lodata will detect support and return a 'Not Implemented' exception\nto a client trying to use an interface that is not available.")]),t._v(" "),a("h2",{attrs:{id:"caching"}},[a("a",{staticClass:"header-anchor",attrs:{href:"#caching"}},[t._v("#")]),t._v(" Caching")]),t._v(" "),a("p",[t._v("Some entity set drivers support automatically discovering the schema of the connected data store. This discovery can\nadd unnecessary overhead in production, so Lodata provides "),a("a",{attrs:{href:"/getting-started/configuration"}},[t._v("configuration options")]),t._v("\nto add caching of schema data.")]),t._v(" "),a("h2",{attrs:{id:"property-renaming"}},[a("a",{staticClass:"header-anchor",attrs:{href:"#property-renaming"}},[t._v("#")]),t._v(" Property renaming")]),t._v(" "),a("p",[t._v("Lodata supports having different property names used in the schema compared to the backend driver. For example you\nmay have an OData property named "),a("code",[t._v("CustomerAge")]),t._v(" which is named "),a("code",[t._v("customer_age")]),t._v(" in a database table. To create a mapping\nfrom Lodata property to backend property use the "),a("code",[t._v("setPropertySourceName()")]),t._v(" method on the entity set object.")]),t._v(" "),a("div",{staticClass:"language-php line-numbers-mode"},[a("pre",{pre:!0,attrs:{class:"language-php"}},[a("code",[a("span",{pre:!0,attrs:{class:"token variable"}},[t._v("$entitySet")]),t._v(" "),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("=")]),t._v(" "),a("span",{pre:!0,attrs:{class:"token class-name static-context"}},[t._v("Lodata")]),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("::")]),a("span",{pre:!0,attrs:{class:"token function"}},[t._v("getEntitySet")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("(")]),a("span",{pre:!0,attrs:{class:"token string single-quoted-string"}},[t._v("'passengers'")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(")")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(";")]),t._v("\n"),a("span",{pre:!0,attrs:{class:"token variable"}},[t._v("$ageProperty")]),t._v(" "),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("=")]),t._v(" "),a("span",{pre:!0,attrs:{class:"token variable"}},[t._v("$entitySet")]),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("->")]),a("span",{pre:!0,attrs:{class:"token function"}},[t._v("getType")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("(")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(")")]),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("->")]),a("span",{pre:!0,attrs:{class:"token function"}},[t._v("getProperty")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("(")]),a("span",{pre:!0,attrs:{class:"token string single-quoted-string"}},[t._v("'CustomerAge'")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(")")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(";")]),t._v("\n"),a("span",{pre:!0,attrs:{class:"token variable"}},[t._v("$entitySet")]),a("span",{pre:!0,attrs:{class:"token operator"}},[t._v("->")]),a("span",{pre:!0,attrs:{class:"token function"}},[t._v("setPropertySourceName")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v("(")]),a("span",{pre:!0,attrs:{class:"token variable"}},[t._v("$ageProperty")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(",")]),t._v(" "),a("span",{pre:!0,attrs:{class:"token string single-quoted-string"}},[t._v("'customer_age'")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(")")]),a("span",{pre:!0,attrs:{class:"token punctuation"}},[t._v(";")]),t._v("\n")])]),t._v(" "),a("div",{staticClass:"line-numbers-wrapper"},[a("span",{staticClass:"line-number"},[t._v("1")]),a("br"),a("span",{staticClass:"line-number"},[t._v("2")]),a("br"),a("span",{staticClass:"line-number"},[t._v("3")]),a("br")])])])}),[],!1,null,null,null);e.default=n.exports}}]);