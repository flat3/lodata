# Transactions

A `\Flat3\Lodata\Controller\Transaction` object is a representation of both the request (`\Flat3\Lodata\Controller\Request`) and
response (`\Flat3\Lodata\Controller\Response`) objects, handles piping the request from one path segment to the next, and provides
a variety of helper methods to generate context and get aspects of the request. Transaction also implements the streaming JSON encoder.

The OData `$expand` system query option, which can itself take system query parameters, creates a new transaction that represents
a sub-request within the main request, with a subclass of the Request object as a NavigationRequest. These can be further nested in
subrequests of `$expand` requests.

Transactions are also serializable for the purposes of async requests, and can therefore be handled offline, replayed, retried etc.

Transactions handle wrapping requests with database transactions, following OData rules for commit / rollback based on the success
or failure of the request.