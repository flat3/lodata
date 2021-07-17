<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

class FilesystemEntityType extends EntityType
{
    public function __construct($identifier = 'file')
    {
        parent::__construct($identifier);

        $this->setKey(new DeclaredProperty('path', Type::string()))
            ->addProperty(
                (new DeclaredProperty('type', Type::string()))
                    ->setNullable(false)
            )
            ->addProperty(
                (new DeclaredProperty('name', Type::string()))
                    ->setNullable(false)
            )
            ->addProperty(
                (new DeclaredProperty('timestamp', Type::datetimeoffset()))
                    ->setNullable(false)
            )
            ->addProperty(
                (new DeclaredProperty('size', Type::int64()))
                    ->setNullable(false)
                    ->addAnnotation(new Computed())
            )
            ->addDeclaredProperty('content', Type::stream());
    }
}