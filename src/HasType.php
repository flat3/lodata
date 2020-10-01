<?php

namespace Flat3\OData;

trait HasType
{
    /** @var Type $type */
    protected $type;

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return $this->type->getName();
    }

    public function setType(Type $type): self
    {
        $this->type = $type;
        return $this;
    }
}
