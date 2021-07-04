# Authentication

Lodata does not wrap the API in authentication by default to get the developer up and running fast, but it's easy to add.

The OData standard is
[light on recommendations](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_SecurityConsiderations)
for authentication, as theoretically any HTTP authentication type could be supported by the producer as long as the consumer understands it.

The only authentication type the OData standard does recommend is [HTTP Basic](https://tools.ietf.org/html/rfc7617),
and there's support in many consumers for this.

If you've [exported the configuration](/getting-started/configuration.md) you can add basic authentication to all
Lodata endpoints by modifying `config/lodata.php` to
include `auth.basic` in the array of middleware:

```php
...
/*
 * An array of middleware to be included when processing an OData request. Common middleware used would be to handle JWT authentication, or adding CORS headers.
 */
'middleware' => ['auth.basic'],
...
```

Similarly, if you are writing a [Single Page Application](https://laravel.com/docs/8.x/sanctum#how-it-works-spa-authentication)
protected by [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum)
you can [include](https://laravel.com/docs/8.x/sanctum#protecting-spa-routes) the `auth:sanctum` middleware.