<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Entity;

interface FromEntityInterface
{
    public function fromEntity(Entity $entity);
}