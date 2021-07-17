<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\DeleteRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Delete Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class DeleteRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.DeleteRestrictions';

    public function __construct()
    {
        $type = new DeleteRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(DeleteRestrictionsType::Deletable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setDeletable(bool $deletable): self
    {
        $this->value[DeleteRestrictionsType::Deletable]->setValue(Boolean::factory($deletable));

        return $this;
    }
}