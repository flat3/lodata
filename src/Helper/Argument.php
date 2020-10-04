<?php

namespace Flat3\OData\Helper;

use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasType;

class Argument implements TypeInterface
{
    use HasName;
    use HasType;

    protected $nullable = true;

    public function __construct(string $identifier, PrimitiveType $type, bool $nullable = true)
    {
        $this->setName($identifier);
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public static function factory(string $identifier, PrimitiveType $type, bool $nullable = true)
    {
        return new self($identifier, $type, $nullable);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
