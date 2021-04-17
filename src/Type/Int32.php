<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Int32
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Int32 extends Byte
{
    const identifier = 'Edm.Int32';

    const openApiSchema = [
        'type' => Constants::OAPI_INTEGER,
        'format' => 'int32',
        "minimum" => -2147483648,
        "maximum" => 2147483647
    ];

    public const format = 'l';
}
