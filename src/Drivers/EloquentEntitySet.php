<?php

namespace Flat3\Lodata\Drivers;

use Exception;
use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQL\SQLFilter;
use Flat3\Lodata\Drivers\SQL\SQLSchema;
use Flat3\Lodata\Drivers\SQL\SQLSearch;
use Flat3\Lodata\Drivers\SQL\SQLWhere;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

/**
 * Eloquent Entity Set
 * @package Flat3\Lodata\Drivers
 */
class EloquentEntitySet extends EntitySet implements ReadInterface, UpdateInterface, CreateInterface, DeleteInterface, QueryInterface, FilterInterface, SearchInterface, ExpandInterface, OrderByInterface
{
    use SQLConnection;
    use SQLSearch;
    use SQLFilter;
    use SQLSchema;
    use SQLWhere;

    /**
     * Eloquent model class name
     * @var Model|Builder $model
     * @internal
     */
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

    /**
     * Set the Eloquent model class name
     * @param  string  $model  Eloquent model class name
     * @return $this
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the OData entity type name for this Eloquent model
     * @param  string  $model  Eloquent model class name
     * @return string OData identifier
     */
    public static function getTypeName(string $model): string
    {
        return Str::studly(class_basename($model));
    }

    /**
     * Get the OData entity set name for this Eloquent model
     * @param  string  $model  Eloquent model class name
     * @return string OData identifier
     */
    public static function getSetName(string $model)
    {
        return Str::pluralStudly(class_basename($model));
    }

    /**
     * Return an instance of the Eloquent model using the provided key
     * @param  PropertyValue  $key  Key
     * @return Model|null Eloquent model
     */
    public function getModelByKey(PropertyValue $key): ?Model
    {
        /** @var Model|Builder $model */
        $model = new $this->model();

        return $model->where($key->getProperty()->getName(), $key->getPrimitiveValue()->get())->first();
    }

    /**
     * Get the database table name used by the model
     * @return string Table name
     */
    public function getTable(): string
    {
        /**
         * @var Model $model
         * @phpstan-ignore-next-line
         */
        $model = new $this->model();
        return $model->getTable();
    }

    /**
     * Get the SQL type casts defined on this Eloquent model
     * @return array Casts
     */
    public function getCasts(): array
    {
        /**
         * @var Model $model
         * @phpstan-ignore-next-line
         */
        $model = new $this->model();
        return $model->getCasts();
    }

    /**
     * Read an Eloquent model
     * @param  PropertyValue  $key  Model key
     * @return Entity|null Entity
     */
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

    /**
     * Update an Eloquent model
     * @param  PropertyValue  $key  Model key
     * @return Entity Entity
     */
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

    /**
     * Create an Eloquent model
     * @return Entity Entity
     */
    public function create(): Entity
    {
        /**
         * @var Model $model
         * @phpstan-ignore-next-line
         */
        $model = new $this->model();

        $body = $this->transaction->getBody();

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            if (array_key_exists($property->getName(), $body)) {
                $model[$property->getName()] = $body[$property->getName()];
            }
        }

        if ($this->expansionPropertyValue) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->expansionPropertyValue->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $model[$referencedProperty->getName()] = $this->expansionPropertyValue->getEntity()->getEntityId()->getPrimitiveValue()->get();
            }
        }

        if (!$model->save()) {
            throw new InternalServerErrorException('commit_failure', 'Could not commit the data');
        }

        $key = new PropertyValue();
        $key->setProperty($this->getType()->getKey());
        $key->setValue($key->getProperty()->getType()->instance($model->id));
        $entity = $this->read($key);
        $key->setEntity($entity);

        return $entity;
    }

    /**
     * Delete an Eloquent model
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key)
    {
        $model = $this->getModelByKey($key);

        try {
            $model->delete();
        } catch (Exception $e) {
            throw new InternalServerErrorException('deletion_error', $e->getMessage());
        }
    }

    /**
     * Query eloquent models
     * @return array Entity buffer
     */
    public function query(): array
    {
        /**
         * @var Model $instance
         * @phpstan-ignore-next-line
         */
        $instance = new $this->model();
        $builder = $instance->newQuery();

        if ($this->expansionPropertyValue) {
            $sourceEntity = $this->expansionPropertyValue->getEntity();
            $expansionPropertyName = $this->expansionPropertyValue->getProperty()->getName();
            $instance = $sourceEntity->getEntitySet()->getModelByKey($sourceEntity->getEntityId());
            $builder = $instance->$expansionPropertyName();

            if ($builder instanceof HasManyThrough) {
                $builder->select($builder->getRelated()->getTable().'.*');
            }
        }

        $this->resetParameters();
        $this->generateWhere();

        if ($this->where) {
            $builder->whereRaw($this->where, $this->parameters);
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

    /**
     * Convert an Eloquent model instance to an OData Entity
     * @param  Model  $model  Eloquent model
     * @return Entity Entity
     */
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

    /**
     * Convert an entity type property to a database field
     * @param  Property  $property  Property
     * @return string Field
     */
    public function propertyToField(Property $property): string
    {
        /**
         * @var Model $model
         * @phpstan-ignore-next-line
         */
        $model = new $this->model();
        return $model->qualifyColumn($property->getName());
    }

    /**
     * Discover an Eloquent relationship method and add it to the model
     * @param  string  $method  Relationship method name
     * @return $this
     */
    public function discoverRelationship(string $method): self
    {
        /**
         * @var Model $model
         * @phpstan-ignore-next-line
         */
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
                $localProperty = $this->getType()->getProperty($r->getLocalKeyName());
                $foreignProperty = $right->getType()->getProperty($r->getForeignKeyName());

                if (!$localProperty || !$foreignProperty) {
                    throw new InternalServerErrorException(
                        'missing_properties',
                        'The properties referenced for the relationship could not be found on the models'
                    );
                }

                $rc = new ReferentialConstraint($localProperty, $foreignProperty);
                $nav->addConstraint($rc);
            }

            if ($r instanceof BelongsTo || $r instanceof HasOneThrough || $r instanceof HasOne) {
                $nav->setCollection(false);
            }

            $binding = new NavigationBinding($nav, $right);

            $this->getType()->addProperty($nav);
            $this->addNavigationBinding($binding);
        } catch (ReflectionException $e) {
            throw new InternalServerErrorException(
                'cannot_add_constraint',
                'The constraint method did not exist on the model: '.get_class($model)
            );
        }

        return $this;
    }

    /**
     * Create an entity set from the provided Eloquent model class and add it to the model
     * @param  string  $class  Eloquent model class
     * @return static
     */
    public static function discover(string $class): self
    {
        $set = new self($class);
        Lodata::add($set);
        $set->discoverProperties();

        return $set;
    }
}
