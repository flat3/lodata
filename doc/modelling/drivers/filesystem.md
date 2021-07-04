# Filesystem

Lodata supports read, update, create, delete and query options on Laravel filesystems.

The `FilesystemEntityType` provides an entity type starting point, with the key property set to an `Edm.String` typed key named
`path`. The `FilesystemEntitySet` can then be attached to expose the filesystem. The entity set supports a `setDisk`
method to set the filesystem to use.

```php
class LodataServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $entitySet = new \Flat3\Lodata\Drivers\FilesystemEntitySet('files', new \Flat3\Lodata\Drivers\FilesystemEntityType());
        $entitySet->setDisk('default');
        \Lodata::add($entitySet);
    }
}
```

The filesystem entity type supports a `Edm.Stream` property named `content` that can be requested with `$select` which
will encode the file into the body of the response. The URL to retrieve the file will also be provided in the body as
the metadata `content@mediaReadLink`.