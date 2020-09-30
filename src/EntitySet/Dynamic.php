<?php

namespace Flat3\OData\EntitySet;

use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Expression\Event;
use RuntimeException;

class Dynamic extends EntitySet
{
    public function filter(Event $event): ?bool
    {
        throw new BadRequestException('no_dynamic_entity_set_filters', 'Dynamic entity sets do not support filter');
    }

    public function search(Event $event): ?bool
    {
        throw new BadRequestException('no_dynamic_entity_set_search', 'Dynamic entity sets do not support search');
    }

    public function countResults(): int
    {
        return count($this->resultSet);
    }

    protected function generateResultSet(): void
    {
        throw new RuntimeException('Dynamic result sets cannot be generated');
    }

    public function addResult(Entity $entity): self
    {
        if (null === $this->resultSet) {
            $this->resultSet = [];
        }

        $this->resultSet[] = $entity;
        return $this;
    }
}