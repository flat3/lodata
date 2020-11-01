<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities;
use Flat3\Lodata\Annotation\Core;
use Flat3\Lodata\Annotation\Reference;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;

class Model
{
    /** @var ObjectArray $model */
    protected $model;

    public function __construct()
    {
        $this->model = new ObjectArray();

        $this->model[] = new Core\V1\Reference();
        $this->model[] = new Core\V1\ConventionalIDs();
        $this->model[] = new Core\V1\DefaultNamespace();
        $this->model[] = new Core\V1\DereferencableIDs();
        $this->model[] = new Core\V1\ODataVersions();

        $this->model[] = new Capabilities\V1\Reference();
        $this->model[] = new Capabilities\V1\AsynchronousRequestsSupported();
        $this->model[] = new Capabilities\V1\CallbackSupported();
        $this->model[] = new Capabilities\V1\ConformanceLevel();
        $this->model[] = new Capabilities\V1\SupportedFormats();
    }

    public function add(IdentifierInterface $item): IdentifierInterface
    {
        $this->model->add($item);
        return $item;
    }

    public function getEntityType($name): ?EntityType
    {
        return $this->getEntityTypes()->get($name);
    }

    public function getSingleton($name): ?Singleton
    {
        $resource = $this->getResource($name);
        return $resource instanceof Singleton ? $resource : null;
    }

    public function getResource($name): ?IdentifierInterface
    {
        return $this->getResources()->get($name);
    }

    public function getEntitySet($name): ?EntitySet
    {
        $resource = $this->getResource($name);
        return $resource instanceof EntitySet ? $resource : null;
    }

    public function getOperation($name): ?Operation
    {
        $resource = $this->getResource($name);
        return $resource instanceof Operation ? $resource : null;
    }

    public function getFunction($name): ?FunctionInterface
    {
        $resource = $this->getResource($name);
        return $resource instanceof FunctionInterface ? $resource : null;
    }

    public function getAction($name): ?ActionInterface
    {
        $resource = $this->getResource($name);
        return $resource instanceof ActionInterface ? $resource : null;
    }

    public function getNamespace(): string
    {
        return config('lodata.namespace');
    }

    public function drop(string $key): self
    {
        $this->model->drop($key);
        return $this;
    }

    public function getEntityTypes(): ObjectArray
    {
        return $this->model->sliceByClass(EntityType::class);
    }

    public function getResources(): ObjectArray
    {
        return $this->model->sliceByClass(ResourceInterface::class);
    }

    public function getServices(): ObjectArray
    {
        return $this->model->sliceByClass(ServiceInterface::class);
    }

    public function getAnnotations(): ObjectArray
    {
        return $this->model->sliceByClass(Annotation::class);
    }

    public function getAnnotationReferences(): ObjectArray
    {
        return $this->model->sliceByClass(Reference::class);
    }

    public function discoverEloquentModel($class): EloquentEntitySet
    {
        return EloquentEntitySet::discover($class);
    }

    public function getEndpoint(): string
    {
        return ServiceProvider::endpoint();
    }

    public function getPbidsUrl(): string
    {
        return ServiceProvider::endpoint().'_lodata/odata.pbids';
    }

    public function getOdcUrl(string $set): string
    {
        return sprintf("%s_lodata/%s.odc", ServiceProvider::endpoint(), $set);
    }
}
