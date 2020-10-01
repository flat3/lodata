<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\WithFactory;
use Flat3\OData\HasIdentifier;
use Flat3\OData\Type\PrimitiveType;
use Flat3\OData\HasType;

class Argument implements IdentifierInterface, TypeInterface
{
    use WithFactory;
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
