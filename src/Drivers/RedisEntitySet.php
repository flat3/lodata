<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Type;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Class RedisEntitySet
 * @package Flat3\Lodata\Drivers
 */
class RedisEntitySet extends EntitySet implements CreateInterface, UpdateInterface, DeleteInterface, ReadInterface, QueryInterface, PaginationInterface
{
    protected $connectionName = null;

    public function create(): Entity
    {
        $entity = $this->newEntity();
        $entity->fromArray($this->transaction->getBody());

        if (!$entity->getEntityId()) {
            throw new BadRequestException('missing_key', 'The required key must be provided to this entity set type');
        }

        $this->getConnection()->set($entity->getEntityId()->getPrimitiveValue()->get(), $this->serialize($entity));

        return $entity;
    }

    /**
     * Get the Redis connection configured for this entity set
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return Redis::connection($this->connectionName);
    }

    /**
     * Set the name of the Redis database to use
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName(string $name): self
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Count records in the database
     * @return int|null
     */
    public function count()
    {
        return $this->getConnection()->dbsize();
    }

    /**
     * Delete a record from the database
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key): void
    {
        $this->getConnection()->del($key->getPrimitiveValue()->get());
    }

    /**
     * Read a record from the database
     * @param  PropertyValue  $key  Key
     * @return Entity|null Entity
     */
    public function read(PropertyValue $key): ?Entity
    {
        $record = $this->getConnection()->get($key->getPrimitiveValue()->get());

        if (null === $record) {
            return null;
        }

        $entity = $this->unserialize($record);
        $entity->setEntityId($key);

        return $entity;
    }

    /**
     * Update a record in the database
     * @param  PropertyValue  $key  Key
     * @return Entity Entity
     */
    public function update(PropertyValue $key): Entity
    {
        $entity = $this->read($key);

        foreach ($this->transaction->getBody() as $property => $value) {
            $entity[$property] = $value;
        }

        $this->getConnection()->set($entity->getEntityId()->getPrimitiveValue()->get(), $this->serialize($entity));

        return $entity;
    }

    /**
     * Query the redis database
     * @return Entity[] Results
     */
    public function query(): array
    {
        $skipToken = $this->getSkipToken();

        if ($skipToken->isPaginationComplete()) {
            return [];
        }

        $config = [];

        $top = $this->getTop();

        if ($top->hasValue()) {
            $config['COUNT'] = $top->getValue();
        }

        list($redisPage, $results) = $this->getConnection()->scan($skipToken->getValue() ?: 0, $config);

        if ($redisPage == 0) {
            $skipToken->setPaginationComplete();
        } else {
            $skipToken->setValue($redisPage);
        }

        return array_map(function ($key) {
            $keyValue = new PropertyValue();
            $keyValue->setProperty($this->getType()->getKey());
            $keyValue->setValue(Type\String_::factory(Str::after($key, config('database.redis.options.prefix'))));

            return $this->read($keyValue);
        }, $results ?: []);
    }

    /**
     * Serialize an Entity into a string for insertion into Redis
     * @param  Entity  $entity  Entity
     * @return string Redis value
     */
    public function serialize(Entity $entity): string
    {
        $data = $entity->toArray();
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
        return $this->newEntity()->fromArray(unserialize($string));
    }
}