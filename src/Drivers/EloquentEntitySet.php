<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Flat3\Lodata\Annotation\Capabilities\V1\DeepInsertSupport;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\Description;
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
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
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
use Flat3\Lodata\Interfaces\EntitySet\RelationshipInterface;
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

/**
 * Eloquent Entity Set
 * @package Flat3\Lodata\Drivers
 */
class EloquentEntitySet extends EntitySet implements CountInterface, CreateInterface, DeleteInterface, ExpandInterface, FilterInterface, OrderByInterface, PaginationInterface, QueryInterface, ReadInterface, SearchInterface, TransactionInterface, UpdateInterface, ComputeInterface, RelationshipInterface
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

    /**
     * Chunk size used for internal pagination
     * @var int $chunkSize
     */
    public static $chunkSize = 1000;

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
        return $this->getModel()->getTable();
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
     * @return Entity Entity
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
        return $propertyValues->getDeclaredPropertyValues()->reduce(function (Model $model, PropertyValue $value) {
            return $model->setAttribute(
                $this->getPropertySourceName($value->getProperty()),
                $value->getPrimitive()->toMixed(),
            );
        }, $model);
    }

    public function unlink(Entity $source, Property $property, Entity $target): Entity
    {
        if (get_class($source) !== $this->entityClass) {
            throw new InternalServerErrorException(
                'unexpected_link_source',
                'The link source for this entity valid.'
            );
        }

        /** @var $sourceModel Model */
        $sourceModel = $source->getSource();
        /** @var $targetModel Model */
        $targetModel = $target->getSource();
        if (!($sourceModel instanceof Model && $targetModel instanceof Model)) {
            throw new InternalServerErrorException(
                'can_only_link_eloquent',
                'The link source or target is not supported for this entity set.'
            );
        }

        $navigationRef = $this->getPropertySourceName($property);

        $rel = $sourceModel->{$navigationRef}();
        if ($rel instanceof BelongsToMany) {
            $rel->detach($targetModel);
        } elseif ($rel instanceof BelongsTo) {
            $rel->dissociate();
        } elseif ($rel instanceof HasMany) {
            $targetModel->{$rel->getForeignKeyName()} = null;
            $targetModel->save();
        } else {
            throw new NotImplementedException();
        }

        return $source;
    }

    public function link(Entity $source, Property $property, Entity $target): Entity
    {
        if (get_class($source) !== $this->entityClass) {
            throw new InternalServerErrorException(
                'unexpected_link_source',
                'The link source for this entity valid.'
            );
        }

        /** @var $sourceModel Model */
        $sourceModel = $source->getSource();
        /** @var $targetModel Model */
        $targetModel = $target->getSource();
        if (!($sourceModel instanceof Model && $targetModel instanceof Model)) {
            throw new InternalServerErrorException(
                'can_only_link_eloquent',
                'The link source or target is not supported for this entity set.'
            );
        }

        $navigationRef = $this->getPropertySourceName($property);

        $rel = $sourceModel->{$navigationRef}();
        if ($rel instanceof BelongsToMany) {
            $rel->syncWithoutDetaching($targetModel);
        } elseif ($rel instanceof BelongsTo) {
            $rel->associate($targetModel);
        } elseif ($rel instanceof HasMany) {
            $targetModel->{$rel->getForeignKeyName()} = $sourceModel->{$rel->getLocalKeyName()};
            $targetModel->save();
        } else {
            throw new NotImplementedException();
        }

        return $source;
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
                    $this->getPropertySourceName($referencedProperty),
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

        if (null === $model) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

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

        $properties = $this->getSelectedProperties();

        $key = $this->getType()->getKey();

        if ($key && !$properties[$key]) {
            $properties[] = $key;
        }

        $builder->select($properties->map(function (Property $property) {
            return $this->getPropertySourceName($property);
        }));

        if ($this->navigationSource) {
            /** @var Entity $sourceEntity */
            $sourceEntity = $this->navigationSource->getParent();
            $expansionPropertyName = $sourceEntity->getEntitySet()->getPropertySourceName($this->navigationSource->getProperty());

            /** @var Model $source */
            $source = $sourceEntity->getSource();
            $expansionBuilder = $source->$expansionPropertyName();

            if ($source->relationLoaded($expansionPropertyName)) {
                foreach (Collection::wrap($source->getRelation($expansionPropertyName)) as $model) {
                    yield $this->modelToEntity($model);
                }

                return;
            }

            $builder = $expansionBuilder;

            if ($builder instanceof HasManyThrough) {
                $builder->select($builder->getRelated()->getTable().'.*');
            }
        }

        $this->configureBuilder($builder);

        $builder->with($this->getRelationships());

        $page = 1;
        $skipValue = $this->getSkip()->getValue();

        $chunkSize = self::$chunkSize;
        if ($this->getTop()->hasValue() && $this->getTop()->getValue() > 0) {
            $chunkSize = $this->getTop()->getValue();
        }

        while (true) {
            $offset = (($page++ - 1) * $chunkSize) + $skipValue;
            $results = $builder->offset($offset)->limit($chunkSize)->get();

            foreach ($results as $result) {
                yield $this->modelToEntity($result);
            }

            if ($results->count() < $chunkSize) {
                break;
            }
        }
    }

    /**
     * Implement $compute, $top, $skip, $orderby, $filter, $search to Builder
     * @param $builder
     * @return void
     */
    protected function configureBuilder($builder)
    {
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
    }

    /**
     * Get relations by navigation requests
     * @return Closure[]
     */
    protected function getRelationships(): array
    {
        $relations = [];

        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            $navigationRequest = $this->getTransaction()->getNavigationRequests()->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            $expansionPropertyName = $this->getPropertySourceName($navigationProperty);
            $expansionTransaction = clone $this->getTransaction();
            $expansionTransaction->setRequest($navigationRequest);
            $expansionSet = clone $this->getBindingByNavigationProperty($navigationProperty)->getTarget();
            $expansionSet->setTransaction($expansionTransaction);

            $relations[$expansionPropertyName] = function ($builder) use ($expansionSet) {
                $expansionSet->configureBuilder($builder);
            };

            $relations = array_merge(
                $relations,
                Collection::make($expansionSet->getRelationships())
                    ->mapWithKeys(function ($item, $key) use ($expansionPropertyName) {
                        return ["{$expansionPropertyName}.".$key => $item];
                    })
                    ->all()
            );
        }

        return $relations;
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

        foreach ($this->getSelectedProperties() as $declaredProperty) {
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

            $entity->addPropertyValue($computedProperty->toPropertyValue($value));
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
     * @param  string|null  $name  Property name
     * @param  string|null  $description  Property description
     * @param  bool|null  $nullable  Whether the property can be made null
     * @return $this
     */
    public function discoverRelationship(
        string $method,
        ?string $name = null,
        ?string $description = null,
        ?bool $nullable = true
    ): self {
        $model = $this->getModel();

        try {
            new ReflectionMethod($model, $method);

            /** @var Relation $relation */
            $relation = $model->$method();

            if (!$relation instanceof Relation) {
                throw new ConfigurationException(
                    'invalid_relationship',
                    'The method could not return a valid relationship'
                );
            }

            $relatedModel = get_class($relation->getRelated());

            $right = Lodata::getResources()->sliceByClass(self::class)->find(function ($set) use ($relatedModel) {
                return $set->getModel() instanceof $relatedModel;
            });

            if (!$right) {
                $right = (new self($relatedModel))->discover();
            }

            $navigationProperty = (new NavigationProperty($name ?? $method, $right->getType()))->setCollection(true);
            $this->setPropertySourceName($navigationProperty, $method);

            if ($description) {
                $navigationProperty->addAnnotation(new Description($description));
            }

            $navigationProperty->setNullable($nullable);

            if ($relation instanceof HasOneOrMany || $relation instanceof BelongsTo) {
                $localProperty = null;
                $foreignProperty = null;

                switch (true) {
                    case $relation instanceof HasOneOrMany:
                        $localProperty = $this->getPropertyBySourceName($relation->getLocalKeyName());
                        $foreignProperty = $right->getPropertyBySourceName($relation->getForeignKeyName());
                        break;

                    case $relation instanceof BelongsTo:
                        $localProperty = $this->getPropertyBySourceName($relation->getForeignKeyName());
                        $foreignProperty = $right->getPropertyBySourceName($relation->getOwnerKeyName());
                        break;
                }

                if ($localProperty && $foreignProperty) {
                    $referentialConstraint = new ReferentialConstraint($localProperty, $foreignProperty);
                    $navigationProperty->addConstraint($referentialConstraint);
                }
            }

            if ($relation instanceof BelongsTo || $relation instanceof HasOneThrough || $relation instanceof HasOne) {
                $navigationProperty->setCollection(false);
            }

            if ($relation instanceof HasManyJson || $relation instanceof BelongsToJson) {
                $navigationProperty->setCollection(true);
            }

            $binding = new NavigationBinding($navigationProperty, $right);

            $this->getType()->addProperty($navigationProperty);

            if ($name && $name !== $method) {
                $this->setPropertySourceName($navigationProperty, $method);
            }

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
            $expansionPropertyName = $sourceEntity->getEntitySet()->getPropertySourceName($this->navigationSource->getProperty());
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
            $property->addAnnotation(new Computed);
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

        Lodata::add($this);
        Operation::discover($this->model);

        if (!Discovery::supportsAttributes()) {
            return $this;
        }

        $typeAttribute = Discovery::getFirstClassAttributeInstance($this->model, LodataTypeIdentifier::class);
        if ($typeAttribute) {
            Lodata::drop($this->getType());
            $this->getType()->setIdentifier($typeAttribute->getIdentifier());
            Lodata::add($this->getType());
        }

        $identifierAttribute = Discovery::getFirstClassAttributeInstance($this->model, LodataIdentifier::class);
        if ($identifierAttribute) {
            Lodata::drop($this);
            $this->setIdentifier($identifierAttribute->getIdentifier());
            Lodata::add($this);
        }

        /** @var ReflectionMethod $reflectionMethod */
        foreach (Discovery::getReflectedMethods($this->model) as $reflectionMethod) {
            /** @var LodataRelationship $relationshipInstance */
            $relationshipInstance = Discovery::getFirstMethodAttributeInstance(
                $reflectionMethod,
                LodataRelationship::class
            );

            if (!$relationshipInstance) {
                continue;
            }

            $relationshipMethod = $reflectionMethod->getName();

            try {
                $this->discoverRelationship(
                    $relationshipMethod,
                    $relationshipInstance->getName(),
                    $relationshipInstance->getDescription(),
                    $relationshipInstance->isNullable()
                );
            } catch (ConfigurationException $e) {
            }
        }

        return $this;
    }
}
