<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Interfaces\TypeInterface;

trait HasType
{
    /** @var TypeInterface $type */
    protected $type;

    public function getType(): ?TypeInterface
    {
        return $this->type;
    }

    public function setType(TypeInterface $type)
    {
        $this->type = $type;
        return $this;
    }
}
