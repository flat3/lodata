<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\NavigationProperty;

interface ExpandInterface
{
    public function expand(Transaction $transaction, Entity $entity, NavigationProperty $navigationProperty): array;
}