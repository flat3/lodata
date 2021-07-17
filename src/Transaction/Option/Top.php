<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

/**
 * Top
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionstopandskip
 * @package Flat3\Lodata\Transaction\Option
 */
class Top extends Numeric
{
    public const param = 'top';
}
