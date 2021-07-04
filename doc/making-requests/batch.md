# Batch Requests

Batch requests allow grouping multiple individual requests into a single HTTP request payload.

An individual request in the context of a batch request is a Metadata Request, Data Request, Data Modification Request,
Action invocation request, or Function invocation request.

Batch requests are submitted as a single HTTP POST request to the batch endpoint of a service, located at the URL
$batch relative to the service root, such as `http://localhost:8080/odata/$batch`.

Individual requests within a batch request are evaluated according to the same semantics used when the request
appears outside the context of a batch request.

Lodata supports both the OData [multipart batch format](https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_MultipartBatchFormat),
and the [JSON batch format](https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html).

## JSON

This example shows the JSON batch format.

<code-group>
<code-block title="Request">
```uri
POST http://localhost:8000/odata/$batch
{
  "requests": [
    {
      "id": 0,
      "method": "get",
      "url": "\/odata\/flights(1)"
    },
    {
      "id": 1,
      "method": "post",
      "url": "\/odata\/airports",
      "headers": {
        "content-type": "application\/json"
      },
      "body": {
        "name": "One",
        "code": "one"
      }
    },
    {
      "id": 2,
      "method": "patch",
      "headers": {
        "content-type": "application\/json",
        "if-match": "W\/\"73fa0e567cdc8392d1869d47b3f0886db629d38780a5f2010ce767900cde7266\""
      },
      "url": "\/odata\/airports(1)",
      "body": {
        "code": "xyz"
      }
    },
    {
      "id": 3,
      "method": "get",
      "url": "\/odata\/airports"
    }
  ]
}
```
</code-block>

<code-block title="Response">
```json
{
    "responses": [
        {
            "id": 0,
            "status": 200,
            "headers": {
                "content-type": "application/json",
                "etag": "W/\"2ccaaf443e26494dff243377cb72fb508b6dfad077dd4216f294be3fc0e7d0b5\""
            },
            "body": {
                "@context": "http://localhost:8000/odata/$metadata#flights/$entity",
                "id": 1,
                "origin": "lhr",
                "destination": "lax",
                "gate": null,
                "duration": "PT11H25M0S"
            }
        },
        {
            "id": 1,
            "status": 201,
            "headers": {
                "content-type": "application/json",
                "location": "http://localhost:8000/odata/airports(5)",
                "etag": "W/\"7f1bc052a54d9aed031b61b33efcee8e26c23b55f10814770f991b58d17c90e5\""
            },
            "body": {
                "@context": "http://localhost:8000/odata/$metadata#airports/$entity",
                "id": 5,
                "name": "One",
                "code": "one"
            }
        },
        {
            "id": 2,
            "status": 200,
            "headers": {
                "content-type": "application/json",
                "etag": "W/\"f593e92b83b424d5c98f33196d1cfff09a070417c1f5f37ffdb2a1451dd8343d\""
            },
            "body": {
                "@context": "http://localhost:8000/odata/$metadata#airports/$entity",
                "id": 1,
                "name": "Heathrow",
                "code": "xyz"
            }
        },
        {
            "id": 3,
            "status": 200,
            "headers": {
                "content-type": "application/json"
            },
            "body": {
                "@context": "http://localhost:8000/odata/$metadata#airports",
                "value": [
                    {
                        "id": 1,
                        "name": "Heathrow",
                        "code": "xyz"
                    },
                    {
                        "id": 2,
                        "name": "Los Angeles",
                        "code": "lax"
                    },
                    {
                        "id": 3,
                        "name": "San Francisco",
                        "code": "sfo"
                    },
                    {
                        "id": 4,
                        "name": "O'Hare",
                        "code": "ohr"
                    },
                    {
                        "id": 5,
                        "name": "One",
                        "code": "one"
                    }
                ]
            }
        }
    ]
}
```
</code-block>
</code-group>

## Multipart

This example shows the multipart batch format.

<code-group>
<code-block title="Request">
```
POST http://localhost:8000/odata/$batch
Content-Type: multipart/mixed; boundary=batch_36522ad7-fc75-4b56-8c71-56071383e77b

--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/flights(1)
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: multipart/mixed; boundary=changeset_77162fcd-b8da-41ac-a9f8-9357efbbd

--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd
Content-Type: application/http
Content-ID: 1

POST /odata/airports HTTP/1.1
Host: localhost
Content-Type: application/json

{
"name": "One",
"code": "one"
}
--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd
Content-Type: application/http
Content-ID: 2

PATCH /odata/airports(1) HTTP/1.1
Host: localhost
Content-Type: application/json
Prefer: return=minimal
If-Match: W/"73fa0e567cdc8392d1869d47b3f0886db629d38780a5f2010ce767900cde7266"

{
"code": "xyz"
}
--changeset_77162fcd-b8da-41ac-a9f8-9357efbbd--
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/airports HTTP/1.1
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
```
</code-block>

<code-block title="Response">
```
HTTP/1.1 200 OK
content-type: [multipart/mixed;boundary=00000000-0000-0000-0000-000000000001]
odata-version: ['4.01']

--00000000-0000-0000-0000-000000000001
content-type: application/http

HTTP/1.0 200 OK
content-type: application/json
etag: W/"2ccaaf443e26494dff243377cb72fb508b6dfad077dd4216f294be3fc0e7d0b5"

{"@context":"http://localhost:8000/odata/$metadata#flights/$entity","id":1,"origin":"lhr","destination":"lax","gate":null,"duration":"PT11H25M0S"}
--00000000-0000-0000-0000-000000000001
content-type: multipart/mixed;boundary=00000000-0000-0000-0000-000000000003


--00000000-0000-0000-0000-000000000003
content-type: application/http

HTTP/1.0 201 Created
content-type: application/json
location: http://localhost:8000/odata/airports(5)
etag: W/"7f1bc052a54d9aed031b61b33efcee8e26c23b55f10814770f991b58d17c90e5"

{"@context":"http://localhost:8000/odata/$metadata#airports/$entity","id":5,"name":"One","code":"one"}
--00000000-0000-0000-0000-000000000003
content-type: application/http

HTTP/1.1 204 No Content
preference-applied: return=minimal
odata-entityid: http://localhost:8000/odata/airports(1)
content-type: application/json


--00000000-0000-0000-0000-000000000003--

--00000000-0000-0000-0000-000000000001
content-type: application/http

HTTP/1.0 200 OK
content-type: application/json

{"@context":"http://localhost:8000/odata/$metadata#airports","value":[{"id":1,"name":"Heathrow","code":"xyz"},{"id":2,"name":"Los Angeles","code":"lax"},{"id":3,"name":"San Francisco","code":"sfo"},{"id":4,"name":"O'Hare","code":"ohr"},{"id":5,"name":"One","code":"one"}]}
--00000000-0000-0000-0000-000000000001--
```
</code-block>
</code-group>