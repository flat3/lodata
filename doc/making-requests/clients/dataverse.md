# Microsoft Dataverse

Microsoft [PowerApps](https://powerapps.microsoft.com) also support importing OData Feeds.
Using a [dataflow](https://docs.microsoft.com/en-us/powerapps/maker/common-data-service/create-and-use-dataflows)
the data exposed by Lodata can be imported into the Common Data Service. When creating an OData data source
the UI will request the 'OData Endpoint', which can be programmatically generated and presented by your app
using:

```php
\Lodata::getEndpoint()
```