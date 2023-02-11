<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Double
 * @package Flat3\Lodata\Type
 */
class Double extends Decimal
{
    const identifier = 'Edm.Double';

    public function getOpenAPISchema(): array
    {
        return [
            'anyOf' => [
                [
                    'type' => Constants::oapiNumber,
                    'format' => 'double',
                ],
                [
                    'enum' => [
                        Constants::negativeInfinity,
                        Constants::infinity,
                        Constants::notANumber,
                    ],
                ]
            ]
        ];
    }
}
