<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;

/**
 * Id
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ResolvinganEntityId
 * @package Flat3\Lodata\Transaction\Option
 */
class Id extends Option
{
    public const param = 'id';
}
