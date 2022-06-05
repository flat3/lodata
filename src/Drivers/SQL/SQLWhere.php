<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

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
        $expression = $this->getSQLExpression();

        $filter = $this->getFilter();

        if ($filter->hasValue()) {
            $filterContainer = $this->getSQLExpression();
            $filter = $this->getFilter();

            $parser = $this->getFilterParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($filter->getExpression());
            $filterContainer->evaluate($tree);
            $expression->pushExpression($filterContainer);
        }

        $search = $this->getSearch();
        if ($search->hasValue()) {
            $this->assertValidSearch();

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
