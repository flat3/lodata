<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * DateTimeOffset
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class DateTimeOffset extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\DateTimeOffset
    {
        return new \Flat3\Lodata\Type\DateTimeOffset($this->value);
    }
}
