<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;

/**
 * SQL Where
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLWhere
{
    /**
     * Generate where clauses for filter and search parameters
     */
    protected function generateWhere(): SQLExpression
    {
        $expression = new SQLExpression($this);

        $filter = $this->getFilter();

        if ($filter->hasValue()) {
            $filterContainer = new SQLExpression($this);
            $filter = $this->getFilter();

            $parser = $this->getFilterParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($filter->getValue());
            $filterContainer->evaluate($tree);
            $expression->pushExpression($filterContainer);
        }

        $search = $this->getSearch();
        if ($search->hasValue()) {
            if ($this->getType()->getDeclaredProperties()->filter(function ($property) {
                return $property->isSearchable();
            })->isEmpty()) {
                throw new InternalServerErrorException(
                    'query_no_searchable_properties',
                    'The provided query had no properties marked searchable'
                );
            }

            $searchContainer = new SQLSearch($this);
            $search = $this->getSearch();

            $parser = $this->getSearchParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($search->getValue());
            $searchContainer->evaluate($tree);

            if ($expression->hasStatement()) {
                $expression->pushStatement('AND');
            }

            $expression->pushExpression($searchContainer);
        }

        return $expression;
    }
}
