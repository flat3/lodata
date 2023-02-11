<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * SByte
 * @package Flat3\Lodata\Type
 */
class SByte extends Byte
{
    const identifier = 'Edm.SByte';

    public const format = 'c';

    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiInteger,
            'format' => 'int8',
            'minimum' => -128,
            'maximum' => 127
        ];
    }
}
