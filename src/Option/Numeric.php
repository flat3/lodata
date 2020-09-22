<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\BadRequestException;
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
            throw new BadRequestException(sprintf('The type of $%s must be numeric', $this::param));
        }

        $this->value = (int) $value;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}
