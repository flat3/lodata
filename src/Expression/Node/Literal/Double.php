<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Double
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Double extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Double
    {
        return new \Flat3\Lodata\Type\Double($this->value);
    }
}
