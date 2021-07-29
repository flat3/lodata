<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class SelectSupportType extends ComplexType
{
    const supported = 'Supported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.SelectSupportType');

        $this->addDeclaredProperty(self::supported, Type::boolean());
    }
}