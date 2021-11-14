<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Operation;
use Illuminate\Database\Eloquent\Model;

class EloquentOperation extends Operation
{
    protected $kind = Operation::function;

    public function invoke(callable $callable, array $arguments)
    {
        /** @var Entity $entity */
        $entity = $this->getBoundParameter();

        /** @var Model $instance */
        $instance = $entity->getSource();

        list (, $method) = $callable;

        return call_user_func_array([$instance, $method], $arguments);
    }
}