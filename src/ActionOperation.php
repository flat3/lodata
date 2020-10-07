<?php

namespace Flat3\OData;

use Flat3\OData\PathComponent\Operation;

class ActionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }
}
