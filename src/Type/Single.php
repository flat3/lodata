<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * Single
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Single extends Decimal
{
    const identifier = 'Edm.Single';

    const openApiSchema = [
        'anyOf' => [
            [
                'type' => Constants::OAPI_NUMBER,
                'format' => 'single',
            ],
            [
                'enum' => [
                    Constants::NEG_INFINITY,
                    Constants::INFINITY,
                    Constants::NOT_A_NUMBER,
                ]
            ],
        ],
    ];
}
