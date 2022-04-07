<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SelectSupportType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * SelectSupport
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SelectSupport extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.SelectSupport');
        $type = new SelectSupportType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SelectSupportType::supported))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setSupported(bool $supported): self
    {
        $this->value[SelectSupportType::supported]->setValue(new Boolean($supported));

        return $this;
    }
}