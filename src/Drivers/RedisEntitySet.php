<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Class RedisEntitySet
 * @package Flat3\Lodata\Drivers
 */
class RedisEntitySet extends EntitySet implements CreateInterface, UpdateInterface, DeleteInterface, ReadInterface, QueryInterface, PaginationInterface
{
    public function create(): Entity
    {
        $entity = $this->newEntity();
        $entity->fromArray($this->transaction->getBody());

        if (!$entity->getEntityId()) {
            throw new BadRequestException('missing_key', 'The required key must be provided to this entity set type');
        }

        // @phpstan-ignore-next-line
        Redis::set($entity->getEntityId()->getPrimitiveValue()->get(), $this->serialize($entity));

        return $entity;
    }

    public function count()
    {
        // @phpstan-ignore-next-line
        return Redis::dbsize();
    }

    public function delete(PropertyValue $key): void
    {
        // @phpstan-ignore-next-line
        Redis::del($key->getPrimitiveValue()->get());
    }

    public function read(PropertyValue $key): ?Entity
    {
        // @phpstan-ignore-next-line
        $record = Redis::get($key->getPrimitiveValue()->get());

        if (null === $record) {
            throw new NotFoundException();
        }

        return $this->unserialize($key, $record);
    }

    public function update(PropertyValue $key): Entity
    {
        $entity = $this->read($key);

        foreach ($this->transaction->getBody() as $property => $value) {
            $entity[$property] = $value;
        }

        // @phpstan-ignore-next-line
        Redis::set($entity->getEntityId()->getPrimitiveValue()->get(), $this->serialize($entity));

        return $entity;
    }

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

        // @phpstan-ignore-next-line
        list($redisPage, $results) = Redis::scan($skipToken->getValue() ?: 0, $config);

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

    public function serialize(Entity $entity): string
    {
        $data = $entity->toArray();
        unset($data[$entity->getEntityId()->getProperty()->getName()]);
        return serialize($data);
    }

    public function unserialize(PropertyValue $key, string $string): Entity
    {
        $entity = $this->newEntity()->fromArray(unserialize($string));
        $entity->setEntityId($key);
        return $entity;
    }
}