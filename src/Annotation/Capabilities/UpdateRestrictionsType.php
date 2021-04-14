<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class UpdateRestrictionsType extends ComplexType
{
    const Updatable = 'Updatable';
    const DeltaUpdateSupported = 'DeltaUpdateSupported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.UpdateRestrictionsType');

        $this->addDeclaredProperty(self::Updatable, Type::boolean());
        $this->addDeclaredProperty(self::DeltaUpdateSupported, Type::boolean());
    }
}