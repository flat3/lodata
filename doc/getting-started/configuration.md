# Configuration

To make changes from this point, it is recommended to install Lodata's configuration into your application:

```sh
php artisan vendor:publish \
  --provider="Flat3\Lodata\ServiceProvider" \
  --tag="config"
```

The default configuration looks like this:

<<< @/../config.php