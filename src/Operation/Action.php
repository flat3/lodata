<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Operation;

class Action extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }
}
