<?php

namespace Flat3\OData\Transaction\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Helper\Constants;
use Flat3\OData\Transaction\Option;

abstract class Boolean extends Option
{
    public function setValue(?string $value): void
    {
        if (null === $value) {
            $this->value = null;

            return;
        }

        if (!in_array($value, [Constants::TRUE, Constants::FALSE])) {
            throw new BadRequestException(
                'option_boolean_invalid',
                sprintf('The value of $%s must be "true" or "false"', $this::param)
            );
        }

        $this->value = \Flat3\OData\Type\Boolean::factory($value)->get();
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }
}
