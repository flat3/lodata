<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;

/**
 * Expand
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionexpand
 * @package Flat3\Lodata\Transaction\Option
 */
class Expand extends Option
{
    public const param = 'expand';
}
