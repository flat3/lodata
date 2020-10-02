<?php

namespace Flat3\OData\Internal;

use Flat3\OData\Interfaces\FactoryInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Traits\HasFactory;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Traits\HasType;
use Flat3\OData\Type\PrimitiveType;

class Argument implements IdentifierInterface, TypeInterface, FactoryInterface
{
    use HasFactory;
    use HasIdentifier;
    use HasType;

    protected $nullable = true;

    public function __construct(string $identifier, PrimitiveType $type, bool $nullable = true)
    {
        $this->setIdentifier($identifier);
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
