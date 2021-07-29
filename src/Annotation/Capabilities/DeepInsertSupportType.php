<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class DeepInsertSupportType extends ComplexType
{
    const supported = 'Supported';
    const contentIdSupported = 'ContentIDSupported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.DeepInsertSupportType');

        $this->addDeclaredProperty(self::supported, Type::boolean());
        $this->addDeclaredProperty(self::contentIdSupported, Type::boolean());
    }
}