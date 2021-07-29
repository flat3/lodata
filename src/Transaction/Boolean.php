<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Helper\Constants;

/**
 * Boolean
 * @package Flat3\Lodata\Transaction
 */
abstract class Boolean
{
    protected $value = false;

    public function __construct(?string $value)
    {
        if (null === $value) {
            return;
        }

        $this->value = $value === Constants::true;
    }

    public function isTrue(): bool
    {
        return true === $this->value;
    }

    public function __toString()
    {
        return $this->value === true ? Constants::true : Constants::false;
    }
}
