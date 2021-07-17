<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Double
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Double extends Decimal
{
    const identifier = 'Edm.Double';

    const openApiSchema = [
        'anyOf' => [
            [
                'type' => Constants::OAPI_NUMBER,
                'format' => 'double',
            ],
            [
                'enum' => [
                    Constants::NEG_INFINITY,
                    Constants::INFINITY,
                    Constants::NOT_A_NUMBER,
                ],
            ]
        ],
    ];
}
