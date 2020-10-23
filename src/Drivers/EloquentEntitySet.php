<?php

namespace Flat3\Lodata\Drivers;

use Exception;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class EloquentEntitySet extends SQLEntitySet
{
    /** @var Model $model */
    protected $model;

    public function __construct(string $model)
    {
        $this->model = $model;

        $modelInstance = new $model();

        $name = EloquentEntitySet::getSetName($model);
        $type = new EntityType(EloquentEntitySet::getTypeName($model));

        parent::__construct($name, $type);

        $this->setTable($modelInstance->getTable());
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

    public static function discoverRelationships()
    {
        $sets = Lodata::getResources()->sliceByClass(EloquentEntitySet::class);

        /** @var self $left */
        foreach ($sets as $left) {
            /** @var self $right */
            foreach ($sets as $right) {
                if ($left === $right) {
                    continue;
                }

                $model = new $left->model;
                $name = Str::lower($right->getName());

                try {
                    new ReflectionMethod($model, $name);
                    /** @var HasOneOrMany $r */
                    $r = $model->$name();

                    $rc = new ReferentialConstraint(
                        $left->getType()->getProperty($r->getLocalKeyName()),
                        $right->getType()->getProperty($r->getForeignKeyName())
                    );

                    $nav = (new NavigationProperty($right, $right->getType()))
                        ->setCollection(true)
                        ->addConstraint($rc);

                    $binding = new NavigationBinding($nav, $right);

                    $left->getType()->addProperty($nav);
                    $left->addNavigationBinding($binding);
                } catch (ReflectionException $e) {
                }
            }
        }
    }

    public function discoverProperties(): self
    {
        /** @var Model $model */
        $model = new $this->model();

        $type = $this->getType();
        $type->setKey(
            new DeclaredProperty(
                $model->getKeyName(),
                $this->eloquentTypeToTypeDefinition($model->getKeyType())
            )
        );

        $schema = Schema::connection(config('database.default'));
        $manager = $schema->getConnection()->getDoctrineSchemaManager();
        $details = $manager->listTableDetails($this->getTable());
        $columns = $details->getColumns();
        $casts = $model->getCasts();

        foreach ($columns as $column) {
            $name = $column->getName();
            if ($name === $model->getKeyName()) {
                continue;
            }

            $cast = $column->getType()->getName();
            $notnull = $column->getNotnull();

            if (array_key_exists($name, $casts)) {
                $cast = $casts[$name];
            }

            $type->addProperty(
                new DeclaredProperty(
                    $name,
                    $this->eloquentTypeToTypeDefinition($cast)->setNullable(!$notnull)
                )
            );
        }

        return $this;
    }

    public function eloquentTypeToTypeDefinition(string $type): PrimitiveType
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
        return $this->getEntityById($row[$this->getType()->getKey()->getName()]);
    }

    public function getModelByKey(Primitive $key): ?Model
    {
        return $this->model::where($key->getProperty()->getName(), $key->get())->first();
    }

    public function getEntityById($id): ?Entity
    {
        $key = $this->getType()->getKey()->getType()->instance();
        $key->setProperty($this->getType()->getKey());
        $key->set($id);

        return $this->read($key);
    }

    public function read(Primitive $key): ?Entity
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

    public function update(Primitive $key): Entity
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
        /** @var Model $model */
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

    public function delete(Primitive $key)
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

    public static function discover($class): self
    {
        /** @var EloquentEntitySet $set */
        $set = new EloquentEntitySet($class);
        Lodata::add($set);
        $set->discoverProperties();
        self::discoverRelationships();

        return $set;
    }
}
