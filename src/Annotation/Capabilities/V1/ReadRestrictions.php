<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\ReadRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Read Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class ReadRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.ReadRestrictions');
        $type = new ReadRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(ReadRestrictionsType::readable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setReadable(bool $readable): self
    {
        $this->value[ReadRestrictionsType::readable]->setValue(new Boolean($readable));

        return $this;
    }

    public function isReadable(): bool
    {
        return $this->value[ReadRestrictionsType::readable]->getPrimitiveValue();
    }
}