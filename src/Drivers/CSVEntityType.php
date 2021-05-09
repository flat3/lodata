<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

/**
 * Class CSVEntityType
 * @package Flat3\Lodata\Drivers
 */
class CSVEntityType extends EntityType
{
    public function __construct($identifier)
    {
        parent::__construct($identifier);
        $this->setKey((new DeclaredProperty('offset', Type::int64()))->addAnnotation(new Computed()));
    }
}