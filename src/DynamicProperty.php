<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;

abstract class DynamicProperty extends Property
{
    abstract public function invoke(Entity $entity, Transaction $transaction);
}
