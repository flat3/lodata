<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class FilterRestrictionsType extends ComplexType
{
    const filterable = 'Filterable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.FilterRestrictionsType');

        $this->addDeclaredProperty(self::filterable, Type::boolean());
    }
}