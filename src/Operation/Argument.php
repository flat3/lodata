<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Resource;
use Flat3\OData\Type;

class Argument extends Resource
{
    /** @var Type $type */
    protected $type;

    protected $nullable = true;

    public function __construct(string $identifier, Type $type, bool $nullable = true)
    {
        parent::__construct($identifier);

        $this->type = $type;
        $this->nullable = $nullable;
    }

    /**
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
