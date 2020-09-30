<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Resource;
use Flat3\OData\Type\PrimitiveType;

class Argument extends Resource
{
    /** @var \Flat3\OData\Type\PrimitiveType $type */
    protected $type;

    protected $nullable = true;

    public function __construct(string $identifier, PrimitiveType $type, bool $nullable = true)
    {
        parent::__construct($identifier);

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
