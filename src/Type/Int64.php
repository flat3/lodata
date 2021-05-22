<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Int64
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Int64 extends Byte
{
    const identifier = 'Edm.Int64';

    const openApiSchema = [
        'type' => Constants::OAPI_INTEGER,
        'format' => 'int64',
        'minimum' => PHP_INT_MIN,
        'maximum' => PHP_INT_MAX,
    ];

    protected function repack($value)
    {
        return (int) $value;
    }
}
