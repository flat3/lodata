# Property renaming

Lodata supports having different property names used in the schema compared to the backend driver. For example you
may have an OData property named `CustomerAge` which is named `customer_age` in a database table. To create a mapping
from Lodata property to backend property use the `setPropertySourceName()` method on the entity set object.

```php
$entitySet = Lodata::getEntitySet('passengers');
$ageProperty = $entitySet->getType()->getProperty('CustomerAge');
$entitySet->setPropertySourceName($ageProperty, 'customer_age');
```