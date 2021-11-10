<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Boolean
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Boolean extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Boolean
    {
        return new \Flat3\Lodata\Type\Boolean($this->value);
    }
}
