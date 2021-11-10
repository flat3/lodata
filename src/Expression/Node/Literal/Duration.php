<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Duration
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Duration extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Duration
    {
        return new \Flat3\Lodata\Type\Duration($this->value);
    }
}
