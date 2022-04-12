<?php

declare(strict_types=1);

namespace Flat3\Lodata\Facades;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Reference;
use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Helper\Annotations;
use Flat3\Lodata\Helper\EntityContainer;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\References;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\Operation;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Singleton;
use Illuminate\Support\Facades\Facade;

/**
 * Lodata
 * @method static ObjectArray|EntityType[] getEntityTypes() Get the entity types attached to the model
 * @method static ObjectArray|ComplexType[] getComplexTypes() Get the complex types attached to the model
 * @method static ObjectArray|ResourceInterface[] getResources() Get the resources attached to the model
 * @method static ObjectArray|ServiceInterface[] getServices() Get the services attached to the model
 * @method static References|Reference[] getReferences() Get the annotation references attached to the model
 * @method static Annotations|Annotation[] getAnnotations() Get the annotations attached to the model
 * @method static ObjectArray|EnumerationType[] getEnumerationTypes() Get the enumeration types attached to the model
 * @method static ObjectArray|PrimitiveType[] getTypeDefinitions() Get custom type definitions
 * @method static ResourceInterface getResource(Identifier|string $name) Get a resource from the model
 * @method static EntitySet getEntitySet(Identifier|string $name) Get an entity set from the model
 * @method static Operation getOperation(Identifier|string $name) Get an operation from the model
 * @method static Operation getFunction(Identifier|string $name) Get a function from the model
 * @method static Operation getAction(Identifier|string $name) Get an action from the model
 * @method static EnumerationType getEnumerationType(Identifier|string $name) Get an enumeration type from the model
 * @method static PrimitiveType getTypeDefinition(Identifier|string $name) Get a type definition from the model
 * @method static EntityType getEntityType(Identifier|string $name) Get an entity type from the model
 * @method static ComplexType getComplexType(Identifier|string $name) Get a complex type from the model
 * @method static Singleton getSingleton(Identifier|string $name) Get a singleton from the model
 * @method static IdentifierInterface add(IdentifierInterface $item) Add a named resource or type to the model
 * @method static Model drop(Identifier|string $key) Drop a named resource or type from the model
 * @method static EntityContainer getEntityContainer() Get the entity container
 * @method static string getNamespace() Get the namespace of this model
 * @method static EloquentEntitySet discoverEloquentModel(string $class) Discover the Eloquent model provided as a class name
 * @method static Model discover($discoverable) Discover sets and operations on the provided class name or object instance
 * @method static string getEndpoint() Get the REST endpoint of this model
 * @method static string getOdcUrl(Identifier|string $set) Get the Office Data Connection URL of the provided entity set
 * @method static string getPbidsUrl() Get the PowerBI discovery URL of this service
 * @method static string getOpenApiUrl() Get the OpenAPI specification document URL of this service
 * @package Flat3\Lodata\Facades
 */
class Lodata extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Model::class;
    }
}