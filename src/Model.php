<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities\V1\AsynchronousRequestsSupported;
use Flat3\Lodata\Annotation\Capabilities\V1\CallbackSupported;
use Flat3\Lodata\Annotation\Capabilities\V1\ConformanceLevel;
use Flat3\Lodata\Annotation\Capabilities\V1\SupportedFormats;
use Flat3\Lodata\Annotation\Core\V1\ConventionalIDs;
use Flat3\Lodata\Annotation\Core\V1\DefaultNamespace;
use Flat3\Lodata\Annotation\Core\V1\DereferencableIDs;
use Flat3\Lodata\Annotation\Core\V1\ODataVersions;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\Traits;
use Flat3\Lodata\Interfaces\NamedInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\Lodata;
use Illuminate\Contracts\Container\BindingResolutionException;

class Model
{
    /** @var ObjectArray $resources */
    protected $model;

    public function __construct()
    {
        $this->model = new ObjectArray();

        $this->model[] = new ODataVersions();
        $this->model[] = new ConformanceLevel();
        $this->model[] = new ConventionalIDs();
        $this->model[] = new DereferencableIDs();
        $this->model[] = new DefaultNamespace();
        $this->model[] = new SupportedFormats();
        $this->model[] = new AsynchronousRequestsSupported();
        $this->model[] = new CallbackSupported();
    }

    public static function get(): self
    {
        try {
            return app()->make(self::class);
        } catch (BindingResolutionException $e) {
            throw new InternalServerErrorException('binding_resolution_error', $e->getMessage());
        }
    }

    public static function add(NamedInterface $item): self
    {
        $model = self::get();
        $model->model->add($item);
        return $model;
    }

    public static function fn($name): FunctionOperation
    {
        $model = self::get();

        $fn = new FunctionOperation($name);

        $model->model[] = $fn;
        return $fn;
    }

    public static function action($name): ActionOperation
    {
        $model = self::get();

        $action = new ActionOperation($name);

        $model->model[] = $action;
        return $action;
    }

    public static function entitytype($name): EntityType
    {
        $model = self::get();

        $type = new EntityType($name);

        $model->model[] = $type;
        return $type;
    }

    public static function getType($name): ?EntityType
    {
        $model = self::get();

        return $model->getEntityTypes()->get($name);
    }

    public static function getResource($name): ?NamedInterface
    {
        $model = self::get();

        return $model->getResources()->get($name);
    }

    public function getNamespace(): string
    {
        return config('odata.namespace') ?: 'com.example.odata';
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

    public static function discovery(): void
    {
        foreach (Traits::getClassesByTrait(Lodata::class) as $model) {
            self::add(new EloquentEntitySet($model));
        }
    }
}
