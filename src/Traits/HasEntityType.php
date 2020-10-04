<?php

namespace Flat3\OData\Traits;

use Flat3\OData\EntityType;

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
