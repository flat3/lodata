<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Flat3\Lodata\Annotation\Capabilities\V1\DeepInsertSupport;
use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\Attributes\LodataIdentifier;
use Flat3\Lodata\Attributes\LodataProperty;
use Flat3\Lodata\Attributes\LodataRelationship;
use Flat3\Lodata\Attributes\LodataTypeIdentifier;
use Flat3\Lodata\ComputedProperty;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQL\SQLExpression;
use Flat3\Lodata\Drivers\SQL\SQLOrderBy;
use Flat3\Lodata\Drivers\SQL\SQLSchema;
use Flat3\Lodata\Drivers\SQL\SQLWhere;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
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
use Flat3\Lodata\Operation;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Type;
use Generator;
use Illuminate\Database\ConnectionInterface;
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
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Eloquent Entity Set
 * @package Flat3\Lodata\Drivers
 */
class EloquentEntitySet extends EntitySet implements CountInterface, CreateInterface, DeleteInterface, ExpandInterface, FilterInterface, OrderByInterface, PaginationInterface, QueryInterface, ReadInterface, SearchInterface, TransactionInterface, UpdateInterface, ComputeInterface
{
    use SQLConnection;
    use SQLOrderBy;
    use SQLSchema {
        columnToDeclaredProperty as protected schemaColumnToDeclaredProperty;
    }
    use SQLWhere;

    /**
     * Eloquent model class name
     * @var Model|Builder $model
     */
    protected $model;

    public function __construct(string $model, ?EntityType $entityType = null)
    {
        if (!is_a($model, Model::class, true)) {
            throw new ConfigurationException(
                'not_eloquent_model',
                'An eloquent model class name must be provided'
            );
        }

        $this->model = $model;

        $name = self::convertClassName($model);
        if (!$entityType) {
            $entityType = new EntityType(EntityType::convertClassName($model));
        }

        parent::__construct($name, $entityType);
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
     * Return an instance of the Eloquent model using the provided key
     * @param  PropertyValue  $key  Key
     * @return Model|null Eloquent model
     */
    public function getModelByKey(PropertyValue $key): ?Model
    {
        $builder = $this->getBuilder();
        $builder->select('*');
        $this->selectComputedProperties($builder);

        return $builder->where(
            $this->getPropertySourceName($key->getProperty()),
            $key->getPrimitive()->toMixed()
        )->first();
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
     * Get the database connection used by the model
     * @return ConnectionInterface Connection
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->getModel()->getConnection();
    }

    /**
     * Read an Eloquent model
     * @param  PropertyValue  $key  Model key
     * @return Entity|null Entity
     */
    public function read(PropertyValue $key): Entity
    {
        $model = $this->getModelByKey($key);

        if (null === $model) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        return $this->modelToEntity($model);
    }

    /**
     * Apply the provided property values to the provided model
     * @param  Model  $model  Model instance
     * @param  PropertyValues  $propertyValues  Property values
     * @return Model
     */
    protected function setModelAttributes(Model $model, PropertyValues $propertyValues): Model
    {
        foreach ($propertyValues->getDeclaredPropertyValues() as $propertyValue) {
            $model->setAttribute(
                $this->getPropertySourceName($propertyValue->getProperty()),
                $propertyValue->getPrimitive()->toMixed()
            );
        }

        return $model;
    }

    /**
     * Update an Eloquent model
     * @param  PropertyValue  $key  Model key
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $entity = $this->read($key);
        $model = $this->setModelAttributes($entity->getSource(), $propertyValues);
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
        $model = $this->setModelAttributes($this->getModel(), $propertyValues);

        if ($this->navigationSource) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationSource->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $model->setAttribute(
                    $referencedProperty->getName(),
                    $this->navigationSource->getParent()->getEntityId()->getPrimitive()->toMixed()
                );
            }
        }

        if (!$model->save()) {
            throw new InternalServerErrorException('commit_failure', 'Could not commit the data');
        }

        $key = new PropertyValue();
        $key->setProperty($this->getType()->getKey());
        $key->setValue($key->getProperty()->getType()->instance($model->getKey()));
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
            throw new InternalServerErrorException('deletion_error', $e->getMessage(), $e);
        }
    }

    /**
     * Query eloquent models
     */
    public function query(): Generator
    {
        $builder = $this->getBuilder();
        $builder->select('*');

        if ($this->navigationSource) {
            /** @var Entity $sourceEntity */
            $sourceEntity = $this->navigationSource->getParent();
            $expansionPropertyName = $this->navigationSource->getProperty()->getName();
            $builder = $sourceEntity->getSource()->$expansionPropertyName();

            if ($builder instanceof HasManyThrough) {
                $builder->select($builder->getRelated()->getTable().'.*');
            }
        }

        $this->selectComputedProperties($builder);

        $where = $this->generateWhere();

        if ($where->hasStatement()) {
            $builder->whereRaw($where->getStatement(), $where->getParameters());
        }

        $orderby = $this->generateOrderBy();

        if ($orderby->hasStatement()) {
            $builder->orderByRaw($orderby->getStatement(), $orderby->getParameters());
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

        foreach ($builder->cursor() as $model) {
            yield $this->modelToEntity($model);
        }
    }

    /**
     * Add computed properties to the select statement of the provided query builder
     * @param  Builder|Relation  $builder
     * @return void
     */
    protected function selectComputedProperties($builder): void
    {
        $compute = $this->getCompute();

        if (!$compute->hasValue()) {
            return;
        }

        $computedProperties = $compute->getProperties();

        foreach ($computedProperties as $computedProperty) {
            $expression = $this->getSQLExpression();
            $computeParser = $this->getComputeParser();
            $computeParser->pushEntitySet($this);
            $tree = $computeParser->generateTree($computedProperty->getExpression());
            $expression->evaluate($tree);
            $builder->selectRaw(
                $expression->getStatement().' as '.$this->quoteSingleIdentifier($computedProperty->getName()),
                $expression->getParameters()
            );
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
        $entity->setEntityId($model->getKey());

        foreach ($this->getType()->getDeclaredProperties() as $declaredProperty) {
            $propertyValue = $entity->newPropertyValue();
            $propertyValue->setProperty($declaredProperty);
            $propertyValue->setValue($declaredProperty->getType()->instance($model->getAttribute($this->getPropertySourceName($declaredProperty))));
            $entity->addPropertyValue($propertyValue);
        }

        foreach ($this->getCompute()->getProperties() as $computedProperty) {
            $value = $model->getAttribute($this->getPropertySourceName($computedProperty));

            if (is_string($value) && is_numeric($value)) {
                $value = JSON::decode($value);
            }

            $entity[$computedProperty->getName()] = $value;
        }

        return $entity;
    }

    /**
     * Convert an entity type property to a database field
     * @param  Property  $property  Property
     * @return SQLExpression Expression
     */
    public function propertyToExpression(Property $property): SQLExpression
    {
        $model = $this->getModel();

        $expression = $this->getSQLExpression();

        switch (true) {
            case $property instanceof DeclaredProperty:
                $expression->pushStatement($model->qualifyColumn($this->getPropertySourceName($property)));
                break;

            case $property instanceof ComputedProperty:
                $computedExpression = $this->getSQLExpression();
                $computeParser = $this->getComputeParser();
                $computeParser->pushEntitySet($this);
                $tree = $computeParser->generateTree($property->getExpression());
                $computedExpression->evaluate($tree);
                $expression->pushExpression($computedExpression);
                break;
        }

        return $expression;
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

            $relatedModel = get_class($r->getRelated());

            $right = Lodata::getResources()->sliceByClass(self::class)->find(function ($set) use ($relatedModel) {
                return $set->getModel() instanceof $relatedModel;
            });

            if (!$right) {
                $right = (new self($relatedModel))->discover();
            }

            $nav = (new NavigationProperty($method, $right->getType()))
                ->setCollection(true);

            if ($r instanceof HasOneOrMany || $r instanceof BelongsTo) {
                $localProperty = null;
                $foreignProperty = null;

                switch (true) {
                    case $r instanceof HasOneOrMany:
                        $localProperty = $this->getType()->getProperty($r->getLocalKeyName());
                        $foreignProperty = $right->getType()->getProperty($r->getForeignKeyName());
                        break;

                    case $r instanceof BelongsTo:
                        $localProperty = $this->getType()->getProperty($r->getForeignKeyName());
                        $foreignProperty = $right->getType()->getProperty($r->getOwnerKeyName());
                        break;
                }

                if (!$localProperty || !$foreignProperty) {
                    throw new ConfigurationException(
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
            throw new ConfigurationException(
                'cannot_add_constraint',
                'The constraint method did not exist on the model: '.get_class($model),
                $e
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

        if ($this->navigationSource) {
            /** @var Entity $sourceEntity */
            $sourceEntity = $this->navigationSource->getParent();
            $expansionPropertyName = $this->navigationSource->getProperty()->getName();
            $builder = $sourceEntity->getSource()->$expansionPropertyName();
        }

        $where = $this->generateWhere();

        if ($where->hasStatement()) {
            $builder->whereRaw($where->getStatement(), $where->getParameters());
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
    public function columnToDeclaredProperty(Column $column): ?DeclaredProperty
    {
        $model = $this->getModel();

        $hidden = $model->getHidden();
        $visible = $model->getVisible();

        if (in_array($column->getName(), $hidden) || ($visible && !in_array($column->getName(), $visible))) {
            return null;
        }

        $property = $this->schemaColumnToDeclaredProperty($column);

        $defaultValue = $model->getAttributeValue($column->getName());
        if ($defaultValue) {
            $property->addAnnotation(new ComputedDefaultValue);
            $property->setDefaultValue($defaultValue);
        }

        $casts = $model->getCasts();

        if (array_key_exists($column->getName(), $casts)) {
            $cast = $casts[$column->getName()];
            switch (true) {
                default:
                case 'string' === $cast:
                    $type = Type::string();
                    break;

                case 'boolean' === $cast:
                    $type = Type::boolean();
                    break;

                case 'array' === $cast:
                    $type = Type::collection(Type::string());
                    break;

                case EnumerationType::isEnum($cast):
                    $type = EnumerationType::discover($cast);
                    break;

                case in_array($cast, ['date', 'datetime:Y-m-d']) || Str::startsWith($cast, 'date:'):
                    $type = Type::date();
                    break;

                case in_array($cast, ['decimal', 'float', 'real']):
                    $type = Type::decimal();
                    break;

                case 'double' === $cast:
                    $type = Type::double();
                    break;

                case in_array($cast, ['int', 'integer']):
                    if (PHP_INT_SIZE === 8) {
                        $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt64::identifier) ? Type::uint64() : Type::int64();
                    } else {
                        $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt32::identifier) ? Type::uint32() : Type::int32();
                    }
                    break;

                case in_array($cast, ['datetime:H:i:s', 'timestamp']):
                    $type = Type::timeofday();
                    break;

                case 'datetime' === $cast || Str::startsWith($cast, 'datetime:'):
                    $type = Type::datetimeoffset();
                    break;
            }

            $property->setType($type);
        }

        return $property;
    }

    /**
     * Discover elements on this entity set model
     * @return $this
     * @throws ReflectionException
     */
    public function discover(): self
    {
        $entityType = $this->getType();

        $propertyAttributes = [];

        if (Discovery::supportsAttributes()) {
            $propertyAttributes = (new ReflectionClass($this->model))->getAttributes(
                LodataProperty::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
        }

        if ($propertyAttributes) {
            foreach ($propertyAttributes as $propertyAttribute) {
                /** @var LodataProperty $instance */
                $instance = $propertyAttribute->newInstance();

                $instance->addProperty($this);
            }
        } else {
            $this->discoverProperties();
        }

        if (!$entityType->getKey()) {
            throw new ConfigurationException(
                'missing_model_key',
                sprintf('The model %s had no primary key', $this->model)
            );
        }

        Lodata::add($this);
        Operation::discover($this->model);

        if (!Discovery::supportsAttributes()) {
            return $this;
        }

        $typeAttribute = Discovery::getFirstAttributeInstance($this->model, LodataTypeIdentifier::class);
        if ($typeAttribute) {
            Lodata::drop($this->getType());
            $this->getType()->setIdentifier($typeAttribute->getIdentifier());
            Lodata::add($this->getType());
        }

        $identifierAttribute = Discovery::getFirstAttributeInstance($this->model, LodataIdentifier::class);
        if ($identifierAttribute) {
            Lodata::drop($this);
            $this->setIdentifier($identifierAttribute->getIdentifier());
            Lodata::add($this);
        }

        foreach (Discovery::getReflectedMethods($this->model) as $reflectionMethod) {
            if (!$reflectionMethod->getAttributes(LodataRelationship::class, ReflectionAttribute::IS_INSTANCEOF)) {
                continue;
            }

            $relationshipMethod = $reflectionMethod->getName();

            try {
                $this->discoverRelationship($relationshipMethod);
            } catch (ConfigurationException $e) {
            }
        }

        return $this;
    }
}
