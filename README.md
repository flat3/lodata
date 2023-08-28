# Lodata - The OData v4.01 Producer for Laravel

<a href="https://github.com/flat3/lodata/actions"><img alt="GitHub Workflow Status" src="https://img.shields.io/github/actions/workflow/status/flat3/lodata/tests.yml"></a>
<img alt="OpenAPI Validator" src="https://img.shields.io/swagger/valid/3.0?specUrl=https%3A%2F%2Fraw.githubusercontent.com%2Fflat3%2Flodata%2F5.x%2Ftests%2F__snapshots__%2FProtocol%2FServiceMetadataTest__test_has_flight_metadata_document_at_document_root__4.json"/>
<a href="https://packagist.org/packages/flat3/lodata"><img alt="Packagist Version" src="https://img.shields.io/packagist/v/flat3/lodata"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/flat3/lodata"></a>
<a href="https://packagist.org/packages/flat3/lodata"><img src="https://img.shields.io/packagist/l/flat3/lodata" alt="License"></a>
<img alt="Code Climate maintainability" src="https://img.shields.io/codeclimate/maintainability-percentage/flat3/lodata">
<img alt="Code Climate coverage" src="https://img.shields.io/codeclimate/coverage/flat3/lodata">

[Lodata](https://lodata.io) is an implementation of the OData v4.01 Producer protocol, designed for use with the Laravel framework.

[See the documentation here!](https://lodata.io/introduction/)

[OData](https://www.odata.org) (Open Data Protocol) is an
[ISO/IEC approved](https://www.oasis-open.org/news/pr/iso-iec-jtc-1-approves-oasis-odata-standard-for-open-data-exchange),
[OASIS standard](https://www.oasis-open.org/committees/tc_home.php?wg_abbrev=odata) that defines a set of best practices for building and
consuming RESTful APIs.

OData helps you focus on your business logic while building RESTful APIs without having to worry about the various approaches to define request
and response headers, status codes, HTTP methods, URL conventions, media types, payload formats, query options, etc. OData also provides guidance
for tracking changes, defining functions/actions for reusable procedures, and sending asynchronous/batch requests.

OData RESTful APIs are easy to consume. The OData metadata, a machine-readable description of the data model of the APIs, enables the creation
of powerful generic client proxies and tools. The metadata is available in OData-specific
[XML](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html) and
[JSON](https://docs.oasis-open.org/odata/odata-csdl-json/v4.01/odata-csdl-json-v4.01.html) formats, as well as an
[OpenAPI v3](https://swagger.io/specification/) document.

There are many tools and techniques for exposing APIs from Laravel and there are some specific use cases
where Lodata could be a great fit for your application:

- Developing **single page applications** and **mobile applications** with OData-supporting enterprise UI frameworks such as
  [Sencha ExtJS](https://docs.sencha.com/extjs/latest/modern/Ext.data.proxy.Rest.html),
  [DevExtreme](https://js.devexpress.com/Documentation/Guide/Data_Binding/Specify_a_Data_Source/OData/),
  [Kendo UI](https://docs.telerik.com/kendo-ui/framework/datasource/basic-usage) and
  [Syncfusion](https://ej2.syncfusion.com/documentation/data/adaptors/#odatav4-adaptor).
- Making live connections to **business intelligence** tools
  such as [Excel](https://docs.microsoft.com/en-us/power-query/connectors/odatafeed),
  [PowerBI](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-connect-odata),
  and [Tableau](https://help.tableau.com/current/pro/desktop/en-us/examples_odata.htm), avoiding clunky CSX/XLSX exports.
- Publishing an out-of-the-box discoverable **OpenAPI** document for tools like
  [Postman](https://www.postman.com/product/api-client/) to help third parties interact with your application.
- Developing **microservices** in Laravel. With all OData services having the same request syntax, as your team develops
  many services you can guarantee API consistency.
- Create [real simple integrations](https://lodata.io/clients/) with enterprise applications from
  [SAP](https://help.sap.com/viewer/3f4043064eed446a895bc8ba7e61dc83/LATEST/en-US/8086d28511be408fbda1443166d350ad.html),
  [SalesForce](https://developer.salesforce.com/docs/atlas.en-us.integration_patterns_and_practices.meta/integration_patterns_and_practices/integ_pat_data_virtualization.htm)
  and [Microsoft](https://docs.microsoft.com/en-us/powerapps/maker/data-platform/virtual-entity-odata-provider-requirements).
  Present forms, tabular data and search interfaces in these applications **without writing a single line of code**.

You can construct OData requests using any HTTP client, but there are also many developer-friendly
[OData libraries](https://www.odata.org/libraries/) for different programming languages.

Now go check out the five-minute [getting started](https://lodata.io/getting-started/) guide!

## Support

<a href="https://flat3.co">Flat3</a> now provides commercial support for Lodata. If you need help integrating Lodata into your application, want to build
a Lodata-powered service or need new features then <a href="https://flat3.co">get in touch.</a>