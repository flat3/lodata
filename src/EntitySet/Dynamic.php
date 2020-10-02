<?php

namespace Flat3\OData\EntitySet;

use Flat3\OData\Entity;
use Flat3\OData\Primitive;
use Flat3\OData\Resource\EntitySet;
use Flat3\OData\Transaction;
use RuntimeException;

class Dynamic extends EntitySet
{
    public function getEntity(Primitive $key): ?Entity
    {
        return null;
    }

    protected function generate(): void
    {
        throw new RuntimeException('Dynamic result sets cannot be generated');
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