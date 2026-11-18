# Octane

Lodata is compatible with [Laravel Octane](https://laravel.com/docs/8.x/octane) in both [Swoole](https://www.swoole.co.uk)
and [Roadrunner](https://roadrunner.dev) configurations.

The Lodata model is a [singleton](https://laravel.com/docs/8.x/container#binding-a-singleton) in the Laravel
service container. All requests use the same model, which allows the model to be dynamically updated at runtime without
requiring a server restart. Lodata does not mutate any internal data structures during the request cycle, making it safe
and extremely fast for multiple concurrent requests. Resources resolved while processing a request (entity sets,
singletons and operations) are cloned before any request-scoped state is attached to them, so the shared model
instance is never modified by a request.

Because the model is resolved once and kept resident in memory, the cost of [discovery](/getting-started/README.md#step-2-discovery)
is paid a single time when an Octane worker boots rather than on every request. This makes Octane an effective way to
remove discovery overhead from the request path for applications that discover a large number of models.

Note that Roadrunner [does not currently](https://github.com/spiral/roadrunner/issues/2) support [streaming responses](/internals/streaming-json.md)
so all output will buffer in memory before being sent to the client. Swoole responses will stream correctly, so this is
the recommended Octane option if your responses are likely to be large.