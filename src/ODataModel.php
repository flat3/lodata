<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Resource\Operation\Action;
use Flat3\OData\Resource\Operation\Function_;
use Flat3\OData\Resource\Store;
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
        $fn = new Function_($identifier);
        self::add($fn);
        return $fn;
    }

    public static function action($identifier): Action
    {
        $action = new Action($identifier);
        self::add($action);
        return $action;
    }

    public static function entitytype($identifier): EntityType
    {
        $type = new EntityType($identifier);
        self::add($type);
        return $type;
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
