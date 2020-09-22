<?php

namespace Flat3\OData;

use Flat3\OData\Property\Navigation;

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

    public function add($add): self
    {
        switch (true) {
            case $add instanceof Resource:
                return $this->resource($add);

            case $add instanceof EntityType:
                return $this->entityType($add);
        }

        return $this;
    }

    public function resource(Resource $resource): self
    {
        $this->resources[] = $resource;

        if ($resource instanceof Store) {
            $this->entityType($resource->getEntityType());
        }

        return $this;
    }

    public function entityType(EntityType $entityType): self
    {
        $entityType->setDataModel($this);
        $this->entityTypes[] = $entityType;

        return $this;
    }

    public function getNamespace(): string
    {
        return config('odata.namespace') ?: 'com.example.odata';
    }

    public function quickBinding(
        string $sourceType,
        string $targetType,
        string $sourceStore,
        string $targetStore,
        string $sourceProperty,
        string $targetProperty
    ): Navigation {
        $sourceType = $this->getEntityTypes()->get($sourceType);
        $sourceStore = $this->getResources()->get($sourceStore);
        $targetType = $this->getEntityTypes()->get($targetType);
        $targetStore = $this->getResources()->get($targetStore);

        $nav = new Property\Navigation($targetStore, $targetType);
        $nav->addConstraint(new Property\Constraint(
            $sourceType->getProperty($sourceProperty),
            $targetType->getProperty($targetProperty)
        ));

        $sourceType->addProperty($nav);
        $sourceStore->addNavigationBinding(
            new Property\Navigation\Binding(
                $nav,
                $targetStore
            )
        );

        return $nav;
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
