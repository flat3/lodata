<?php

namespace Flat3\Lodata\Facades;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Reference;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Singleton;
use Illuminate\Support\Facades\Facade;

/**
 * Lodata
 * @method static ObjectArray|EntityType[] getEntityTypes()
 * @method static ObjectArray|ResourceInterface[] getResources()
 * @method static ObjectArray|ServiceInterface[] getServices()
 * @method static ObjectArray|Reference[] getAnnotationReferences()
 * @method static ObjectArray|Annotation[] getAnnotations()
 * @method static ObjectArray|EnumerationType[] getEnumerationTypes()
 * @method static ResourceInterface getResource($name)
 * @method static EntitySet getEntitySet($name)
 * @method static Operation getOperation($name)
 * @method static FunctionInterface getFunction($name)
 * @method static ActionInterface getAction($name)
 * @method static EntityType getEntityType($name)
 * @method static Singleton getSingleton($name)
 * @method static IdentifierInterface add(IdentifierInterface $item)
 * @method static string getNamespace()
 * @method static EloquentEntitySet discoverEloquentModel(string $class)
 * @method static string getEndpoint()
 * @method static string getOdcUrl(string $set)
 * @method static string getPbidsUrl()
 * @package Flat3\Lodata\Facades
 */
class Lodata extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Model::class;
    }
}