<?php

namespace Flat3\Lodata\Type;

use DateTime;

class Date extends DateTimeOffset
{
    protected $identifier = 'Edm.Date';
    public const DATE_FORMAT = 'Y-m-d';

    protected function repack(DateTime $dt)
    {
        return $dt->setTime(0, 0, 0, 0);
    }
}
