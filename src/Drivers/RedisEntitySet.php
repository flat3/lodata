<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Controller\Request;
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
use Flat3\Lodata\Interfaces\TransactionInterface;
use Flat3\Lodata\Type;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RedisEntitySet extends EntitySet implements CreateInterface, UpdateInterface, DeleteInterface, ReadInterface, QueryInterface, TransactionInterface, PaginationInterface
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

    public function delete(PropertyValue $key)
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
        $entity->fromArray($this->transaction->getBody());

        // @phpstan-ignore-next-line
        Redis::set($key->getPrimitiveValue()->get(), $this->serialize($entity));

        return $this->read($key);
    }

    public $finished = false;

    public function query(): array
    {
        $config = [];

        if ($this->getTop()->hasValue()) {
            $config['COUNT'] = $this->getTop()->getValue();
        }

        $skip = $this->getSkip()->hasValue() ? $this->getSkip()->getValue() : 0;

        // @phpstan-ignore-next-line
        $scan = Redis::scan((string) $skip, $config);

        if ($this->finished) {
            return [];
        }

        if ($scan[0] == 0) {
            $this->finished = true;
        }

        return array_map(function ($key) {
            $keyValue = new PropertyValue();
            $keyValue->setProperty($this->getType()->getKey());
            $keyValue->setValue(Type\String_::factory(Str::after($key, config('database.redis.options.prefix'))));

            return $this->read($keyValue);
        }, $scan[1] ?: []);
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

    public function startTransaction()
    {
        if ($this->getTransaction()->getMethod() === Request::METHOD_GET) {
            return;
        }

        // @phpstan-ignore-next-line
        Redis::multi();
    }

    public function rollback()
    {
        // @phpstan-ignore-next-line
        Redis::discard();
    }

    public function commit()
    {
        // @phpstan-ignore-next-line
        Redis::exec();
    }
}