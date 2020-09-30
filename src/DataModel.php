<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\ResourceInterface;

class DataModel
{
    /** @var ObjectArray $resources */
    protected $resources;

    /** @var ObjectArray $entityTypes */
    protected $entityTypes;

    public function __construct()
    {
        $this->resources = new ObjectArray();
        $this->entityTypes = new ObjectArray();
    }

    public function addEntityType(EntityType $entityType): self
    {
        $entityType->setDataModel($this);
        $this->entityTypes[] = $entityType;

        return $this;
    }

    public function addResource(ResourceInterface $resource): self
    {
        $this->resources[] = $resource;
        return $this;
    }

    public function getNamespace(): string
    {
        return config('odata.namespace') ?: 'com.example.odata';
    }

    public function getEntityTypes(): ObjectArray
    {
        return $this->entityTypes;
    }

    public function getResources(): ObjectArray
    {
        return $this->resources;
    }
}
