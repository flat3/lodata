<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SelectSupportType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * SelectSupport
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SelectSupport extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SelectSupport';

    public function __construct()
    {
        $type = new SelectSupportType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SelectSupportType::Supported))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setSupported(bool $supported): self
    {
        $this->value[SelectSupportType::Supported]->setValue(Boolean::factory($supported));

        return $this;
    }
}