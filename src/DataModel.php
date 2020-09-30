<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Type\EntityType;

class DataModel
{
    /** @var ObjectArray $resources */
    protected $model;

    public function __construct()
    {
        $this->model = new ObjectArray();
    }

    public function addEntityType(EntityType $entityType): self
    {
        $this->model[] = $entityType;
        return $this;
    }

    public function addResource(IdentifierInterface $resource): self
    {
        $this->model[] = $resource;
        return $this;
    }

    public function getNamespace(): string
    {
        return config('odata.namespace') ?: 'com.example.odata';
    }

    public function getEntityTypes(): ObjectArray
    {
        return $this->model->sliceByClass(EntityType::class);
    }

    public function getResources(): ObjectArray
    {
        return $this->model->sliceByClass(ResourceInterface::class);
    }
}
