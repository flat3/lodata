<?php

namespace Flat3\Lodata\Drivers;

use Exception;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQL\SQLFilter;
use Flat3\Lodata\Drivers\SQL\SQLSchema;
use Flat3\Lodata\Drivers\SQL\SQLSearch;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class EloquentEntitySet extends EntitySet implements ReadInterface, UpdateInterface, CreateInterface, DeleteInterface, QueryInterface, FilterInterface, SearchInterface, ExpandInterface, OrderByInterface
{
    use SQLConnection;
    use SQLSearch;
    use SQLFilter;
    use SQLSchema;

    /** @var Model $model */
    protected $model;

    public function __construct(string $model)
    {
        if (!is_a($model, Model::class, true)) {
            throw new InternalServerErrorException(
                'not_eloquent_model',
                'An eloquent model class name must be provided'
            );
        }

        $this->model = $model;

        $name = EloquentEntitySet::getSetName($model);
        $type = new EntityType(EloquentEntitySet::getTypeName($model));

        parent::__construct($name, $type);
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

    public function getModelByKey(PropertyValue $key): ?Model
    {
        return $this->model::where($key->getProperty()->getName(), $key->getPrimitiveValue()->get())->first();
    }

    public function getTable(): string
    {
        /** @var Model $model */
        $model = new $this->model();
        return $model->getTable();
    }

    public function getCasts(): array
    {
        /** @var Model $model */
        $model = new $this->model();
        return $model->getCasts();
    }

    public function read(PropertyValue $key): ?Entity
    {
        $model = $this->getModelByKey($key);

        if (null === $model) {
            return null;
        }

        $entity = $this->newEntity();

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            $propertyValue = $entity->newPropertyValue();
            $propertyValue->setProperty($property);
            $propertyValue->setValue($property->getType()->instance($model->{$property->getName()}));
            $entity->addProperty($propertyValue);
        }

        $entity->setEntityId($model->getKey());

        return $entity;
    }

    public function update(PropertyValue $key): Entity
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

        $key = new PropertyValue();
        $key->setProperty($this->getType()->getKey());
        $key->setValue($key->getProperty()->getType()->instance($id));
        $entity = $this->read($key);
        $key->setEntity($entity);

        return $entity;
    }

    public function delete(PropertyValue $key)
    {
        $model = $this->getModelByKey($key);

        try {
            $model->delete();
        } catch (Exception $e) {
            throw new InternalServerErrorException('deletion_error', $e->getMessage());
        }
    }

    public function query(): array
    {
        /** @var Model $instance */
        $instance = new $this->model();
        $builder = $instance->newQuery();

        if ($this->expansionPropertyValue) {
            $sourceEntity = $this->expansionPropertyValue->getEntity();
            $expansionPropertyName = $this->expansionPropertyValue->getProperty()->getName();
            $instance = $sourceEntity->getEntitySet()->getModelByKey($sourceEntity->getEntityId());
            $builder = $instance->$expansionPropertyName();
        }

        $this->resetParameters();

        $select = $this->transaction->getSelect();
        if ($select->hasValue()) {
            $properties = $select->getSelectedProperties($this)->sliceByClass(DeclaredProperty::class);
            /** @var DeclaredProperty $property */
            foreach ($properties as $property) {
                $builder->addSelect($property->getName());
            }
        }

        $this->generateWhere();

        if ($this->where) {
            $builder->whereRaw($this->where, ...$this->parameters);
        }

        $orderby = $this->transaction->getOrderBy();
        if ($orderby->hasValue()) {
            foreach ($orderby->getSortOrders() as $so) {
                [$literal, $direction] = $so;
                $builder->orderBy($literal, $direction);
            }
        }

        if ($this->top !== PHP_INT_MAX) {
            $builder->limit($this->top);

            if ($this->skip) {
                $builder->skip($this->skip);
            }
        }

        $results = [];

        foreach ($builder->getModels() as $model) {
            $results[] = $this->modelToEntity($model);
        }

        return $results;
    }

    public function modelToEntity(Model $model): Entity
    {
        $set = Lodata::getEntitySet(self::getSetName(get_class($model)));
        $entity = $set->newEntity();

        /** @var Property $property */
        foreach ($set->getType()->getDeclaredProperties() as $property) {
            $propertyValue = $entity->newPropertyValue();
            $propertyValue->setProperty($property);
            $propertyValue->setValue($property->getType()->instance($model->{$property->getName()}));
            $entity->addProperty($propertyValue);
        }

        $entity->setEntityId($model->getKey());

        return $entity;
    }

    public function propertyToField(Property $property): string
    {
        $model = new $this->model();
        return $model->qualifyColumn($property->getName());
    }

    public function discoverRelationship(string $method): self
    {
        $model = new $this->model;

        try {
            new ReflectionMethod($model, $method);

            /** @var Relation $r */
            $r = $model->$method();
            $esn = self::getSetName(get_class($r->getRelated()));
            $right = Lodata::getEntitySet($esn);
            if (!$right) {
                throw new InternalServerErrorException('no_related_set', 'Could not find the related entity set '.$esn);
            }

            $nav = (new NavigationProperty($method, $right->getType()))
                ->setCollection(true);

            if ($r instanceof HasOne || $r instanceof HasOneOrMany) {
                $rc = new ReferentialConstraint(
                    $this->getType()->getProperty($r->getLocalKeyName()),
                    $right->getType()->getProperty($r->getForeignKeyName())
                );

                $nav->addConstraint($rc);
            }

            $binding = new NavigationBinding($nav, $right);

            $this->getType()->addProperty($nav);
            $this->addNavigationBinding($binding);
        } catch (ReflectionException $e) {
            throw new InternalServerErrorException(
                'cannot_add_constraint',
                'The constraint method did not exist on the Model'
            );
        }

        return $this;
    }

    public static function discover($class): self
    {
        $set = new self($class);
        Lodata::add($set);
        $set->discoverProperties();

        return $set;
    }
}
