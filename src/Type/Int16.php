<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Int16
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Int16 extends Byte
{
    const identifier = 'Edm.Int16';

    const openApiSchema = [
        'type' => Constants::OAPI_INTEGER,
        'format' => 'int16',
        'minimum' => -(2 ** 15),
        'maximum' => (2 ** 15) - 1,
    ];

    public const format = 's';
}
