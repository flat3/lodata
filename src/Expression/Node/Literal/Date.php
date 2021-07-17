<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Date
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Date extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Date
    {
        return \Flat3\Lodata\Type\Date::factory($this->value);
    }
}
