<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\PrimitiveType;

trait HasPrimitiveType
{
    /** @var PrimitiveType $type */
    protected $type;

    public function getType(): ?PrimitiveType
    {
        return $this->type;
    }

    public function setType(PrimitiveType $type)
    {
        $this->type = $type;
        return $this;
    }
}
