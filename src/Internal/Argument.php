<?php

namespace Flat3\OData\Internal;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Primitive;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Traits\HasType;

class Argument implements IdentifierInterface, TypeInterface
{
    use HasIdentifier;
    use HasType;

    protected $nullable = true;

    public function __construct(string $identifier, Primitive $type, bool $nullable = true)
    {
        $this->setIdentifier($identifier);
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public static function factory(string $identifier, Primitive $type, bool $nullable = true)
    {
        return new self($identifier, $type, $nullable);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
