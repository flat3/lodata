<?php

namespace Flat3\OData\Type;

use DateTime;

class TimeOfDay extends DateTimeOffset
{
    public const EDM_TYPE = 'Edm.TimeOfDay';
    public const DATE_FORMAT = 'H:i:s.u';

    protected function repack(DateTime $dt)
    {
        return $dt->setDate(1970, 1, 1);
    }
}
