<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\ComplexType;

trait HasComplexType
{
    /** @var ComplexType $type */
    protected $type;

    public function getType(): ?ComplexType
    {
        return $this->type;
    }

    public function setType(ComplexType $type)
    {
        $this->type = $type;
        return $this;
    }
}
