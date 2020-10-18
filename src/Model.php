<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities;
use Flat3\Lodata\Annotation\Core;
use Flat3\Lodata\Annotation\Reference;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
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

    public function getResource($name): ?IdentifierInterface
    {
        return $this->getResources()->get($name);
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

    public function discover($class): IdentifierInterface
    {
        switch (true) {
            case is_a($class, \Illuminate\Database\Eloquent\Model::class, true):
                /** @var EloquentEntitySet $set */
                $set = $this->add(new EloquentEntitySet($class));
                $set->discoverProperties();
                $set->discoverRelationships();
                return $set;
        }

        throw new InternalServerErrorException('discovery_failed',
            'Could not understand the class passed for discovery');
    }
}
