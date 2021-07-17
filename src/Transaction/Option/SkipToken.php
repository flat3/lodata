<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;

/**
 * SkipToken
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServerDrivenPaging
 * @package Flat3\Lodata\Transaction\Option
 */
class SkipToken extends Option
{
    public const param = 'skiptoken';
}
