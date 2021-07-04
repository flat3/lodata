# CSV

Lodata can expose CSV files using the `CSVEntitySet` driver and the `CSVEntityType` entity type.
This driver is configured with a Laravel disk and a file path, and supports query and read operations including
sorting and pagination.

The `CSVEntityType` uses the key property `offset` referring to the CSV row number.
The entity type is further configured with the same field order as found in the CSV file.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entityType = new \Flat3\Lodata\Drivers\CSVEntityType('entry');
        $entityType->addDeclaredProperty('name', \Flat3\Lodata\Type::string());
        $entityType->addDeclaredProperty('dob', \Flat3\Lodata\Type::date());

        $entitySet = new \Flat3\Lodata\Drivers\CSVEntitySet('csv', $entityType);
        $entitySet->setDisk('default');
        $entitySet->setFilePath('example.csv');
        \Lodata::add($entitySet);
    }
}
```

Internally Lodata uses the [league/csv](https://csv.thephpleague.com) package to process CSV files.