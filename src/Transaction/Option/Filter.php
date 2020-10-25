<?php

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Expression\Parser\Filter as Parser;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Transaction\Option;

/**
 * Class Filter
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionfilter
 */
class Filter extends Option
{
    public const param = 'filter';

    public function applyQuery(EntitySet $query, array $validLiterals = []): void
    {
        if (!$this->hasValue()) {
            return;
        }

        $parser = new Parser($query, $this->transaction);

        foreach ($validLiterals as $validLiteral) {
            $parser->addValidLiteral($validLiteral);
        }

        $tree = $parser->generateTree($this->getValue());
        $tree->compute();
    }
}
