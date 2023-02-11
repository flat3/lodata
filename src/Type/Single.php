<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\PathSegment\OpenAPI;
use Flat3\Lodata\Property;

/**
 * Single
 * @package Flat3\Lodata\Type
 */
class Single extends Decimal
{
    const identifier = 'Edm.Single';

    public function getOpenAPISchema(?Property $property = null): array
    {
        return [
            'anyOf' => [
                OpenAPI::applyProperty($property, [
                    'type' => Constants::oapiNumber,
                    'format' => 'single',
                ]),
                OpenAPI::applyProperty($property, [
                    'type' => Constants::oapiString,
                    'enum' => [
                        Constants::negativeInfinity,
                        Constants::infinity,
                        Constants::notANumber,
                    ],
                ]),
            ],
        ];
    }
}
