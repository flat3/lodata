# Enumerations

Lodata implements [Enumeration Types](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EnumerationType).
These have an underlying integer type, and can support bitwise operations when using 'flags'.

Enumerations can be discovered directly from PHP 8.1's [backed enumeration](https://www.php.net/manual/en/language.enumerations.backed.php)
objects, or defined manually.

<code-group>
<code-block title="Backed Enum">
```php
enum Colour: int
{
    case Red = 1;
    case Green = 2;
    case Blue = 4;
    case Brown = 8;
}

Lodata::discover(Colour::class);
```
</code-block>

<code-block title="Manual">
```php
$colour = Type::enum('Colours');
$colour[] = 'Red';
$colour[] = 'Green';
$colour[] = 'Blue';
$colour[] = 'Brown';
Lodata::add($colour);
```
</code-block>
</code-group>

Once defined, the type can be applied to an entity type:

```php
$type = new EntityType('person');
$type->addDeclaredProperty('socks', Lodata::getTypeDefinition('Colours'));
```

Lodata also supports the [flags](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_FlagsEnumerationType)
enumeration type, where multiple members can be selected simultaneously.

This can be defined with a hint on the `enum` object, or defined manually.

<code-group>
<code-block title="Backed Enum">
```php
enum Colour: int
{
    const isFlags = true;

    case Red = 1;
    case Green = 2;
    case Blue = 4;
    case Brown = 8;
}

Lodata::discover(Colour::class);
```
</code-block>

<code-block title="Manual">
```php
$colour = Type::enum('Colours')->setIsFlags();
$colour[] = 'Red';
$colour[] = 'Green';
$colour[] = 'Blue';
$colour[] = 'Brown';
Lodata::add($colour);
```
</code-block>
</code-group>

With the enumeration defined as using flags, the [`has`](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_BuiltinFilterOperations)
function can be used as a bitwise filter.