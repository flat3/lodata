<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Traits\EloquentOData;
use Flat3\Lodata\Type\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class EloquentEntitySet extends SQLEntitySet
{
    /** @var Model $model */
    protected $model;

    public function __construct(string $model)
    {
        $this->model = $model;

        /** @var Model $model */
        $model = new $this->model();
        $type = new EntityType(Str::singular($model->getTable()));
        $type->setKey(
            new DeclaredProperty(
                $model->getKeyName(),
                $this->eloquentTypeToPrimitive($model->getKeyType())
            )
        );
        $type->addProperty((new DeclaredProperty(
            'code',
            PrimitiveType::string()
        ))->setKeyable());

        parent::__construct($model->getTable(), $type);
    }

    public function eloquentTypeToPrimitive(string $type): PrimitiveType
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                return PrimitiveType::boolean();

            case 'date':
            case 'datetime':
                return PrimitiveType::timeofday();

            case 'decimal':
            case 'float':
            case 'real':
                return PrimitiveType::decimal();

            case 'double':
                return PrimitiveType::double();

            case 'int':
            case 'integer':
                return PrimitiveType::int32();

            case 'string':
                return PrimitiveType::string();

            case 'timestamp':
                return PrimitiveType::datetimeoffset();
        }

        return PrimitiveType::string();
    }

    public function query(): array
    {
        return parent::query();
    }

    public function getModelByKey(PrimitiveType $key): ?Model
    {
        return $this->model::where($key->getProperty()->getName(), $key->get())->first();
    }

    public function entityToModel(Entity $entity): Model
    {
        /** @var Model $model */
        $model = new $this->model();

        /** @var PrimitiveType $primitive */
        foreach ($entity->getPrimitives() as $primitive) {
            $model[$primitive->getName()] = $primitive->get();
        }

        return $model;
    }

    public function read(PrimitiveType $key): ?Entity
    {
        $model = $this->getModelByKey($key);

        if (null === $model) {
            return null;
        }

        $entity = $this->newEntity();

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            $entity->setPrimitive($property, $model->{$property->getName()});
        }

        $entity->setEntityId($model->getKey());

        return $entity;
    }

    public function update(PrimitiveType $key): Entity
    {
        $entity = $this->read($key);
        $entity->fromArray($this->transaction->getBody());

        $this->entityToModel($entity)->save();

        return $this->read($key);
    }

    public function create(): Entity
    {
        $entity = $this->newEntity();
        $entity->fromArray($this->transaction->getBody());

        $model = $this->entityToModel($entity);
        $model->save();

        $entity->setEntityId($model->getKey());

        return $this->read($entity->getEntityId());
    }

    public function delete(PrimitiveType $key)
    {
        $model = $this->getModelByKey($key);
        $model->delete();
    }

    public static function attach()
    {
        /** @var EloquentEntitySet $model */
        $model = resolve(EloquentEntitySet::class);

        $eloquentModels = self::getAllModels();
    }

    public function test()
    {
        $this->getTable();
        $this->getConnection();
        $this->getKeyName();
        $this->getKeyType();
        $this->getPerPage();
        $this->qualifyColumn($column);
        // Insert - new Model and ->save()
        $this->update($array);
        $this->delete();
        $this->save();
        self::find([1, 2, 3]);
    }

    public static function getAllModels(): array
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $models = [];

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            $models = array_merge(collect(File::allFiles(base_path($path)))
                ->map(function ($item) use ($namespace) {
                    $path = $item->getRelativePathName();
                    return sprintf(
                        '\%s%s',
                        $namespace,
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );
                })
                ->filter(function ($class) {
                    $valid = false;
                    if (class_exists($class)) {
                        $reflection = new ReflectionClass($class);
                        $valid = in_array(EloquentOData::class, array_keys($reflection->getTraits())) &&
                            !$reflection->isAbstract();
                    }
                    return $valid;
                })
                ->values()
                ->toArray(), $models);
        }

        return $models;
    }
}
