<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * UInt16
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class UInt16 extends Int16
{
    const identifier = 'UInt16';

    const underlyingType = Int16::class;

    const openApiSchema = [
        'type' => Constants::oapiInteger,
        'format' => 'int16',
        'minimum' => 0,
        'maximum' => (2 ** 15) - 1,
    ];

    public const format = 'S';
}
