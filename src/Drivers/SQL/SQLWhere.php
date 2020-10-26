<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;

trait SQLWhere
{
    use SQLParameters;

    /** @var string $where */
    protected $where = '';

    protected function addWhere(string $where): void
    {
        $this->where .= ' '.$where;
    }

    protected function generateWhere(): void
    {
        $this->where = '';

        if ($this instanceof FilterInterface) {
            $filter = $this->transaction->getFilter();
            if ($filter->hasValue()) {
                $this->whereMaybeAnd();
                $validLiterals = [];

                /** @var DeclaredProperty $property */
                foreach ($this->getType()->getDeclaredProperties() as $property) {
                    if ($property->isFilterable()) {
                        $validLiterals[] = (string) $property->getName();
                    }
                }

                $filter->applyQuery($this, $validLiterals);
            }
        }

        if ($this instanceof SearchInterface) {
            $search = $this->transaction->getSearch();
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
                $search->applyQuery($this);
            }
        }
    }

    protected function whereMaybeAnd(): void
    {
        if ($this->where) {
            $this->addWhere('AND');
        }
    }
}
