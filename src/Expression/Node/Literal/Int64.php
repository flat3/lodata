<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Int64
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Int64 extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Int64
    {
        return new \Flat3\Lodata\Type\Int64($this->value);
    }
}
