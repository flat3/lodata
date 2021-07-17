<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class DeepInsertSupportType extends ComplexType
{
    const Supported = 'Supported';
    const ContentIDSupported = 'ContentIDSupported';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.DeepInsertSupportType');

        $this->addDeclaredProperty(self::Supported, Type::boolean());
        $this->addDeclaredProperty(self::ContentIDSupported, Type::boolean());
    }
}