<?php

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Type;

class CallbackProtocol extends ComplexType
{
    const Id = 'Id';
    const HTTP = 'http';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.CallbackProtocol');

        $this->addDeclaredProperty(self::Id, Type::string()->setNullable(true));
    }
}