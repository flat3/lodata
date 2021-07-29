<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class SearchRestrictionsType extends ComplexType
{
    const searchable = 'Searchable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.SearchRestrictionsType');

        $this->addDeclaredProperty(self::searchable, Type::boolean());
    }
}