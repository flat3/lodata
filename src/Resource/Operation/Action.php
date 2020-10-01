<?php

namespace Flat3\OData\Resource\Operation;

use Flat3\OData\Resource\Operation;

class Action extends Operation
{
    public function getKind(): string
    {
        return 'Action';
    }
}
