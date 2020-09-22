<?php

namespace Flat3\OData\Type;

use DateTime;

class Date extends DateTimeOffset
{
    public const EDM_TYPE = 'Edm.Date';
    public const DATE_FORMAT = 'Y-m-d';

    protected function repack(DateTime $dt)
    {
        return $dt->setTime(0, 0, 0, 0);
    }
}
