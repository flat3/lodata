<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Traits\HasIdentifier;

class EntityContainer extends ObjectArray implements NameInterface
{
    use HasIdentifier;

    public function __construct()
    {
        $this->setIdentifier('DefaultContainer');
    }
}