<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * UInt64
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class UInt64 extends Int64
{
    const identifier = 'UInt64';

    const underlyingType = Int64::class;

    const openApiSchema = [
        'type' => Constants::OAPI_INTEGER,
        'format' => 'int64',
        'minimum' => 0,
        'maximum' => PHP_INT_MAX,
    ];

    protected function repack($value)
    {
        return abs((int) $value);
    }
}
