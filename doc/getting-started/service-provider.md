# Service Provider

When Lodata is installed via composer it [registers](https://laravel.com/docs/8.x/providers#registering-providers) a Service Provider.

This provider sets up an instance of `Flat3\Lodata\Model` as a singleton in the service container, and configures all the routes
and middleware. There are hooks in the [configuration](/getting-started/configuration) to modify the behaviour of the service provider.

::: details View the service provider
<<< @../../src/ServiceProvider.php
:::