<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Int64
 * @package Flat3\Lodata\Type
 */
class Int64 extends Byte
{
    const identifier = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }

    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiInteger,
            'format' => 'int64',
            'minimum' => PHP_INT_MIN,
            'maximum' => PHP_INT_MAX,
        ];
    }
}
