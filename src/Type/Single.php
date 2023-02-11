<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Single
 * @package Flat3\Lodata\Type
 */
class Single extends Decimal
{
    const identifier = 'Edm.Single';

    public function getOpenAPISchema(): array
    {
        return [
            'anyOf' => [
                [
                    'type' => Constants::oapiNumber,
                    'format' => 'single',
                ],
                [
                    'enum' => [
                        Constants::negativeInfinity,
                        Constants::infinity,
                        Constants::notANumber,
                    ]
                ],
            ],
        ];
    }
}
