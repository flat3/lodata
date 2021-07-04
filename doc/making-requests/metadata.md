# Control information

The amount of control information needed (or desired) in the payload depends on the client application and device.
The metadata parameter can be applied to the Accept header of an OData request to influence how much control
information will be included in the response.

If a client prefers a very small wire size and is intelligent enough to compute data using metadata expressions,
the Accept header should include `metadata=minimal`. If computation is more critical than wire size or the client is
incapable of computing control information, `metadata=full` directs the service to inline the control information that
normally would be computed from metadata expressions in the payload. `metadata=none` is an option for clients that have
out-of-band knowledge or don't require control information.

Note that in OData 4.0 the metadata format parameter was prefixed with "odata.". Clients requiring this prefix must set the
OData-Version header to "4.0".

## Minimal

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/8
Accept: application/json;odata.metadata=minimal
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#People/$entity",
    "id": 8,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com"
}
```
</code-block>
</code-group>

## Full

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/8
Accept: application/json;odata.metadata=full
```
</code-block>

<code-block title="Response">
```json
{
    "@context": "http://localhost:8000/odata/$metadata#People/$entity",
    "@type": "#com.example.odata.Person",
    "@id": "http://localhost:8000/odata/People(8)",
    "@readLink": "http://localhost:8000/odata/People(8)",
    "id": 8,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com"
}
```
</code-block>
</code-group>

## None

<code-group>
<code-block title="Request">
```uri
GET http://localhost:8000/odata/People/8
Accept: application/json;odata.metadata=none
```
</code-block>

<code-block title="Response">
```json
{
    "id": 8,
    "FirstName": "Jason",
    "LastName": "Bourne",
    "Email": "jason.bourne@gmail.com"
}
```
</code-block>
</code-group>

## Further Information

For further information on the control information provided see
the [OData JSON format documentation](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControlInformation)