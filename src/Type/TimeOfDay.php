<?php

namespace Flat3\Lodata\Type;

use DateTime;

/**
 * Time Of Day
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class TimeOfDay extends DateTimeOffset
{
    const identifier = 'Edm.TimeOfDay';
    public const DATE_FORMAT = 'H:i:s.u';

    protected function repack(DateTime $dt)
    {
        return $dt->setDate(1970, 1, 1);
    }
}
