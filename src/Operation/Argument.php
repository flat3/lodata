<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\HasIdentifier;
use Flat3\OData\Type\PrimitiveType;

class Argument implements IdentifierInterface
{
    use HasIdentifier;

    /** @var PrimitiveType $type */
    protected $type;

    protected $nullable = true;

    public function __construct(string $identifier, PrimitiveType $type, bool $nullable = true)
    {
        $this->setIdentifier($identifier);
        $this->type = $type;
        $this->nullable = $nullable;
    }

    /**
     * @return PrimitiveType
     */
    public function getType(): PrimitiveType
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
