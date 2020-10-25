<?php

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;

/**
 * Class Skip
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionstopandskip
 */
class Skip extends Numeric
{
    public const param = 'skip';
}
