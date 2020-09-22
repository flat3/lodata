<?php

namespace Flat3\OData\Attribute;

/**
 * Class IEEE754Compatible
 *
 * https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_ControllingtheRepresentationofNumber
 */
class IEEE754Compatible extends Boolean
{
    public function __construct(?Format $format)
    {
        $value = $format->getParameter('IEEE754Compatible');
        parent::__construct($value);
    }
}
