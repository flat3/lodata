<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Type;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

class MongoEntitySet extends EntitySet implements ReadInterface, CreateInterface, QueryInterface, DeleteInterface, UpdateInterface, CountInterface, OrderByInterface, PaginationInterface, FilterInterface
{
    /** @var ?Collection $collection */
    protected $collection;

    /**
     * Set the MongoDB Collection to use
     * @param  Collection  $collection
     * @return $this
     */
    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get the attached MongoDB Collection
     * @return Collection|null
     */
    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    /**
     * Create a document
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $entity = $this->newEntity();

        foreach ($propertyValues as $propertyValue) {
            $entity[$propertyValue->getProperty()->getName()] = $propertyValue->getValue();
        }

        $result = $this->collection->insertOne($this->entityToBson($entity));
        $entity->setEntityId($result->getInsertedId());

        return $entity;
    }

    /**
     * Read a document
     * @param  PropertyValue  $key
     * @return Entity
     */
    public function read(PropertyValue $key): Entity
    {
        /** @var BSONDocument $document */
        $document = $this->collection->findOne([$key->getProperty()->getName() => $key->getPrimitiveValue()]);

        if (null === $document) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        return $this->bsonToEntity($document)->generateComputedProperties();
    }

    /**
     * Delete a document
     * @param  PropertyValue  $key
     * @return void
     */
    public function delete(PropertyValue $key): void
    {
        $this->collection->deleteOne([$key->getProperty()->getName() => $key->getPrimitiveValue()]);
    }

    /**
     * Update a document
     * @param  PropertyValue  $key  Key
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $entity = $this->read($key);

        foreach ($propertyValues as $propertyValue) {
            $entity->addPropertyValue($propertyValue);
        }

        $this->collection->updateOne(
            [$key->getProperty()->getName() => $key->getPrimitiveValue()],
            ['$set' => $this->entityToBson($entity)]
        );

        return $entity;
    }

    /**
     * Count documents
     * @return int
     */
    public function count(): int
    {
        return $this->collection->countDocuments($this->getFilterExpression());
    }

    /**
     * Query collection
     * @return Generator
     */
    public function query(): Generator
    {
        $options = [];

        if ($this->getTop()->hasValue()) {
            $options['limit'] = $this->getTop()->getValue();
        }

        if ($this->getSkip()->hasValue()) {
            $options['skip'] = $this->getSkip()->getValue();
        }

        if ($this->getOrderBy()->hasValue()) {
            $sort = [];

            foreach ($this->getOrderBy()->getSortOrders() as $order) {
                $sort[$order[0]] = $order[1] === 'asc' ? 1 : -1;
            }

            $options['sort'] = $sort;
        }

        $filter = $this->getFilterExpression();

        /** @var BSONDocument $document */
        foreach ($this->collection->find($filter, $options) as $document) {
            yield $this->bsonToEntity($document)->generateComputedProperties();
        }
    }

    /**
     * Convert a BSON document to an Entity
     * @param  BSONDocument  $document
     * @return Entity
     */
    public function bsonToEntity(BSONDocument $document): Entity
    {
        return $this->toEntity($this->bsonToArray($document));
    }

    public function bsonToArray(BSONDocument $document): array
    {
        $record = [];

        foreach ($document as $key => $value) {
            switch (true) {
                case $value instanceof BSONDocument:
                    $value = $this->bsonToArray($value);
                    break;

                case $value instanceof UTCDateTime:
                    $value = Carbon::create($value->toDateTime());
                    break;
            }

            $record[$key] = $value;
        }

        return $record;
    }

    /**
     * Convert an Entity to a BSON document
     * @param  Entity  $entity
     * @return BSONDocument
     */
    public function entityToBson(Entity $entity): BSONDocument
    {
        $document = new BSONDocument();

        /** @var PropertyValue $propertyValue */
        foreach ($entity->getPropertyValues() as $propertyValue) {
            $property = $propertyValue->getProperty();
            $value = $propertyValue->getValue();

            switch (true) {
                case $value instanceof Type\Date:
                    $value = $value->get()->format('Y-m-d');
                    break;

                case $value instanceof Type\TimeOfDay:
                    $value = $value->get()->format('H:i:s');
                    break;

                case $value instanceof Type\DateTimeOffset:
                    $value = new UTCDateTime($value->get());
                    break;

                default:
                    $value = $value->toMixed();
                    break;
            }

            $document[$this->getPropertySourceName($property)] = $value;
        }

        return $document;
    }

    /**
     * Recursively evaluate a filter expression
     * @param  Node|null  $node
     * @return array|string|void
     */
    protected function evaluateFilter(?Node $node)
    {
        if (null === $node) {
            return null;
        }

        $left = $node->getLeftNode();
        $right = $node->getRightNode();

        $lValue = $this->evaluateFilter($left);
        $rValue = $this->evaluateFilter($right);

        $args = array_map(function (Node $arg) {
            return $this->evaluateFilter($arg);
        }, $node->getArguments());

        $arg0 = $args[0] ?? null;

        switch (true) {
            case $node instanceof Node\Operator\Logical\In:
                return ['$in' => [$lValue, $args]];

            case $node instanceof Node\Operator\Arithmetic:
            case $node instanceof Node\Operator\Logical:
            case $node instanceof Node\Operator\Comparison:
                $operator = [
                    Node\Operator\Logical\Equal::class => '$eq',
                    Node\Operator\Logical\NotEqual::class => '$ne',
                    Node\Operator\Logical\GreaterThan::class => '$gt',
                    Node\Operator\Logical\LessThan::class => '$lt',
                    Node\Operator\Logical\LessThanOrEqual::class => '$lte',
                    Node\Operator\Logical\GreaterThanOrEqual::class => '$gte',
                    Node\Operator\Comparison\And_::class => '$and',
                    Node\Operator\Comparison\Or_::class => '$or',
                    Node\Operator\Comparison\Not_::class => '$not',
                    Node\Operator\Arithmetic\Add::class => '$add',
                    Node\Operator\Arithmetic\DivBy::class => '$divide',
                    Node\Operator\Arithmetic\Mod::class => '$mod',
                    Node\Operator\Arithmetic\Mul::class => '$multiply',
                    Node\Operator\Arithmetic\Sub::class => '$subtract',
                ][get_class($node)] ?? null;

                if (null === $operator) {
                    $node->notImplemented();
                }

                return $node::isUnary() ? [$operator => $lValue] : [$operator => [$lValue, $rValue]];

            case $node instanceof Node\Func\DateTime\Now:
                return '$$NOW';

            case $node instanceof Node\Func\DateTime\Date:
            case $node instanceof Node\Func\DateTime\Day:
            case $node instanceof Node\Func\DateTime\FractionalSeconds:
            case $node instanceof Node\Func\DateTime\Hour:
            case $node instanceof Node\Func\DateTime\Minute:
            case $node instanceof Node\Func\DateTime\Month:
            case $node instanceof Node\Func\DateTime\Second:
            case $node instanceof Node\Func\DateTime\Time:
            case $node instanceof Node\Func\DateTime\Year:
                $dateFormat = [
                    Node\Func\DateTime\Date::class => '%Y-%m-%d',
                    Node\Func\DateTime\Day::class => '%d',
                    Node\Func\DateTime\FractionalSeconds::class => '%L',
                    Node\Func\DateTime\Hour::class => '%H',
                    Node\Func\DateTime\Minute::class => '%M',
                    Node\Func\DateTime\Month::class => '%m',
                    Node\Func\DateTime\Second::class => '%S',
                    Node\Func\DateTime\Time::class => '%H:%M:%S.%L',
                    Node\Func\DateTime\Year::class => '%Y',
                ][get_class($node)];

                $convertToInt = !in_array(get_class($node), [
                    Node\Func\DateTime\Date::class,
                    Node\Func\DateTime\Time::class,
                ]);

                $function = [
                    '$dateToString' => [
                        'date' => $arg0,
                        'format' => $dateFormat,
                    ]
                ];

                if ($convertToInt) {
                    $function = [
                        '$convert' => [
                            'input' => $function,
                            'to' => 'int'
                        ]
                    ];
                }

                return $function;

            case $node instanceof Node\Func\Type\Cast:
                $to = [
                    Type\Double::identifier => 'double',
                    Type\String_::identifier => 'string',
                    Type\Boolean::identifier => 'boolean',
                    Type\Date::identifier => 'date',
                    Type\Int32::identifier => 'int',
                    Type\Int64::identifier => 'long',
                    Type\Decimal::identifier => 'decimal',
                ][$args[1]] ?? null;

                if (null === $to) {
                    $node->notImplemented();
                }

                return [
                    '$convert' => [
                        'input' => $arg0,
                        'to' => $to,
                    ]
                ];

            case $node instanceof Node\Func\Arithmetic\Round:
                return ['$round' => [$arg0, 0]];

            case $node instanceof Node\Func\String\Trim:
                return ['$trim' => ['input' => $arg0]];

            case $node instanceof Node\Func\StringCollection\Substring:
                return [
                    '$substrCP' => [
                        $arg0, $args[1], $args[2] ?? ((2 ** 31) - 1),
                    ]
                ];

            case $node instanceof Node\Func\String\MatchesPattern:
            case $node instanceof Node\Func\StringCollection\StartsWith:
            case $node instanceof Node\Func\StringCollection\EndsWith:
            case $node instanceof Node\Func\StringCollection\Contains:
                $regex = [
                    Node\Func\String\MatchesPattern::class => $args[1],
                    Node\Func\StringCollection\StartsWith::class => '^'.$args[1],
                    Node\Func\StringCollection\EndsWith::class => $args[1].'$',
                    Node\Func\StringCollection\Contains::class => $args[1],
                ][get_class($node)];

                return [
                    '$regexMatch' => [
                        'input' => $arg0,
                        'regex' => $regex,
                    ],
                ];

            case $node instanceof Node\Func:
                $function = [
                    Node\Func\Arithmetic\Ceiling::class => '$ceil',
                    Node\Func\Arithmetic\Floor::class => '$floor',
                    Node\Func\String\ToLower::class => '$toLower',
                    Node\Func\String\ToUpper::class => '$toUpper',
                    Node\Func\StringCollection\Concat::class => '$concat',
                    Node\Func\StringCollection\Length::class => '$strLenCP',
                    Node\Func\StringCollection\IndexOf::class => '$indexOfCP',
                ][get_class($node)] ?? null;

                if (null === $function) {
                    $node->notImplemented();
                }

                return [$function => $node::arguments === 1 ? $arg0 : $args];

            case $node->getValue() === null:
                return null;

            case $node instanceof Node\Literal\Date:
                return $node->getValue()->get()->format('Y-m-d');

            case $node instanceof Node\Literal\DateTimeOffset:
                return new UTCDateTime($node->getValue()->get());

            case $node instanceof Node\Literal\TimeOfDay:
                return $node->getValue()->get()->format('H:i:s.v');

            case $node instanceof Node\Literal:
                return $node->getValue()->get();

            case $node instanceof Node\Property:
                return '$'.$node->getValue()->getName();
        }

        $node->notImplemented();
    }

    protected function getFilterExpression(): array
    {
        if (!$this->getFilter()->hasValue()) {
            return [];
        }

        $parser = $this->getFilterParser();
        $parser->pushEntitySet($this);
        $tree = $parser->generateTree($this->getFilter()->getExpression());

        return ['$expr' => $this->evaluateFilter($tree)];
    }

    public static function discover(Collection $collection): self
    {
        $type = new MongoEntityType(Str::singular($collection->getCollectionName()));
        $set = new self(Str::plural($collection->getCollectionName()), $type);
        $set->setCollection($collection);

        Lodata::add($set);

        return $set;
    }
}