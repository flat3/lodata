<?php

namespace Flat3\Lodata\Type;

use DateTime;

/**
 * Date
 * @package Flat3\Lodata\Type
 */
class Date extends DateTimeOffset
{
    const identifier = 'Edm.Date';
    public const DATE_FORMAT = 'Y-m-d';

    protected function repack(DateTime $dt)
    {
        return $dt->setTime(0, 0, 0, 0);
    }
}
