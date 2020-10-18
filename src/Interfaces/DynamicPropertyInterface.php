<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;

interface DynamicPropertyInterface
{
    public function invoke(Entity $entity, Transaction $transaction);
}