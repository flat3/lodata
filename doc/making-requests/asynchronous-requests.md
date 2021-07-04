# Asynchronous requests

The OData specification defines [asynchronous requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359016)
where the client indicates that it prefers the server to respond asynchronously via the `respond-async` Prefer header. This is helpful
for long-running [operations](#operations).

Lodata handles this by generating a Laravel [job](https://laravel.com/docs/8.x/queues#creating-jobs) which is then processed by
Laravel in the same way it handles any other queued job. For this to work your Laravel installation must have a working job queue.

When the client sends a request in this way, the server dispatches the job and returns to the client a monitoring URL. The client
can use this URL to retrieve the job output, or its status if not completed or failed.

The job runner will execute the OData request in the normal way, but will write the output to a Laravel [disk](https://laravel.com/docs/8.x/filesystem#obtaining-disk-instances)
for it to be picked up later. The name of this disk is set in the `disk` option in `config/lodata.php`. In a multi-server environment
this should be some type of shared storage such as NFS or AWS S3. The storage does not need to be client-facing, when the job output
is retrieved it is streamed to the client by the Laravel application.

Callbacks
Respond-async
Monitoring
