<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\PathComponent\Operation;

class FunctionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Function';
    }

    public function setCallback(callable $callback): Operation
    {
        parent::setCallback($callback);

        $returnType = $this->getReflectedReturnType();

        if ($returnType === 'void') {
            throw new InternalServerErrorException('missing_return_type', 'Functions must have a return type');
        }

        return $this;
    }
}
