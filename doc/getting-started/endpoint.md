# Service Endpoints

At this point we assume you already published the `lodata.php` config file to your project.

In case you want to distribute different service endpoints with your Laravel app, you can do so by providing one or more service endpoints to the package. This especially comes in handy when following a modularized setup.

Each of your modules could register its own service endpoint with an `\Flat3\Lodata\Endpoint` like this:

```php
/**
 * At the end of `config/lodata.php` 
 */
'endpoints' => [
    'projects' ⇒ \App\Projects\ProjectEndpoint::class,
],
```

With that configuration a separate `$metadata` service file will be available via `https://<server>:<port>/<lodata.prefix>/projects/$metadata`.

If the `endpoints` array stays empty (the default), only one global service endpoint is created.

## Selective Discovery

With endpoints, you can now discover all your entities and annotations in a separate class via the `discover` function. 

```php
use App\Model\Contact;
use Flat3\Lodata\Model;

/**
 * Discovers Schema and Annotations of the `$metadata` file for
 * the service.
 */
public function discover(Model $model): Model
{
    // register all of your $metadata capabilities
    $model->discover(Contact::class); 
    …
    return $model;
}
```

Furthermore, the `discover` function will only be executed when serving actual oData routes. This will enhance page speed for routes outside the `config('lodata.prefix')` URI space. 
