<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Int32
 * @package Flat3\Lodata\Type
 */
class Int32 extends Byte
{
    const identifier = 'Edm.Int32';

    public const format = 'l';

    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiInteger,
            'format' => 'int32',
            'minimum' => -(2 ** 31),
            'maximum' => (2 ** 31) - 1,
        ];
    }
}
