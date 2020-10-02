<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Resource\Operation\Action;
use Flat3\OData\Resource\Operation\Function_;
use Flat3\OData\Type\EntityType;

class ODataModel
{
    /** @var ObjectArray $resources */
    protected $model;

    public function __construct()
    {
        $this->model = new ObjectArray();
    }

    public static function add(IdentifierInterface $item): self
    {
        /** @var self $model */
        $model = app()->make(self::class);
        $model->model[] = $item;
        return $model;
    }

    public static function fn($identifier): Function_
    {
        /** @var self $model */
        $model = app()->make(self::class);

        $fn = new Function_($identifier);

        $model->model[] = $fn;
        return $fn;
    }

    public static function action($identifier): Action
    {
        /** @var self $model */
        $model = app()->make(self::class);

        $action = new Action($identifier);

        $model->model[] = $action;
        return $action;
    }

    public static function entitytype($identifier): EntityType
    {
        /** @var self $model */
        $model = app()->make(self::class);

        $type = new EntityType($identifier);

        $model->model[] = $type;
        return $type;
    }

    public static function getType($identifier): EntityType
    {
        /** @var self $model */
        $model = app()->make(self::class);

        return $model->getEntityTypes()->get($identifier);
    }

    public static function getResource($identifier): IdentifierInterface
    {
        /** @var self $model */
        $model = app()->make(self::class);

        return $model->getResources()->get($identifier);
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
