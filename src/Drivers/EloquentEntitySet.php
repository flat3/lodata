<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Flat3\Lodata\Annotation\Capabilities\V1\DeepInsertSupport;
use Flat3\Lodata\DeclaredProperty;
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
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\TransactionInterface;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Type;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

/**
 * Eloquent Entity Set
 * @package Flat3\Lodata\Drivers
 */
class EloquentEntitySet extends EntitySet implements CountInterface, CreateInterface, DeleteInterface, ExpandInterface, FilterInterface, OrderByInterface, PaginationInterface, QueryInterface, ReadInterface, SearchInterface, TransactionInterface, UpdateInterface
{
    use SQLConnection;
    use SQLSearch;
    use SQLFilter;
    use SQLSchema {
        columnToDeclaredProperty as protected schemaColumnToDeclaredProperty;
    }
    use SQLWhere;

    /**
     * Eloquent model class name
     * @var Model|Builder $model
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

        $name = $this->getSetName($model);
        $type = new EntityType(EloquentEntitySet::getTypeName($model));

        parent::__construct($name, $type);
        $this->addAnnotation(new DeepInsertSupport());
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
    public function getSetName(string $model): string
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
        $model = $this->getBuilder();

        return $model->where($key->getProperty()->getName(), $key->getPrimitiveValue())->first();
    }

    /**
     * Get the database table name used by the model
     * @return string Table name
     */
    public function getTable(): string
    {
        $model = $this->getModel();

        return $model->getTable();
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

        return $this->modelToEntity($model);
    }

    /**
     * Update an Eloquent model
     * @param  PropertyValue  $key  Model key
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $model = $this->getModelByKey($key);

        foreach ($propertyValues->getDeclaredPropertyValues() as $propertyValue) {
            $model[$propertyValue->getProperty()->getName()] = $propertyValue->getPrimitiveValue();
        }

        $model->save();

        return $this->read($key);
    }

    /**
     * Create an Eloquent model
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $model = $this->getModel();

        /** @var DeclaredProperty $declaredProperty */
        foreach ($propertyValues->getDeclaredPropertyValues() as $propertyValue) {
            $model[$propertyValue->getProperty()->getName()] = $propertyValue->getPrimitiveValue();
        }

        if ($this->navigationPropertyValue) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationPropertyValue->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $model[$referencedProperty->getName()] = $this->navigationPropertyValue->getParent()->getEntityId()->getPrimitiveValue();
            }
        }

        if (!$model->save()) {
            throw new InternalServerErrorException('commit_failure', 'Could not commit the data');
        }

        $key = new PropertyValue();
        $key->setProperty($this->getType()->getKey());
        $key->setValue($key->getProperty()->getType()->instance($model->id));
        $entity = $this->read($key);
        $key->setParent($entity);

        return $entity;
    }

    /**
     * Delete an Eloquent model
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key): void
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
     */
    public function query(): Generator
    {
        $builder = $this->getBuilder();

        if ($this->navigationPropertyValue) {
            /** @var Entity $sourceEntity */
            $sourceEntity = $this->navigationPropertyValue->getParent();
            $expansionPropertyName = $this->navigationPropertyValue->getProperty()->getName();
            $builder = $sourceEntity->getSource()->$expansionPropertyName();

            if ($builder instanceof HasManyThrough) {
                $builder->select($builder->getRelated()->getTable().'.*');
            }
        }

        $this->resetParameters();
        $this->generateWhere();

        if ($this->where) {
            $builder->whereRaw($this->where, $this->parameters);
        }

        $orderby = $this->getOrderBy();
        if ($orderby->hasValue()) {
            foreach ($orderby->getSortOrders() as $so) {
                [$literal, $direction] = $so;
                $builder->orderBy($literal, $direction);
            }
        }

        if ($this->getTop()->hasValue()) {
            $builder->limit($this->getTop()->getValue());
        }

        if ($this->getSkip()->hasValue()) {
            if (!$this->getTop()->hasValue()) {
                $builder->limit(PHP_INT_MAX);
            }

            $builder->skip($this->getSkip()->getValue());
        }

        foreach ($builder->lazyById() as $model) {
            yield $this->modelToEntity($model);
        }
    }

    /**
     * Convert an Eloquent model instance to an OData Entity
     * @param  Model  $model  Eloquent model
     * @return Entity Entity
     */
    public function modelToEntity(Model $model): Entity
    {
        $entity = $this->newEntity();
        $entity->setSource($model);

        /** @var Property $property */
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            $propertyValue = $entity->newPropertyValue();
            $propertyValue->setProperty($property);
            $propertyValue->setValue($property->getType()->instance($model->{$property->getName()}));
            $entity->addPropertyValue($propertyValue);
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
        $model = $this->getModel();

        return $model->qualifyColumn($property->getName());
    }

    /**
     * Discover an Eloquent relationship method and add it to the model
     * @param  string  $method  Relationship method name
     * @return $this
     */
    public function discoverRelationship(string $method): self
    {
        $model = $this->getModel();

        try {
            new ReflectionMethod($model, $method);

            /** @var Relation $r */
            $r = $model->$method();
            $esn = $this->getSetName(get_class($r->getRelated()));
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

    public function startTransaction()
    {
        DB::beginTransaction();
    }

    public function rollback()
    {
        DB::rollBack();
    }

    public function commit()
    {
        DB::commit();
    }

    /**
     * Count the number of records matching the query
     * @return int Count
     */
    public function count(): int
    {
        $builder = $this->getBuilder();

        $this->resetParameters();
        $this->generateWhere();

        if ($this->where) {
            $builder->whereRaw($this->where, $this->parameters);
        }

        return $builder->count();
    }

    /**
     * Provide a query builder
     * Enables subclasses to apply filters and scopes to the builder
     *
     * @return Builder Query builder
     */
    public function getBuilder(): Builder
    {
        $instance = $this->getModel();

        return $instance->newQuery();
    }

    /**
     * Provide an instance of the model
     * Enables subclasses to modify the model used by the set
     *
     * @return Model Model
     */
    public function getModel(): Model
    {
        return App::make($this->model);
    }

    /**
     * Convert an SQL column that may have an Eloquent cast to an OData declared property
     * @link https://laravel.com/docs/8.x/eloquent-mutators#attribute-casting
     * @param  Column  $column  SQL column
     * @return DeclaredProperty OData declared property
     */
    public function columnToDeclaredProperty(Column $column): DeclaredProperty
    {
        $model = $this->getModel();
        $casts = $model->getCasts();

        if (!array_key_exists($column->getName(), $casts)) {
            return $this->schemaColumnToDeclaredProperty($column);
        }

        switch ($casts[$column->getName()]) {
            case 'string':
            default:
                $type = Type::string();
                break;

            case 'boolean':
                $type = Type::boolean();
                break;

            case 'date':
                $type = Type::date();
                break;

            case 'datetime':
                $type = Type::datetimeoffset();
                break;

            case 'decimal':
            case 'float':
            case 'real':
                $type = Type::decimal();
                break;

            case 'double':
                $type = Type::double();
                break;

            case 'int':
            case 'integer':
                if (PHP_INT_SIZE === 8) {
                    $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt64::identifier) ? Type::uint64() : Type::int64();
                } else {
                    $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt32::identifier) ? Type::uint32() : Type::int32();
                }
                break;

            case 'timestamp':
                $type = Type::timeofday();
                break;
        }

        return new DeclaredProperty($column->getName(), $type);
    }
}
