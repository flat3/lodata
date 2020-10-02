<?php

namespace Flat3\OData\Request\Option;

use Flat3\OData\Interfaces\TopInterface;

/**
 * Class Top
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionstopandskip
 */
class Top extends Numeric
{
    public const param = 'top';
    public const query_interface = TopInterface::class;
}
