<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class DeleteRestrictionsType extends ComplexType
{
    const Deletable = 'Deletable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.DeleteRestrictionsType');

        $this->addDeclaredProperty(self::Deletable, Type::boolean());
    }
}