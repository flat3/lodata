<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class SortRestrictionsType extends ComplexType
{
    const Sortable = 'Sortable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.SortRestrictionsType');

        $this->addDeclaredProperty(self::Sortable, Type::boolean());
    }
}