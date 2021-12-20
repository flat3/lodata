<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Type;

class CallbackProtocol extends ComplexType
{
    const id = 'Id';
    const http = 'http';

    public function __construct()
    {
        parent::__construct('Org.OData.Capabilities.CallbackProtocol');

        $this->addProperty((new DeclaredProperty(self::id, Type::string()))->setNullable(true));
    }
}