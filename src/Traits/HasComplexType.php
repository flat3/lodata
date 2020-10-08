<?php

namespace Flat3\OData\Traits;

use Flat3\OData\ComplexType;

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
