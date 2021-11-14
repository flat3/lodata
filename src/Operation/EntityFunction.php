<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Operation;

class EntityFunction extends Operation
{
    protected $kind = Operation::function;

    public function invoke(callable $callable, array $arguments)
    {
        /** @var Entity $entity */
        $entity = $this->getBoundParameter();

        /** @var object $instance */
        $instance = $entity->getSource();

        list (, $method) = $callable;

        return call_user_func_array([$instance, $method], $arguments);
    }
}