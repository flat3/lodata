# Asynchronous requests

The OData specification defines [asynchronous requests](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31359016)
where the client indicates that it prefers the server to respond asynchronously via the `respond-async` Prefer header. This is helpful
for long-running [operations](#operations).

Lodata handles this by generating a Laravel [job](https://laravel.com/docs/8.x/queues#creating-jobs) which is then processed by
Laravel in the same way it handles any other queued job. For this to work your Laravel installation must have a working job queue.

When the client sends a request in this way, the server dispatches the job and returns to the client a monitoring URL. The client
can use this URL to retrieve the job output, or its status if not completed or failed. The client can also provide a callback URL
to be notified when the job is complete.

The job runner will execute the OData request in the normal way, but will write the output to a Laravel [disk](https://laravel.com/docs/8.x/filesystem#obtaining-disk-instances)
for it to be picked up later. The name of this disk is set in the `disk` option in `config/lodata.php`. In a multi-server environment
this should be some type of shared storage such as NFS or AWS S3. The storage does not need to be client-facing, when the job output
is retrieved it is streamed to the client by the Laravel application.

## Sending a request

When dispatching a request with `Prefer: respond-async`, Lodata will return a `202 Accepted` header, and a `Location`
header with a URL that can be used to monitor the progress of the request and retrieve the result.

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People
Prefer: respond-async
```
</code-block>

<code-block title="Response">
```
HTTP/1.1 202 Accepted
location: http://localhost:8000/odata/_lodata/monitor/b6534e03-564b-4a74-abe6-a912483688e6
```
```json
{
    "error": {
        "code": "accepted",
        "message": "Accepted",
        "target": null,
        "details": [],
        "innererror": {}
    }
}
```
</code-block>
</code-group>

## Monitoring the request

The request can be monitored on the returned URL. If the job is not started or in progress the monitoring URL
will return `202 Accepted`. If it is complete then it will return the result. The HTTP response code of the monitoring
URL will be `200 OK`, to check the HTTP response code of the request itself check the `asyncresult` response header.

::: warning
The result of the request can only be retrieved once. After it is retrieved further requests to the monitoring URL
will return `404 Not Found`.
:::

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/_lodata/monitor/b6534e03-564b-4a74-abe6-a912483688e6
```
</code-block>

<code-block title="Response">
```
HTTP/1.1 200 OK
odata-version: 4.01
asyncresult: 200
```
```json
{
  "@context": "http://127.0.0.1:8000/odata/$metadata#People",
  "value": [
    {
      "id": "michael-caine",
      "name": "Michael Caine"
    },
    {
      "id": "bob-hoskins",
      "name": "Bob Hoskins"
    }
  ]
}
```
</code-block>
</code-group>

## Cancelling the request

If the request is pending then it can be cancelled by sending a DELETE request to the monitoring URL:

<code-group>
<code-block title="Request">
```uri
DELETE http://localhost:8000/odata/_lodata/monitor/b6534e03-564b-4a74-abe6-a912483688e6
```
</code-block>

<code-block title="Response">
```
HTTP/1.1 200 OK
odata-version: 4.01
```
</code-block>
</code-group>

## Using a callback

The client can be notified when the request is complete by providing a callback URL. When the request is complete
the service will make a GET request to the provided callback. No payload or query parameters are added, the client
must provide a callback URL that contains any tracking information needed to match the original request.

```uri
GET http://localhost:8000/odata/People
Prefer: respond-async,callback;url=https://client.example.com/callback
```