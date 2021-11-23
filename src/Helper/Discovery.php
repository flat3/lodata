<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Attributes\LodataOperation;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Drivers\EloquentOperation;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\RepositoryInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\Repository;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class Discovery
{
    public function discover($discoverable)
    {
        if (is_string($discoverable) && !class_exists($discoverable)) {
            throw new InternalServerErrorException(
                'missing_class',
                'Discovery was passed an item that was not a class'
            );
        }

        if (is_a($discoverable, EloquentModel::class, true)) {
            $this->discoverEloquentModel($discoverable);
        }

        if (!Discovery::supportsAttributes()) {
            return;
        }

        $reflectionClass = new ReflectionClass($discoverable);

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            foreach ($reflectionMethod->getAttributes(
                LodataOperation::class,
                ReflectionAttribute::IS_INSTANCEOF
            ) as $attribute) {
                /** @var LodataOperation $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $operationName = $attributeInstance->getName() ?: $reflectionMethod->getName();

                switch (true) {
                    case is_a($discoverable, EloquentModel::class, true):
                        $operation = new EloquentOperation($operationName);
                        break;

                    case is_a($discoverable, RepositoryInterface::class, true):
                        $operation = new Repository($operationName);
                        break;

                    default:
                        $operation = new Operation($operationName);
                        break;
                }

                $operation->setKind($attributeInstance::operationType);
                $operation->setCallable([$discoverable, $reflectionMethod->getName()]);

                if ($attributeInstance->hasBindingParameterName()) {
                    $operation->setBindingParameterName($attributeInstance->getBindingParameterName());
                }

                if ($attributeInstance->hasReturnType()) {
                    $operation->setReturnType($attributeInstance->getReturnType());
                }

                Lodata::add($operation);
            }
        }
    }

    public function discoverEloquentModel(string $model): EloquentEntitySet
    {
        $set = new EloquentEntitySet($model);
        Lodata::add($set);
        $set->discoverProperties();

        return $set;
    }

    public static function supportsAttributes(): bool
    {
        return PHP_VERSION_ID > 80000;
    }
}