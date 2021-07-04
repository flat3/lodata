# Introduction

You can use any HTTP client to interact with the Lodata service.

Lodata can render the OData API documented as an OpenAPI specification. The URL to the specification document is
[`http://127.0.0.1:8000/odata/openapi.json`](http://127.0.0.1:8000/odata/openapi.json) and can be imported by a
tool such as [Postman](https://postman.com).

To preview the specification online you can use a tool such as [Swagger UI](https://petstore.swagger.io). To use this service
with a development machine you may have to expose your API to the Internet using a tool such as [ngrok](https://ngrok.com),
and configure Laravel to use [CORS](https://laravel.com/docs/8.x/routing#cors).

Lodata has specific support for Microsoft Excel and PowerBI service discovery. Click one of the following URLs
to prompt Windows to open the feed in the relevant application:

- To load the `Users` model in Excel use [`http://127.0.0.1:8000/odata/_lodata/Users.odc`](http://127.0.0.1:8000/odata/_lodata/Users.odc)
- For PowerBI use [`http://127.0.0.1:8000/odata/_lodata/odata.pbids`](http://127.0.0.1:8000/odata/_lodata/odata.pbids).

Both Excel and PowerBI can now refresh the data source themselves using the Refresh buttons in those interfaces.

Any other consumer service requesting your "OData Endpoint" should accept the service document at
[`http://127.0.0.1:8000/odata/`](http://127.0.0.1:8000/odata/)