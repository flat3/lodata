<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Parser\Filter as FilterParser;
use Flat3\Lodata\Expression\Parser\Search as SearchParser;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;

/**
 * SQL Where
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLWhere
{
    use SQLParameters;

    /**
     * The where clause
     * @var string $where
     * @internal
     */
    protected $where = '';

    /**
     * Add a statement to the where clause
     * @param  string  $where  Where clause
     */
    protected function addWhere(string $where): void
    {
        $this->where .= ' '.$where;
    }

    /**
     * Generate where clauses for filter and search parameters
     */
    protected function generateWhere(): void
    {
        $this->where = '';

        if ($this instanceof FilterInterface) {
            $filter = $this->getFilter();

            if ($filter->hasValue()) {
                $this->whereMaybeAnd();

                $filter = $this->getFilter();

                $parser = new FilterParser($this->getTransaction());
                $parser->pushEntitySet($this);

                $tree = $parser->generateTree($filter->getValue());
                $tree->compute();
            }
        }

        if ($this instanceof SearchInterface) {
            $search = $this->getSearch();
            if ($search->hasValue()) {
                if (!$this->getType()->getDeclaredProperties()->filter(function ($property) {
                    return $property->isSearchable();
                })->hasEntries()) {
                    throw new InternalServerErrorException(
                        'query_no_searchable_properties',
                        'The provided query had no properties marked searchable'
                    );
                }

                $this->whereMaybeAnd();
                $search = $this->getSearch();

                $parser = new SearchParser();
                $parser->pushEntitySet($this);

                $tree = $parser->generateTree($search->getValue());
                $tree->compute();
            }
        }
    }

    /**
     * Attach an AND statement to the where clause if required
     */
    protected function whereMaybeAnd(): void
    {
        if ($this->where) {
            $this->addWhere('AND');
        }
    }
}
