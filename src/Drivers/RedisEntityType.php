<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

/**
 * Class RedisEntityType
 * @package Flat3\Lodata\Drivers
 */
class RedisEntityType extends EntityType
{
    protected $open = true;

    public function __construct($identifier)
    {
        parent::__construct($identifier);
        $this->setKey(new DeclaredProperty('key', Type::string()));
    }
}