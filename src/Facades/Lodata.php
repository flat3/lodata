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
 * @method static ObjectArray|EntityType[] getEntityTypes() Get the entity types attached to the model
 * @method static ObjectArray|ResourceInterface[] getResources() Get the resources attached to the model
 * @method static ObjectArray|ServiceInterface[] getServices() Get the services attached to the model
 * @method static ObjectArray|Reference[] getAnnotationReferences() Get the annotation references attached to the model
 * @method static ObjectArray|Annotation[] getAnnotations() Get the annotations attached to the model
 * @method static ObjectArray|EnumerationType[] getEnumerationTypes() Get the enumeration types attached to the model
 * @method static ResourceInterface getResource($name) Get a resource from the model
 * @method static EntitySet getEntitySet($name) Get an entity set from the model
 * @method static Operation getOperation($name) Get an operation from the model
 * @method static FunctionInterface getFunction($name) Get a function from the model
 * @method static ActionInterface getAction($name) Get an action from the model
 * @method static EntityType getEntityType($name) Get an entity type from the model
 * @method static Singleton getSingleton($name) Get a singleton from the model
 * @method static IdentifierInterface add(IdentifierInterface $item) Add a named resource or type to the OData model
 * @method static Model drop(string $key) Drop a named resource or type from the model
 * @method static string getNamespace() Get the namespace of this model
 * @method static EloquentEntitySet discoverEloquentModel(string $class) Discover the Eloquent model provided as a class name
 * @method static string getEndpoint() Get the REST endpoint of this OData model
 * @method static string getOdcUrl(string $set) Get the Office Data Connection URL of the provided entity set
 * @method static string getPbidsUrl() Get the PowerBI discovery URL of this service
 * @package Flat3\Lodata\Facades
 */
class Lodata extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Model::class;
    }
}