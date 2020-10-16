<?php

namespace Flat3\Lodata\Type;

use DateTime;

class TimeOfDay extends DateTimeOffset
{
    protected $identifier = 'Edm.TimeOfDay';
    public const DATE_FORMAT = 'H:i:s.u';

    protected function repack(DateTime $dt)
    {
        return $dt->setDate(1970, 1, 1);
    }
}
