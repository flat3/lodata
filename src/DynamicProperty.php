<?php

declare(strict_types=1);

namespace Flat3\Lodata;

/**
 * Dynamic Property
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_DataModel
 * @package Flat3\Lodata
 */
class DynamicProperty extends Property
{
    protected $nullable = true;
}
