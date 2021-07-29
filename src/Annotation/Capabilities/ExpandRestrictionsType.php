<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class ExpandRestrictionsType extends ComplexType
{
    const expandable = 'Expandable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.ExpandRestrictionsType');

        $this->addDeclaredProperty(self::expandable, Type::boolean());
    }
}