# Errors

Lodata implements [Streaming JSON](../internals/streaming-json.md) by default. This is very efficient, but it can encounter a
situation where a fatal error occurs part way through sending a response, and after sending a successful HTTP code to
the client.

When this happens Lodata will leave the response as incomplete JSON, but will append
the header `OData-Error` as a [trailing header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Trailer)
if supported by the transport protocol (eg HTTP/1.1 with chunked transfer encoding, or HTTP/2).

Calling clients can disable the streaming behaviour by setting the `Accept` header with a parameter that includes
`streaming=false`.

For example: `Accept: application/json;streaming=false`. This will buffer the response server-side,
and return only error information and correct status codes.

Alternatively, you can globally disable the streaming behaviour by setting `streaming` to `false` in the `lodata.php`
[config file](../getting-started/configuration.md). Clients that want the streaming behaviour in this case can still
set the `Accept` header with a parameter of `streaming=true`.

Any calling client in streaming mode must be aware of this in order to deal with JSON responses that cannot be decoded, and in
this event should check the trailing header to get
[an error object](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ErrorResponse)
which will be well-formed.

An example OData error object:

```json
{
  "error": {
    "code": "no_handler",
    "message": "No route handler was able to process this request",
    "target": null,
    "details": [],
    "innererror": {}
  }
}
```