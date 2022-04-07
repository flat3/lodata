<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\DeepInsertSupportType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Deep insert support
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class DeepInsertSupport extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.DeepInsertSupport');
        $type = new DeepInsertSupportType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(DeepInsertSupportType::supported))
            ->setValue(new Boolean(true));

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(DeepInsertSupportType::contentIdSupported))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setSupported(bool $supported): self
    {
        $this->value[DeepInsertSupportType::supported]->setValue(new Boolean($supported));

        return $this;
    }

    public function setContentIDSupported(bool $contentIDSupported): self
    {
        $this->value[DeepInsertSupportType::contentIdSupported]->setValue(new Boolean($contentIDSupported));

        return $this;
    }
}