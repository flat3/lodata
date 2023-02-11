<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Property;

/**
 * SByte
 * @package Flat3\Lodata\Type
 */
class SByte extends Byte
{
    const identifier = 'Edm.SByte';

    public const format = 'c';

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiInteger,
            'format' => 'int8',
            'minimum' => -128,
            'maximum' => 127,
        ]);
    }
}
