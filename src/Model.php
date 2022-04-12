<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities;
use Flat3\Lodata\Annotation\Core;
use Flat3\Lodata\Annotation\Reference;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Helper\EntityContainer;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\References;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasAnnotations;

/**
 * Model
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530335
 * @package Flat3\Lodata
 */
class Model implements AnnotationInterface
{
    use HasAnnotations;

    /**
     * The OData model
     * @var ObjectArray $model
     */
    protected $model;

    /**
     * References to external CSDL documents
     * @var Reference[]|References $references
     */
    protected $references;

    /**
     * The model's entity container
     * @var EntityContainer $entityContainer
     */
    protected $entityContainer;

    public function __construct()
    {
        $this->model = new ObjectArray();
        $this->entityContainer = new EntityContainer();
        $this->references = new References();

        $this->addReference(new Core\V1\Reference());
        $this->addReference(new Capabilities\V1\Reference());

        $this->addAnnotation(new Core\V1\ConventionalIDs());
        $this->addAnnotation(new Core\V1\DefaultNamespace());
        $this->addAnnotation(new Core\V1\DereferencableIDs());
        $this->addAnnotation(new Core\V1\ODataVersions());
        $this->addAnnotation(new Capabilities\V1\AsynchronousRequestsSupported());
        $this->addAnnotation(new Capabilities\V1\BatchSupported());
        $this->addAnnotation(new Capabilities\V1\BatchSupport());
        $this->addAnnotation(new Capabilities\V1\CallbackSupported());
        $this->addAnnotation(new Capabilities\V1\ConformanceLevel());
        $this->addAnnotation(new Capabilities\V1\KeyAsSegmentSupported());
        $this->addAnnotation(new Capabilities\V1\QuerySegmentSupported());
        $this->addAnnotation(new Capabilities\V1\SupportedFormats());
        $this->addAnnotation(new Capabilities\V1\SupportedMetadataFormats());
    }

    /**
     * Add a named resource or type to the OData model
     * @param  IdentifierInterface  $item  Resource or type
     * @return IdentifierInterface Resource or type
     */
    public function add(IdentifierInterface $item): IdentifierInterface
    {
        if ($item instanceof ServiceInterface) {
            $this->entityContainer->add($item);
        } else {
            $this->model->add($item);
        }

        return $item;
    }

    /**
     * Get an entity type from the model
     * @param  string  $name  Entity type name
     * @return EntityType|null Entity type
     */
    public function getEntityType(string $name): ?EntityType
    {
        return $this->getEntityTypes()->get($name);
    }

    /**
     * Get a complex type from the model
     * @param  string  $name  Complex type name
     * @return ComplexType|null Complex type
     */
    public function getComplexType(string $name): ?ComplexType
    {
        return $this->getComplexTypes()->get($name);
    }

    /**
     * Get a singleton from the model
     * @param  string  $name  Singleton name
     * @return Singleton|null Singleton
     */
    public function getSingleton(string $name): ?Singleton
    {
        $resource = $this->getResource($name);
        return $resource instanceof Singleton ? $resource : null;
    }

    /**
     * Get a resource from the model
     * @param  string  $name  Resource name
     * @return IdentifierInterface|null Resource
     */
    public function getResource(string $name): ?IdentifierInterface
    {
        return $this->getResources()->get($name);
    }

    /**
     * Get an entity set from the model
     * @param  string  $name  Entity set name
     * @return EntitySet|null Entity set
     */
    public function getEntitySet(string $name): ?EntitySet
    {
        $resource = $this->getResource($name);
        return $resource instanceof EntitySet ? $resource : null;
    }

    /**
     * Get an operation from the model
     * @param  string  $name  Operation name
     * @return Operation|null Operation
     */
    public function getOperation(string $name): ?Operation
    {
        $resource = $this->getResource($name);
        return $resource instanceof Operation ? $resource : null;
    }

    /**
     * Get an enumeration type from the model
     * @param  string  $name  Enumeration name
     * @return EnumerationType|null Enumeration type
     */
    public function getEnumerationType(string $name): ?EnumerationType
    {
        return $this->getEnumerationTypes()->get($name);
    }

    /**
     * Get a function from the model
     * @param  string  $name  Function name
     * @return Operation|null Function
     */
    public function getFunction(string $name): ?Operation
    {
        /** @var Operation $resource */
        $resource = $this->getResource($name);
        return $resource && $resource->isFunction() ? $resource : null;
    }

    /**
     * Get an action from the model
     * @param  string  $name  Action name
     * @return Operation|null Action
     */
    public function getAction(string $name): ?Operation
    {
        /** @var Operation $resource */
        $resource = $this->getResource($name);
        return $resource && $resource->isAction() ? $resource : null;
    }

    /**
     * Get a type definition from the model
     * @param  string  $name  Action name
     * @return Type|null Action
     */
    public function getTypeDefinition(string $name): ?Type
    {
        return $this->getTypeDefinitions()->get($name);
    }

    /**
     * Get the namespace of this model
     * @return string Namespace
     */
    public static function getNamespace(): string
    {
        return config('lodata.namespace');
    }

    /**
     * Drop a named resource or type from the model
     * @param  IdentifierInterface  $item  Resource or type
     * @return $this
     */
    public function drop(IdentifierInterface $item): self
    {
        if ($item instanceof ServiceInterface) {
            $this->entityContainer->drop($item);
        } else {
            $this->model->drop($item);
        }

        return $this;
    }

    /**
     * Get the entity types attached to the model
     * @return ObjectArray Entity types
     */
    public function getEntityTypes(): ObjectArray
    {
        return $this->model->sliceByClass(EntityType::class);
    }

    /**
     * Get the complex types attached to the model
     * @return ObjectArray Complex types
     */
    public function getComplexTypes(): ObjectArray
    {
        return $this->model->sliceByClass(ComplexType::class);
    }

    /**
     * Get the enumeration types attached to the model
     * @return ObjectArray Enumeration types
     */
    public function getEnumerationTypes(): ObjectArray
    {
        return $this->model->sliceByClass(EnumerationType::class);
    }

    /**
     * Get the resources attached to the model
     * @return ObjectArray Resources
     */
    public function getResources(): ObjectArray
    {
        return $this->entityContainer->sliceByClass(ResourceInterface::class);
    }

    /**
     * Get the services attached to the model
     * @return ObjectArray Services
     */
    public function getServices(): ObjectArray
    {
        return $this->entityContainer->sliceByClass(ServiceInterface::class);
    }

    /**
     * Get the type definitions attached to the model
     * @return ObjectArray|PrimitiveType[]|ComplexType[]
     */
    public function getTypeDefinitions(): ObjectArray
    {
        return $this->model->sliceByClass([PrimitiveType::class, ComplexType::class]);
    }

    /**
     * Get the document references attached to the model
     * @return Reference[]|References References
     */
    public function getReferences(): References
    {
        return $this->references;
    }

    /**
     * Add a reference to an external CSDL document
     * @param  Reference  $reference
     * @return $this
     * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_Reference
     */
    public function addReference(Reference $reference): self
    {
        $this->references[] = $reference;

        return $this;
    }

    /**
     * Add a new type definition
     * @param  PrimitiveType  $typeDefinition  Type definition
     * @return $this
     */
    public function addTypeDefinition(PrimitiveType $typeDefinition): self
    {
        $this->model[] = $typeDefinition;

        return $this;
    }

    /**
     * Get the entity container
     * @return EntityContainer
     */
    public function getEntityContainer(): EntityContainer
    {
        return $this->entityContainer;
    }

    /**
     * Discover the Eloquent model provided as a class name
     * @param  string  $model  Eloquent model class name
     * @return EloquentEntitySet Eloquent entity set
     */
    public function discoverEloquentModel(string $model): EloquentEntitySet
    {
        return (new EloquentEntitySet($model))->discover();
    }

    /**
     * Perform discovery on the provided class name or instance
     * @param  string|object  $discoverable
     * @return $this
     */
    public function discover($discoverable): self
    {
        (new Discovery)->discover($discoverable);

        return $this;
    }

    /**
     * Get the REST endpoint of this OData model
     * @return string REST endpoint
     */
    public function getEndpoint(): string
    {
        return ServiceProvider::endpoint();
    }

    /**
     * Get the PowerBI discovery URL of this service
     * @return string URL
     */
    public function getPbidsUrl(): string
    {
        return ServiceProvider::endpoint().'_lodata/odata.pbids';
    }

    /**
     * Get the Office Data Connection URL of the provided entity set
     * @param  string  $set  Entity set name
     * @return string URL
     */
    public function getOdcUrl(string $set): string
    {
        return sprintf('%s_lodata/%s.odc', ServiceProvider::endpoint(), $set);
    }

    /**
     * Get the OpenAPI specification document URL of this service
     * @return string
     */
    public function getOpenApiUrl(): string
    {
        return ServiceProvider::endpoint().'openapi.json';
    }
}
