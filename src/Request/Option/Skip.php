<?php

namespace Flat3\OData\Request\Option;

use Flat3\OData\Interfaces\SkipInterface;

/**
 * Class Skip
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionstopandskip
 */
class Skip extends Numeric
{
    public const param = 'skip';
    public const query_interface = SkipInterface::class;
}
