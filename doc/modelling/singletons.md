# Singletons

Singletons are named entities which can be accessed as direct children of the entity container. This example shows
a singleton that shows configuration information about the application:

<code-group>
<code-block title="Code">
```php
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Singleton;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $Tconfig = new EntityType( 'config' );
        $Tconfig->addDeclaredProperty( 'name', Type::string() );
        $Tconfig->addDeclaredProperty( 'version', Type::string() );
        $Tconfig->addDeclaredProperty( 'session_lifetime', Type::int64() );

        $config = new Singleton( 'config', $Tconfig );

        $config['name'] = config( 'app.name' );
        $config['version'] = App::version();
        $config['session_lifetime'] = config('session.lifetime');

        \Lodata::add( $config );
    }
}

```
</code-block>

<code-block title="Request">
```
http://127.0.0.1:8000/odata/config
```
</code-block>

<code-block title="Result">
```json
{
    "@context": "http://127.0.0.1:8000/odata/$metadata#com.example.odata.config",
    "name": "Laravel",
    "version": "8.51.0",
    "session_lifetime": 120
}
```
</code-block>
</code-group>