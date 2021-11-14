# Reporting issues

Report issues with Lodata using [GitHub Issues](https://github.com/flat3/lodata/issues).

When reporting an issue please include any configuration of entity sets or operations, and the complete HTTP request that is not working correctly.

Ideally to reproduce an issue you can generate a test case. Lodata uses [snapshot tests](https://github.com/spatie/phpunit-snapshot-assertions)
extensively to capture the entire output of the API.

Many examples exist in the test folder, for example [https://github.com/flat3/lodata/blob/main/tests/Unit/Eloquent/EloquentTest.php](https://github.com/flat3/lodata/blob/main/tests/Unit/Eloquent/EloquentTest.php)

Test cases that generate snapshots can use the provided assertions and the `Request` object to generate and configure a request.

This example creates a POST request with a body, sending to a particular path.
The assertion both checks the HTTP response code, and generates a snapshot.
```php
$this->assertJsonResponse(
    (new Request)
        ->post()
        ->body([
            'name' => 'Harry Horse',
        ])
        ->path('/Flights(1)/passengers'),
    Response::HTTP_CREATED
);
```
