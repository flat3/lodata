<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Option;

abstract class Boolean extends Option
{
    public function setValue(?string $value): void
    {
        if (null === $value) {
            $this->value = null;

            return;
        }

        if (!in_array($value, [\Flat3\OData\Type\Boolean::URL_TRUE, \Flat3\OData\Type\Boolean::URL_FALSE])) {
            throw new BadRequestException(
                'option_boolean_invalid',
                sprintf('The value of $%s must be "true" or "false"', $this::param)
            );
        }

        $this->value = \Flat3\OData\Type\Boolean::factory($value)->getInternalValue();
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }
}
