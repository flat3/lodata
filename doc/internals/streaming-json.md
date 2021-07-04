# Streaming JSON

Responses to OData requests can be of unlimited size. The request for an entity set without server-side pagination, of a database
table of many gigabytes, would generate a JSON document of at least that size. In order to process this efficiently, and without
running out of memory, Lodata implements a streaming JSON encoder. Through this method the memory usage of the responding PHP process
will stay very low.

Even if the request for the entity set is made with no pagination parameters, internally `\Flat3\Lodata\EntitySet` will implement
pagination against the database or other storage system so that that system is not overloaded. This process is invisible to the client.

When a path segment refers to an entity set, the initialization of that path segment sets up the query including all the filtering
options, but it is not executed to receive data from the data source until the content is actually emitted or an operation requests
data from it. For example in the SQL driver, the path segment generates the query, prepares and executes the query, but not until
the data is emitted does PDO start drawing data from the server and outputting it.