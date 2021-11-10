<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Transaction\Option;

/**
 * Boolean
 * @package Flat3\Lodata\Transaction\Option
 */
abstract class Boolean extends Option
{
    public function setValue(?string $value): void
    {
        if (null === $value) {
            $this->value = null;

            return;
        }

        if (!in_array($value, [Constants::true, Constants::false])) {
            throw new BadRequestException(
                'option_boolean_invalid',
                sprintf('The value of $%s must be "true" or "false"', $this::param)
            );
        }

        $this->value = (new \Flat3\Lodata\Type\Boolean($value))->get();
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }
}
