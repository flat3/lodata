<?php

namespace Flat3\OData;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\PathComponent\Operation;

class ActionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }
}
