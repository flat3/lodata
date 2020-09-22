<?php

namespace Flat3\OData\Attribute;

abstract class Boolean
{
    protected $value = false;

    public function __construct(?string $value)
    {
        if (null === $value) {
            return;
        }

        $this->value = $value === 'true';
    }

    public function setValue(bool $value)
    {
        $this->value = $value;
    }

    public function isTrue(): bool
    {
        return true === $this->value;
    }

    public function __toString()
    {
        return $this->value === true ? 'true' : 'false';
    }
}
