<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Attributes\LodataNamespace;
use Flat3\Lodata\Attributes\LodataOperation;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\RepositoryInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Operation\EntityFunction;
use Flat3\Lodata\Operation\Repository;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class Discovery
{
    public function discover($discoverable)
    {
        if (is_string($discoverable) && !class_exists($discoverable)) {
            throw new ConfigurationException(
                'missing_class',
                'Discovery was passed an item that was not a class'
            );
        }

        if (is_a($discoverable, EloquentModel::class, true)) {
            $this->discoverEloquentModel($discoverable);
        }

        $this->discoverOperations($discoverable);
    }

    public function discoverEloquentModel(string $model): EloquentEntitySet
    {
        $set = new EloquentEntitySet($model);
        Lodata::add($set);
        $set->discoverProperties();

        if (!Discovery::supportsAttributes()) {
            return $set;
        }

        foreach (Discovery::getReflectedMethods($model) as $reflectionMethod) {
            if (!$reflectionMethod->getAttributes(LodataRelationship::class, ReflectionAttribute::IS_INSTANCEOF)) {
                continue;
            }

            $relationshipMethod = $reflectionMethod->getName();

            try {
                $set->discoverRelationship($relationshipMethod);
            } catch (ConfigurationException $e) {
            }
        }

        return $set;
    }

    public function discoverOperations($discoverable)
    {
        if (!Discovery::supportsAttributes()) {
            return;
        }

        $namespace = null;
        $reflectionClass = new ReflectionClass($discoverable);

        /** @var ReflectionAttribute $namespaceAttribute */
        $namespaceAttribute = Arr::first($reflectionClass->getAttributes(LodataNamespace::class));
        if ($namespaceAttribute) {
            $namespace = $namespaceAttribute->newInstance()->getName();
        }

        foreach (Discovery::getReflectedMethods($discoverable) as $reflectionMethod) {
            foreach ($reflectionMethod->getAttributes(
                LodataOperation::class,
                ReflectionAttribute::IS_INSTANCEOF
            ) as $operationAttribute) {
                /** @var LodataOperation $attributeInstance */
                $attributeInstance = $operationAttribute->newInstance();

                $operationName = $attributeInstance->getName() ?: $reflectionMethod->getName();

                switch (true) {
                    case is_a($discoverable, EloquentModel::class, true):
                        $operation = new EntityFunction($operationName);
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

                if ($namespace) {
                    $operation->getIdentifier()->setNamespace($namespace);
                }

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

    public static function getReflectedMethods($class): array
    {
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
    }

    public static function supportsAttributes(): bool
    {
        return PHP_VERSION_ID > 80000;
    }
}