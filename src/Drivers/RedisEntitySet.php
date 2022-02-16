<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\TokenPaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Type;
use Generator;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Class RedisEntitySet
 * @package Flat3\Lodata\Drivers
 */
class RedisEntitySet extends EntitySet implements CreateInterface, UpdateInterface, DeleteInterface, ReadInterface, QueryInterface, TokenPaginationInterface, CountInterface, ComputeInterface
{
    /** @var ?Connection $connection */
    protected $connection = null;

    /** @var int $pageSize */
    protected $pageSize = 100;

    public function __construct(string $identifier, EntityType $entityType)
    {
        parent::__construct($identifier, $entityType);

        $this->connection = Redis::connection();
    }

    /**
     * Create a new record
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $entity = $this->newEntity();

        foreach ($propertyValues as $propertyValue) {
            $entity[$propertyValue->getProperty()->getName()] = $propertyValue->getValue();
        }

        $this->getConnection()->set($entity->getEntityId()->getPrimitiveValue(), $this->serialize($entity));

        return $entity;
    }

    /**
     * Get the Redis connection configured for this entity set
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set the name of the Redis database to use
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName(string $name): self
    {
        $this->connection = Redis::connection($name);

        return $this;
    }

    /**
     * Set the Redis connection to use
     * @param  Connection  $connection
     * @return $this
     */
    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Count records in the database
     * @return int|null
     */
    public function count(): int
    {
        return $this->getConnection()->dbsize();
    }

    /**
     * Delete a record from the database
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key): void
    {
        $this->getConnection()->del($key->getPrimitiveValue());
    }

    /**
     * Read a record from the database
     * @param  PropertyValue  $key  Key
     * @return Entity|null Entity
     */
    public function read(PropertyValue $key): Entity
    {
        $record = $this->getConnection()->get($key->getPrimitiveValue());

        if (null === $record) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        $entity = $this->unserialize($record);
        $entity->setEntityId($key);
        $entity->generateComputedProperties();

        return $entity;
    }

    /**
     * Update a record in the database
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

        $this->getConnection()->set($entity->getEntityId()->getPrimitiveValue(), $this->serialize($entity));

        return $entity;
    }

    /**
     * Query the redis database
     */
    public function query(): Generator
    {
        $token = $this->getSkipToken()->hasValue() ? $this->getSkipToken()->getValue() : null;

        $pageSize = $this->pageSize;
        if ($this->getTop()->hasValue()) {
            $pageSize = min($pageSize, $this->getTop()->getValue());
        }

        do {
            list($token, $keys) = $this->getConnection()->scan($token, ['COUNT' => $pageSize]);

            if ($keys === null) {
                break;
            }

            foreach ($keys as $key) {
                $keyValue = new PropertyValue();
                $keyValue->setProperty($this->getType()->getKey());
                $keyValue->setValue(new Type\String_(Str::after($key, config('database.redis.options.prefix'))));

                yield $this->read($keyValue);
            }

            $this->getSkipToken()->setValue((string) $token);
        } while ($token > 0);

        $this->getSkipToken()->clearValue();
    }

    /**
     * Serialize an Entity into a string for insertion into Redis
     * @param  Entity  $entity  Entity
     * @return string Redis value
     */
    public function serialize(Entity $entity): string
    {
        $data = $this->toArray($entity);
        unset($data[$entity->getEntityId()->getProperty()->getName()]);
        return serialize($data);
    }

    /**
     * Deserialize a string Redis value into an Entity
     * @param  string  $string
     * @return Entity Entity
     */
    public function unserialize(string $string): Entity
    {
        $redisValue = unserialize($string);

        if (!is_array($redisValue)) {
            throw new InternalServerErrorException(
                'invalid_redis_value',
                'The value retrieved from Redis could not be converted to an entity'
            );
        }

        return $this->toEntity($redisValue);
    }
}