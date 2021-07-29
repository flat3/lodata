<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class ReadRestrictionsType extends ComplexType
{
    const readable = 'Readable';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.ReadRestrictionsType');

        $this->addDeclaredProperty(self::readable, Type::boolean());
    }
}