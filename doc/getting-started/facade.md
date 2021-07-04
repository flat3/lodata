# Facade

Lodata provides a [facade](https://laravel.com/docs/8.x/facades) mounted at `\Lodata` which references the model stored
in the [service container](https://laravel.com/docs/8.x/container) as a
[singleton](https://laravel.com/docs/8.x/container#binding-a-singleton).

This facade is the main entry point for configuring the Lodata model, and has a variety of useful methods.

::: details View the facade
<<< @../../src/Facades/Lodata.php
:::