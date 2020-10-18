<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities;
use Flat3\Lodata\Annotation\Core;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\Traits;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

class Model
{
    /** @var ObjectArray $resources */
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

    public static function get(): self
    {
        try {
            return app()->make(self::class);
        } catch (BindingResolutionException $e) {
            throw new InternalServerErrorException('binding_resolution_error', $e->getMessage());
        }
    }

    public static function add(IdentifierInterface $item): IdentifierInterface
    {
        $model = self::get();
        $model->model->add($item);
        return $item;
    }

    public static function getType($name): ?EntityType
    {
        $model = self::get();

        return $model->getEntityTypes()->get($name);
    }

    public static function getResource($name): ?IdentifierInterface
    {
        $model = self::get();

        return $model->getResources()->get($name);
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

    public function getModel(): ObjectArray
    {
        return $this->model;
    }

    public static function discovery(): void
    {
        foreach (Traits::getClassesOfType(\Illuminate\Database\Eloquent\Model::class) as $model) {
            self::add(new EloquentEntitySet($model));
        }
    }
}
