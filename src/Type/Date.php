<?php

namespace Flat3\Lodata\Type;

use DateTime;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Constants;

/**
 * Date
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Date extends DateTimeOffset
{
    const identifier = 'Edm.Date';

    const openApiSchema = [
        'type' => Constants::OAPI_STRING,
        'format' => 'date',
        'pattern' => '^'.Lexer::DATE.'$',
    ];

    public const DATE_FORMAT = 'Y-m-d';

    protected function repack(DateTime $dt)
    {
        return $dt->setTime(0, 0, 0, 0);
    }
}
