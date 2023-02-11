<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Property;

/**
 * Int64
 * @package Flat3\Lodata\Type
 */
class Int64 extends Byte
{
    const identifier = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return OpenAPI::applyProperty($property, [
            'type' => Constants::oapiInteger,
            'format' => 'int64',
            'minimum' => PHP_INT_MIN,
            'maximum' => PHP_INT_MAX,
        ]);
    }
}
