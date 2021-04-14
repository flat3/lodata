<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class SelectSupportType extends ComplexType
{
    const Supported = 'Supported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.SelectSupportType');

        $this->addDeclaredProperty(self::Supported, Type::boolean());
    }
}