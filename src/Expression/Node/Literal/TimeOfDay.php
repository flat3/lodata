<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Time Of Day
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class TimeOfDay extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\TimeOfDay
    {
        return new \Flat3\Lodata\Type\TimeOfDay($this->value);
    }
}
