<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

/**
 * Class MongoEntityType
 * @package Flat3\Lodata\Drivers
 */
class MongoEntityType extends EntityType
{
    protected $open = true;

    public function __construct($identifier)
    {
        parent::__construct($identifier);
        $this->setKey((new DeclaredProperty('_id', Type::string()))
            ->addAnnotation(new Computed)
        );
    }
}