<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Helper\ObjectArray;
use Illuminate\Contracts\Container\BindingResolutionException;
use RuntimeException;

class Model
{
    /** @var ObjectArray $resources */
    protected $model;

    public function __construct()
    {
        $this->model = new ObjectArray();
    }

    public static function add(NamedInterface $item): self
    {
        try {
            $model = app()->make(self::class);
            $model->model[] = $item;
            return $model;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function fn($name): FunctionOperation
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $fn = new FunctionOperation($name);

            $model->model[] = $fn;
            return $fn;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function action($name): ActionOperation
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $action = new ActionOperation($name);

            $model->model[] = $action;
            return $action;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function entitytype($name): EntityType
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $type = new EntityType($name);

            $model->model[] = $type;
            return $type;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @param $name
     * @return EntityType
     * @throws BindingResolutionException
     */
    public static function getType($name): EntityType
    {
        /** @var self $model */
        $model = app()->make(self::class);

        return $model->getEntityTypes()->get($name);
    }

    /**
     * @param $name
     * @return NamedInterface
     * @throws BindingResolutionException
     */
    public static function getResource($name): NamedInterface
    {
        /** @var self $model */
        $model = app()->make(self::class);

        return $model->getResources()->get($name);
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
