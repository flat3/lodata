<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class DeleteRestrictionsType extends ComplexType
{
    const deletable = 'Deletable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.DeleteRestrictionsType');

        $this->addDeclaredProperty(self::deletable, Type::boolean());
    }
}