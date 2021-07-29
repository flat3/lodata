<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class CallbackType extends ComplexType
{
    const callbackProtocols = 'CallbackProtocols';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.CallbackType');

        $this->addDeclaredProperty(self::callbackProtocols, Type::collection());
    }
}