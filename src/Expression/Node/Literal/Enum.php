<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Enum
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Enum extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Enum
    {
        return $this->value;
    }
}
