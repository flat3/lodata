<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Guid
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Guid extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Guid
    {
        return new \Flat3\Lodata\Type\Guid($this->value);
    }
}
