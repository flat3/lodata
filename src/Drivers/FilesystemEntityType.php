<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Carbon\Carbon;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Type;

class FilesystemEntityType extends EntityType
{
    public function __construct($identifier = 'file')
    {
        parent::__construct($identifier);

        $this->setKey(new DeclaredProperty('path', Type::string()));

        $this->addProperty(
            (new DeclaredProperty('type', Type::string()))
                ->setNullable(false)
                ->addAnnotation(new Computed)
        );

        $this->addProperty(
            (new DeclaredProperty('name', Type::string()))
                ->setNullable(false)
                ->addAnnotation(new Computed)
        );

        $this->addProperty(
            (new DeclaredProperty('timestamp', Type::datetimeoffset()))
                ->setNullable(false)
                ->addAnnotation(new ComputedDefaultValue)
                ->setDefaultValue([Carbon::class, 'now'])
        );

        $this->addProperty(
            (new DeclaredProperty('size', Type::int64()))
                ->setNullable(false)
                ->addAnnotation(new Computed)
        );

        $this->addDeclaredProperty('content', Type::stream());
    }
}