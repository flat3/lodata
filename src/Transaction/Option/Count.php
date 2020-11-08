<?php

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;

/**
 * Count
 * @link http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptioncount
 * @package Flat3\Lodata\Transaction\Option
 */
class Count extends Option\Boolean
{
    public const param = 'count';
}
