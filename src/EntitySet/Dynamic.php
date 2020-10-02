<?php

namespace Flat3\OData\EntitySet;

use Flat3\OData\Entity;
use Flat3\OData\Resource\EntitySet;
use RuntimeException;

class Dynamic extends EntitySet
{
    protected function generate(): array
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