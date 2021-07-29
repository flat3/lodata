# Expressions

Lodata contains an expression parser in `\Flat3\Lodata\Expression` that handles both `$search` and `$filter` expressions. The
parser decodes the incoming expression into an [abstract syntax tree](https://en.m.wikipedia.org/wiki/Abstract_syntax_tree). During
entity set query processing the entity set driver will be passed every element of the tree in the correct parsing order, enabling it
to convert the OData query into a native query such as an SQL query.

Because not every possible OData function or operation is supported by every Laravel database driver, or the internal semantics of the
underlying database do not support the required data types, then a "Not Supported" exception may be thrown by some database drivers
and not others.

The behaviour of both the `$search` and `$filter` parameters can be overridden by extending the driver class, and the relevant methods.