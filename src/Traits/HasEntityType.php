<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\EntityType;

trait HasEntityType
{
    /** @var EntityType $type */
    protected $type;

    public function getType(): ?EntityType
    {
        return $this->type;
    }

    public function setType(EntityType $type)
    {
        $this->type = $type;
        return $this;
    }
}
