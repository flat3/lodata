<?php

namespace Flat3\OData\Request\Option;

use Flat3\OData\EntitySet;
use Flat3\OData\Expression\Parser\Search as Parser;
use Flat3\OData\Interfaces\SearchInterface;
use Flat3\OData\Request\Option;

/**
 * Class Search
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionsearch
 */
class Search extends Option
{
    public const param = 'search';
    public const query_interface = SearchInterface::class;

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
