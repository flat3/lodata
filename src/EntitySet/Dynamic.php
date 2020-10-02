<?php

namespace Flat3\OData\EntitySet;

use Flat3\OData\Entity;
use Flat3\OData\Primitive;
use Flat3\OData\Resource\EntitySet;
use Flat3\OData\Transaction;
use RuntimeException;

class Dynamic extends EntitySet
{
    public function getEntity(Transaction $transaction, Primitive $key): ?Entity
    {
        return null;
    }

    public function count(): int
    {
        return count($this->results);
    }

    protected function generate(): void
    {
        throw new RuntimeException('Dynamic result sets cannot be generated');
    }

    public function toEntity($data): Entity
    {
        return new Entity();
    }

    public function addResult(Entity $entity): self
    {
        if (null === $this->results) {
            $this->results = [];
        }

        $this->results[] = $entity;
        return $this;
    }
}