# DevExtreme

DevExtreme (by DevExpress) supports OData as a [data source](https://js.devexpress.com/Documentation/Guide/Data_Binding/Specify_a_Data_Source/OData/).
However, it supports only OData 4.0, not OData 4.01. To support Lodata you must send the "OData-Version" header with every request.

Alternatively, you can configure Lodata to default to OData 4.0 if the client does not specify a version by modifying the
[configuration](/getting-started/configuration.md).

This Vue example shows how to send the header, and load data into a DxDataGrid.

```vue
<template>
  <DxDataGrid
    :data-source="dataSource"
    :show-borders="true"
  >
    <DxColumn data-field="id"/>
    <DxColumn data-field="name"/>
    <DxColumn data-field="email"/>
  </DxDataGrid>
</template>
<script>
import 'devextreme/data/odata/store';
import { DxDataGrid, DxColumn } from 'devextreme-vue/data-grid';

export default {
  components: {
    DxDataGrid,
    DxColumn,
  },
  data() {
    return {
      dataSource: {
        store: {
          type: 'odata',
          url: 'http://localhost:8000/odata/Users',
          beforeSend: function (e) {  
            e.headers = {
              'OData-Version': '4.0'
            };
          },
          key: 'id',
          version: 4
        },
        select: [
          'id',
          'name',
          'email'
        ]
      },
    };
  },
};
</script>
```