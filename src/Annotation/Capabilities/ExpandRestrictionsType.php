<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class ExpandRestrictionsType extends ComplexType
{
    const Expandable = 'Expandable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.ExpandRestrictionsType');

        $this->addDeclaredProperty(self::Expandable, Type::boolean());
    }
}