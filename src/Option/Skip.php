<?php

namespace Flat3\OData\Option;

use Flat3\OData\Option;

/**
 * Class Skip
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionstopandskip
 */
class Skip extends Option\Numeric
{
    public const param = 'skip';
}
