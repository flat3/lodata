<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * UInt64
 * @package Flat3\Lodata\Type
 */
class UInt64 extends Int64
{
    const identifier = 'UInt64';

    const underlyingType = Int64::class;

    const openApiSchema = [
        'type' => Constants::oapiInteger,
        'format' => 'int64',
        'minimum' => 0,
        'maximum' => PHP_INT_MAX,
    ];

    protected function repack($value)
    {
        return abs((int) $value);
    }
}
