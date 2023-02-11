<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Property;

/**
 * UInt16
 * @package Flat3\Lodata\Type
 */
class UInt16 extends Int16
{
    const identifier = 'UInt16';

    const underlyingType = Int16::class;

    public const format = 'S';

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiInteger,
            'format' => 'int16',
            'minimum' => 0,
            'maximum' => (2 ** 15) - 1,
        ]);
    }
}
