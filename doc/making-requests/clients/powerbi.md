# Microsoft PowerBI

Microsoft [PowerBI](https://powerbi.microsoft.com) supports creating a live connection to an OData source via its
[PBIDS](https://docs.microsoft.com/en-us/power-bi/connect-data/desktop-data-sources#using-pbids-files-to-get-data)
document format.

The URL to the PBIDS document can be used in a "Connect to PowerBI" feature button. Unlike Excel which works on a single
entity set, this URL provides PowerBI with access to the whole model: [`http://127.0.0.1:8000/odata/_lodata/odata.pbids`](http://127.0.0.1:8000/odata/_lodata/odata.pbids)

This URL can be programmatically generated:
```php
\Lodata::getPbidsUrl()
```