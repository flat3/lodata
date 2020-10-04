<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\IdentifierInterface;
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

    public static function add(IdentifierInterface $item): self
    {
        try {
            $model = app()->make(self::class);
            $model->model[] = $item;
            return $model;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function fn($identifier): FunctionOperation
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $fn = new FunctionOperation($identifier);

            $model->model[] = $fn;
            return $fn;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function action($identifier): ActionOperation
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $action = new ActionOperation($identifier);

            $model->model[] = $action;
            return $action;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public static function entitytype($identifier): EntityType
    {
        /** @var self $model */
        try {
            $model = app()->make(self::class);

            $type = new EntityType($identifier);

            $model->model[] = $type;
            return $type;
        } catch (BindingResolutionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @param $identifier
     * @return EntityType
     * @throws BindingResolutionException
     */
    public static function getType($identifier): EntityType
    {
        /** @var self $model */
        $model = app()->make(self::class);

        return $model->getEntityTypes()->get($identifier);
    }

    /**
     * @param $identifier
     * @return IdentifierInterface
     * @throws BindingResolutionException
     */
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
