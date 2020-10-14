<?php

namespace Flat3\Lodata\Drivers;

use Exception;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\Literal;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Model as LoModel;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Traits\Lodata;
use Flat3\Lodata\Type\Property;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class EloquentEntitySet extends SQLEntitySet
{
    /** @var Model $model */
    protected $model;

    public static function autoDiscover(string $model): void
    {
        $modelInstance = new $model();

        $type = new EntityType(self::getTypeName($model));
        $set = new static(self::getSetName($model), $type);
        $set->setModel($model);
        $set->setTable($modelInstance->getTable());
        $set->discoverProperties();

        LoModel::add($set);
    }

    public static function autoDiscoverAll(): void
    {
        foreach (self::getAllModels() as $model) {
            self::autoDiscover($model);
        }
    }

    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public static function getTypeName(string $model): string
    {
        return Str::studly(class_basename($model));
    }

    public static function getSetName(string $model)
    {
        return Str::pluralStudly(class_basename($model));
    }

    public function discoverProperties(): void
    {
        /** @var Model $model */
        $model = new $this->model();

        $type = $this->getType();
        $type->setKey(
            new DeclaredProperty(
                $model->getKeyName(),
                $this->eloquentTypeToPrimitive($model->getKeyType())
            )
        );

        /** @var Connection $conn */
        $conn = DB::connection();
        $grammar = $conn->selectFromWriteConnection($conn->getSchemaGrammar()->compileColumnListing($this->getTable()));
        $casts = $model->getCasts();

        foreach ($grammar as $gram) {
            if ($gram->name === $model->getKeyName()) {
                continue;
            }

            $cast = $gram->type;
            if (array_key_exists($gram->name, $casts)) {
                $cast = $casts[$gram->name];
            }

            $type->addProperty(
                new DeclaredProperty(
                    $gram->name,
                    $this->eloquentTypeToPrimitive($cast)->clone()->setNullable(!$gram->notnull)->seal()
                )
            );
        }
    }

    public function eloquentTypeToPrimitive(string $type): PrimitiveType
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                return PrimitiveType::boolean();

            case 'date':
                return PrimitiveType::date();

            case 'datetime':
                return PrimitiveType::datetimeoffset();

            case 'decimal':
            case 'float':
            case 'real':
                return PrimitiveType::decimal();

            case 'double':
                return PrimitiveType::double();

            case 'int':
            case 'integer':
                return PrimitiveType::int32();

            case 'varchar':
            case 'string':
                return PrimitiveType::string();

            case 'timestamp':
                return PrimitiveType::timeofday();
        }

        return PrimitiveType::string();
    }

    public function assocToEntity(array $row): Entity
    {
        $id = $row['id'];
        return $this->getEntityById($row['id']);
    }

    public function getModelByKey(PrimitiveType $key): ?Model
    {
        return $this->model::where($key->getProperty()->getName(), $key->get())->first();
    }

    public function getEntityById($id): ?Entity
    {
        $key = $this->getType()->getKey()->getType()->clone();
        $key->setProperty($this->getType()->getKey());
        $key->set($id);

        return $this->read($key);
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
        $model = $this->getModelByKey($key);

        $body = $this->transaction->getBody();

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            if (array_key_exists($property->getName(), $body)) {
                $model[$property->getName()] = $body[$property->getName()];
            }
        }

        $model->save();

        return $this->read($key);
    }

    public function create(): Entity
    {
        $model = new $this->model();

        $body = $this->transaction->getBody();

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            if (array_key_exists($property->getName(), $body)) {
                $model[$property->getName()] = $body[$property->getName()];
            }
        }

        $id = $model->save();

        return $this->getEntityById($id);
    }

    public function delete(PrimitiveType $key)
    {
        $model = $this->getModelByKey($key);

        try {
            $model->delete();
        } catch (Exception $e) {
            throw new InternalServerErrorException('deletion_error', $e->getMessage());
        }
    }

    public function propertyToField(Property $property): string
    {
        $model = new $this->model();
        return $model->qualifyColumn($property->getName());
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
                        $valid = in_array(
                                Lodata::class,
                                array_keys($reflection->getTraits())
                            ) && !$reflection->isAbstract();
                    }
                    return $valid;
                })
                ->values()
                ->toArray(), $models);
        }

        return $models;
    }
}
