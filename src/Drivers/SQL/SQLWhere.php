<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Property;
use Flat3\Lodata\Transaction\NavigationRequest;

trait SQLWhere
{
    /** @var string $where */
    protected $where = '';

    protected function addWhere(string $where): void
    {
        $this->where .= ' '.$where;
    }

    protected function generateWhere(): void
    {
        $this->where = '';

        if ($this->key) {
            $this->addWhere($this->propertyToField($this->key->getProperty()).' = ?');
            $this->addParameter($this->key->getValue()->get());
            return;
        }

        $filter = $this->transaction->getFilter();
        if ($filter->hasValue()) {
            $this->whereMaybeAnd();
            $validLiterals = [];

            /** @var Property $property */
            foreach ($this->getType()->getDeclaredProperties() as $property) {
                if ($property->isFilterable()) {
                    $validLiterals[] = (string) $property->getName();
                }
            }

            $filter->applyQuery($this, $validLiterals);
        }

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

    protected function whereMaybeAnd(): void
    {
        if ($this->where) {
            $this->addWhere('AND');
        }
    }
}
