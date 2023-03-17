# Syncfusion

Syncfusion supports OData as a [data source](https://ej2.syncfusion.com/documentation/data/adaptors/#odatav4-adaptor).
However, it supports only OData 4.0, not OData 4.01. To support Lodata you must send the "OData-Version" header with
every request.

Alternatively, you can configure Lodata to default to OData 4.0 if the client does not specify a version by modifying
the [configuration](/getting-started/configuration.md).

This example shows how to send the header. The same technique can be applied to any component using `ODataAdaptor`.

```js
import { DataManager, ODataAdaptor, Query, ReturnOption } from '@syncfusion/ej2-data';

const SERVICE_URI: string = 'http://localhost:8000/odata/Users';

new DataManager({ url: SERVICE_URI, adaptor: new ODataAdaptor, headers:[{ 'odata-version': '4.0' }] })
    .executeQuery(new Query())
    .then((e: ReturnOption) => {
        // get result from e.result
    });
```