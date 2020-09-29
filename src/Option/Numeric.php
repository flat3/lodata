<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Option;

abstract class Numeric extends Option
{
    public function setValue(?string $value): void
    {
        if (null === $value) {
            $this->value = null;

            return;
        }

        if (!is_numeric($value)) {
            throw new BadRequestException('option_not_numeric',
                sprintf('The type of $%s must be numeric', $this::param));
        }

        if ($value < 0) {
            throw new BadRequestException('option_numeric_invalid',
                sprintf('The value of $%s must be greater than zero', $this::param));
        }

        $this->value = (int) $value;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}
