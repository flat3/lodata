<?php

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Expression\Parser\Search as Parser;
use Flat3\Lodata\Transaction\Option;

/**
 * Search
 * @link http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionsearch
 * @package Flat3\Lodata\Transaction\Option
 */
class Search extends Option
{
    public const param = 'search';

    public function applyQuery(EntitySet $query): void
    {
        if (!$this->hasValue()) {
            return;
        }

        $parser = new Parser($query);

        $tree = $parser->generateTree($this->getValue());
        $tree->compute();
    }
}
