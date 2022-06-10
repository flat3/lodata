<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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

        if (is_string($discoverable) && EnumerationType::isEnum($discoverable)) {
            EnumerationType::discover($discoverable);
            return;
        }

        if (is_a($discoverable, EloquentModel::class, true)) {
            if (Lodata::getEntitySet(EntitySet::convertClassName($discoverable))) {
                return;
            }

            (new EloquentEntitySet($discoverable))->discover();
            return;
        }

        Operation::discover($discoverable);
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

    public static function supportsEnum(): bool
    {
        return PHP_VERSION_ID > 80100;
    }

    public function remember($key, callable $callback)
    {
        return Cache::store(config('lodata.discovery.store'))
            ->remember(
                'lodata.discovery.'.$key,
                config('lodata.discovery.ttl', 0),
                $callback
            );
    }

    public static function getFirstAttribute($class, $type): ?ReflectionAttribute
    {
        return Arr::first($class->getAttributes($type));
    }
}