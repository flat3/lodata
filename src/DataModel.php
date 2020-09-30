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

    public function add(IdentifierInterface $item): self
    {
        $this->model[] = $item;
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
