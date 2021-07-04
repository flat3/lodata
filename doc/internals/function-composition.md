# Function composition

OData URLs are parsed using composition, with each path segment being piped to the next using a static `pipe()` method on path
segment classes, with the final segment in the chain being responsible for handling the system query options and generating
the response via the `response()` method.

Operations can therefore act on path segments that precede them as [bound parameters](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_BindinganOperationtoaResource), and the output of one operation can be piped
into the next. The output can therefore pass through several functions before being output.