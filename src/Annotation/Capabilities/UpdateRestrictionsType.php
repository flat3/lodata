<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class UpdateRestrictionsType extends ComplexType
{
    const updatable = 'Updatable';
    const deltaUpdateSupported = 'DeltaUpdateSupported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.UpdateRestrictionsType');

        $this->addDeclaredProperty(self::updatable, Type::boolean());
        $this->addDeclaredProperty(self::deltaUpdateSupported, Type::boolean());
    }
}