<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class CountRestrictionsType extends ComplexType
{
    const Countable = 'Countable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.CountRestrictionsType');

        $this->addDeclaredProperty(self::Countable, Type::boolean());
    }
}