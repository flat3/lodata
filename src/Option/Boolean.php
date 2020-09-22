<?php

namespace Flat3\OData\Option;

use Flat3\OData\Option;

abstract class Boolean extends Option
{
    public function setValue(?string $value): void
    {
        if (null === $value) {
            $this->value = null;

            return;
        }

        $this->value = \Flat3\OData\Type\Boolean::type()->factory($value)->getInternalValue();
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }
}
