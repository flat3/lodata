<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class InsertRestrictionsType extends ComplexType
{
    const Insertable = 'Insertable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.InsertRestrictionsType');

        $this->addDeclaredProperty(self::Insertable, Type::boolean());
    }
}