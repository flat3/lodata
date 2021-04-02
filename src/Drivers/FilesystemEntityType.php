<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

class FilesystemEntityType extends EntityType
{
    public function __construct()
    {
        parent::__construct('file');

        $path = new DeclaredProperty('path', Type::string());
        $this->setKey($path);

        $this->addProperty((new DeclaredProperty('type', Type::string()))->setNullable(false));
        $this->addProperty((new DeclaredProperty('name', Type::string()))->setNullable(false));
        $this->addProperty((new DeclaredProperty('timestamp', Type::datetimeoffset()))->setNullable(false));
        $this->addProperty((new DeclaredProperty('size', Type::int64()))->setNullable(false)->addAnnotation(new Computed()));
    }
}