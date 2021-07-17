<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Transaction\Option;

/**
 * Select
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionselect
 * @package Flat3\Lodata\Transaction\Option
 */
class Select extends Option
{
    public const param = 'select';

    public function isStar(): bool
    {
        return $this->value === '*';
    }
}
