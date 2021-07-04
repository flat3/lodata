# Microsoft Excel

[Excel 2019](https://www.microsoft.com/en-gb/microsoft-365/excel) (and some earlier versions) support live updating
[OData Feeds](https://docs.microsoft.com/en-us/power-query/connectors/odatafeed) using Power Query.

As well as being able to create a connection in Excel using the UI, Lodata provides an easy way to add an
"Connect to Excel" button in your application using an [ODCFF](https://docs.microsoft.com/en-us/openspecs/office_file_formats/ms-odcff/09a237b3-a761-4847-a54c-eb665f5b0a6e)
document.

The URL provided for this button will be for a specific entity
set, for example if you have the `Users` entity set defined:
[`http://127.0.0.1:8000/odata/_lodata/Users.odc`](http://127.0.0.1:8000/odata/_lodata/Users.odc)

This URL can be programmatically generated:
```php
\Lodata::getOdcUrl('Users')
```
