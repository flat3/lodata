<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Interfaces\PipeInterface;
use Illuminate\Http\Request;

class FunctionOperation extends Operation
{
    public function getKind(): string
    {
        return 'Function';
    }

    public function getTransactionArguments(): array
    {
        return $this->inlineParameters;
    }

    public function invoke(): ?PipeInterface
    {
        $this->transaction->ensureMethod(Request::METHOD_GET, 'This operation must be addressed with a GET request');

        $result = parent::invoke();

        if (null === $result) {
            throw new InternalServerErrorException(
                'missing_function_result',
                'Function is required to return a result'
            );
        }

        return $result;
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
